<!DOCTYPE html>
<html lang="{{ $lang }}" xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="color-scheme" content="dark">
  <meta name="supported-color-schemes" content="dark">
  <title>{{ $copy['subject'] }}</title>
</head>
<body style="margin:0;padding:0;background-color:#0f0f0f;-webkit-text-size-adjust:100%;">
  <div style="display:none;max-height:0;overflow:hidden;opacity:0;">{{ $copy['preheader'] }}</div>

  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#0f0f0f;padding:32px 16px;">
    <tr>
      <td align="center">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">

          <!-- Logo -->
          <tr>
            <td align="center" style="padding-bottom:24px;">
              <table role="presentation" cellpadding="0" cellspacing="0">
                <tr>
                  <td align="center" style="width:56px;height:56px;border-radius:16px;background:linear-gradient(135deg,#5AD4B5,#5B74FF);font-family:Arial,Helvetica,sans-serif;font-size:30px;font-weight:900;color:#062a20;">
                    L
                  </td>
                  <td style="padding-left:12px;font-family:Arial,Helvetica,sans-serif;font-size:22px;font-weight:900;color:#ffffff;letter-spacing:-0.5px;">
                    Lexio
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Card -->
          <tr>
            <td style="background-color:#171717;border:1px solid #262626;border-radius:24px;overflow:hidden;">
              <!-- gradient top line -->
              <div style="height:4px;background:linear-gradient(90deg,#5AD4B5,#5B74FF);font-size:0;line-height:0;">&nbsp;</div>

              <div style="padding:36px 32px 32px 32px;text-align:center;">
                <span style="display:inline-block;padding:6px 14px;border-radius:999px;background-color:rgba(90,212,181,0.10);border:1px solid rgba(90,212,181,0.30);font-family:Arial,Helvetica,sans-serif;font-size:11px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:#5AD4B5;">
                  {{ $copy['badge'] }}
                </span>

                <h1 style="margin:20px 0 0 0;font-family:Arial,Helvetica,sans-serif;font-size:30px;line-height:36px;font-weight:900;color:#ffffff;letter-spacing:-0.5px;">
                  {{ $copy['heading'] }}
                </h1>
                <p style="margin:12px 0 0 0;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:23px;color:rgba(255,255,255,0.65);">
                  {{ $copy['intro'] }}
                </p>

                <p style="margin:28px 0 0 0;font-family:Arial,Helvetica,sans-serif;font-size:11px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:rgba(255,255,255,0.40);">
                  {{ $copy['codeLabel'] }}
                </p>

                <!-- Code digits -->
                <table role="presentation" cellpadding="0" cellspacing="0" align="center" style="margin:14px auto 0 auto;">
                  <tr>
                    @foreach (mb_str_split($code) as $digit)
                      <td align="center" style="width:52px;height:64px;border-radius:14px;background-color:rgba(90,212,181,0.08);border:1px solid rgba(90,212,181,0.35);font-family:Arial,Helvetica,sans-serif;font-size:30px;font-weight:900;color:#5AD4B5;">
                        {{ $digit }}
                      </td>
                      @if (!$loop->last)
                        <td style="width:8px;font-size:0;line-height:0;">&nbsp;</td>
                      @endif
                    @endforeach
                  </tr>
                </table>

                <p style="margin:16px 0 0 0;font-family:Arial,Helvetica,sans-serif;font-size:13px;color:rgba(255,255,255,0.45);">
                  &#9201; {{ $copy['expires'] }}
                </p>

                @if ($isNewUser)
                  <p style="margin:30px 0 0 0;font-family:Arial,Helvetica,sans-serif;font-size:11px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:rgba(255,255,255,0.40);">
                    {{ $copy['perksTitle'] }}
                  </p>
                  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:12px;">
                    <tr>
                      <td width="33%" align="center" style="padding:14px 6px;border-radius:16px;background-color:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.06);">
                        <div style="font-size:22px;">&#128293;</div>
                        <div style="margin-top:6px;font-family:Arial,Helvetica,sans-serif;font-size:13px;font-weight:900;color:#ffffff;">{{ $copy['perk1'] }}</div>
                        <div style="margin-top:2px;font-family:Arial,Helvetica,sans-serif;font-size:11px;color:rgba(255,255,255,0.45);">{{ $copy['perk1Sub'] }}</div>
                      </td>
                      <td style="width:8px;font-size:0;">&nbsp;</td>
                      <td width="33%" align="center" style="padding:14px 6px;border-radius:16px;background-color:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.06);">
                        <div style="font-size:22px;">&#129504;</div>
                        <div style="margin-top:6px;font-family:Arial,Helvetica,sans-serif;font-size:13px;font-weight:900;color:#ffffff;">{{ $copy['perk2'] }}</div>
                        <div style="margin-top:2px;font-family:Arial,Helvetica,sans-serif;font-size:11px;color:rgba(255,255,255,0.45);">{{ $copy['perk2Sub'] }}</div>
                      </td>
                      <td style="width:8px;font-size:0;">&nbsp;</td>
                      <td width="33%" align="center" style="padding:14px 6px;border-radius:16px;background-color:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.06);">
                        <div style="font-size:22px;">&#128172;</div>
                        <div style="margin-top:6px;font-family:Arial,Helvetica,sans-serif;font-size:13px;font-weight:900;color:#ffffff;">{{ $copy['perk3'] }}</div>
                        <div style="margin-top:2px;font-family:Arial,Helvetica,sans-serif;font-size:11px;color:rgba(255,255,255,0.45);">{{ $copy['perk3Sub'] }}</div>
                      </td>
                    </tr>
                  </table>
                @endif
              </div>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td align="center" style="padding:20px 8px 0 8px;">
              <p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:12px;color:rgba(255,255,255,0.35);">
                {{ $copy['ignore'] }}
              </p>
              <p style="margin:10px 0 0 0;font-family:Arial,Helvetica,sans-serif;font-size:12px;font-weight:700;color:rgba(255,255,255,0.45);">
                {{ $copy['footer'] }}
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
