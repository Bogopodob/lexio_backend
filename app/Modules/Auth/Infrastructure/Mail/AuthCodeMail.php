<?php

namespace App\Modules\Auth\Infrastructure\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class AuthCodeMail extends Mailable
{
    public string $lang;

    public int $ttlMinutes;

    /** @var array<string, string> */
    public array $copy;

    public function __construct(
        public string $code,
        public bool $isNewUser,
        ?string $locale = null,
        int $ttlSeconds = 300,
    ) {
        $this->lang = mb_strtolower(trim((string) ($locale ?? 'ru'))) === 'en' ? 'en' : 'ru';
        $this->ttlMinutes = max(1, (int) round($ttlSeconds / 60));
        $this->copy = $this->isNewUser ? $this->welcomeCopy() : $this->loginCopy();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->copy['subject']);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.auth-code');
    }

    /** @return array<string, string> */
    private function welcomeCopy(): array
    {
        if ($this->lang === 'en') {
            return [
                'subject' => 'Welcome to Lexio! Your login code inside',
                'preheader' => 'Your dictionary is ready — here is your login code.',
                'badge' => 'Welcome aboard',
                'heading' => 'Welcome to Lexio!',
                'intro' => 'Your account is created. Enter this code to dive into words — no passwords needed, ever.',
                'codeLabel' => 'Your login code',
                'expires' => "The code lives for {$this->ttlMinutes} min.",
                'perksTitle' => 'What awaits you',
                'perk1' => '20 words a day',
                'perk1Sub' => 'a tiny daily goal',
                'perk2' => 'Smart repetition',
                'perk2Sub' => 'memory science built-in',
                'perk3' => 'Living phrases',
                'perk3Sub' => 'café, travel, life',
                'ignore' => 'If this wasn’t you, just ignore this email.',
                'footer' => 'Lexio • learn languages',
            ];
        }

        return [
            'subject' => 'Добро пожаловать в Lexio! Твой код входа внутри',
            'preheader' => 'Аккаунт готов — вот твой код входа.',
            'badge' => 'Ты с нами',
            'heading' => 'Добро пожаловать в Lexio!',
            'intro' => 'Аккаунт создан. Введи этот код, чтобы нырнуть в слова — пароли больше вообще не нужны.',
            'codeLabel' => 'Твой код входа',
            'expires' => "Код живёт {$this->ttlMinutes} мин.",
            'perksTitle' => 'Что тебя ждёт',
            'perk1' => '20 слов в день',
            'perk1Sub' => 'маленькая цель на день',
            'perk2' => 'Умные повторения',
            'perk2Sub' => 'наука о памяти внутри',
            'perk3' => 'Живые фразы',
            'perk3Sub' => 'кафе, поездки, жизнь',
            'ignore' => 'Если это были не вы — просто проигнорируйте письмо.',
            'footer' => 'Lexio • учи языки',
        ];
    }

    /** @return array<string, string> */
    private function loginCopy(): array
    {
        if ($this->lang === 'en') {
            return [
                'subject' => 'Your Lexio login code',
                'preheader' => 'Here is your one-time login code.',
                'badge' => 'Login',
                'heading' => 'Welcome back!',
                'intro' => 'Your words missed you. Enter this code to keep the streak going.',
                'codeLabel' => 'Your login code',
                'expires' => "The code lives for {$this->ttlMinutes} min.",
                'perksTitle' => '',
                'perk1' => '',
                'perk1Sub' => '',
                'perk2' => '',
                'perk2Sub' => '',
                'perk3' => '',
                'perk3Sub' => '',
                'ignore' => 'If this wasn’t you, just ignore this email.',
                'footer' => 'Lexio • learn languages',
            ];
        }

        return [
            'subject' => 'Твой код входа в Lexio',
            'preheader' => 'Вот твой одноразовый код входа.',
            'badge' => 'Вход',
            'heading' => 'С возвращением!',
            'intro' => 'Твои слова соскучились. Введи код, чтобы продолжить серию.',
            'codeLabel' => 'Твой код входа',
            'expires' => "Код живёт {$this->ttlMinutes} мин.",
            'perksTitle' => '',
            'perk1' => '',
            'perk1Sub' => '',
            'perk2' => '',
            'perk2Sub' => '',
            'perk3' => '',
            'perk3Sub' => '',
            'ignore' => 'Если это были не вы — просто проигнорируйте письмо.',
            'footer' => 'Lexio • учи языки',
        ];
    }
}
