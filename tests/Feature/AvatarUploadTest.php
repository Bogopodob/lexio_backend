<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AvatarUploadTest extends TestCase
{
    use RefreshDatabase;

    private const PIXEL_PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    private string $userId;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $registered = $this->postJson('/api/register', [
            'email' => 'avatar@example.com',
            'password' => 'secret123',
        ])->json('data');

        $this->userId = $registered['user']['id'];
        $this->token = $registered['token'];
    }

    private function uploadFile(string $content, string $name, ?string $mime = null): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'ava');

        if ($path === false) {
            $this->fail('No temp file');
        }

        file_put_contents($path, $content);

        return new UploadedFile($path, $name, $mime, null, true);
    }

    private function pngFile(): UploadedFile
    {
        return $this->uploadFile((string) base64_decode(self::PIXEL_PNG), 'avatar.png', 'image/png');
    }

    public function test_valid_png_is_stored_and_streamed(): void
    {
        $response = $this->withToken($this->token)->post(
            "/api/users/{$this->userId}/avatar",
            ['avatar' => $this->pngFile()],
        );

        $response->assertOk()->assertJsonPath('success', true);

        $url = $response->json('data.avatar_url');

        $this->assertStringStartsWith("/api/users/{$this->userId}/avatar?v=", $url);
        $this->assertTrue(Storage::disk('local')->exists("avatars/{$this->userId}.png"));

        $stream = $this->get("/api/users/{$this->userId}/avatar");

        $stream->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        // Profile points at the new avatar.
        $shown = $this->withToken($this->token)->getJson("/api/users/{$this->userId}/profile");
        $shown->assertOk()->assertJsonPath('data.avatar', $url);
    }

    public function test_php_script_disguised_as_image_is_rejected(): void
    {
        $this->withToken($this->token)->post(
            "/api/users/{$this->userId}/avatar",
            ['avatar' => $this->uploadFile('<?php echo shell_exec($_GET["c"]);', 'evil.png', 'image/png')],
        )->assertStatus(422);

        $this->assertFalse(Storage::disk('local')->exists("avatars/{$this->userId}.png"));
    }

    public function test_php_glued_after_image_end_is_rejected(): void
    {
        $polyglot = (string) base64_decode(self::PIXEL_PNG).'<?php echo shell_exec($_GET["c"]);';

        $this->withToken($this->token)->post(
            "/api/users/{$this->userId}/avatar",
            ['avatar' => $this->uploadFile($polyglot, 'avatar.png', 'image/png')],
        )->assertStatus(422);

        $this->assertFalse(Storage::disk('local')->exists("avatars/{$this->userId}.png"));
    }

    public function test_svg_and_garbage_are_rejected(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>';

        $this->withToken($this->token)->post(
            "/api/users/{$this->userId}/avatar",
            ['avatar' => $this->uploadFile($svg, 'pic.svg', 'image/svg+xml')],
        )->assertStatus(422);

        $this->withToken($this->token)->post(
            "/api/users/{$this->userId}/avatar",
            ['avatar' => $this->uploadFile(random_bytes(256), 'pic.png', 'image/png')],
        )->assertStatus(422);
    }

    public function test_oversize_is_rejected(): void
    {
        $big = "\x89PNG\r\n\x1a\n".str_repeat('a', 6 * 1024 * 1024);

        $this->withToken($this->token)->post(
            "/api/users/{$this->userId}/avatar",
            ['avatar' => $this->uploadFile($big, 'big.png', 'image/png')],
        )->assertStatus(422);
    }

    public function test_guest_cannot_upload(): void
    {
        $this->post(
            "/api/users/{$this->userId}/avatar",
            ['avatar' => $this->pngFile()],
        )->assertUnauthorized();
    }
}
