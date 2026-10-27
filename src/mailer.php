<?php
declare(strict_types=1);

/*
 * Sending email. With 'mail' => ['smtp_host' => ...] in config.php, mail goes through
 * your mailbox over SMTP (Hostinger: smtp.hostinger.com, port 465, 'ssl'), which lands in
 * inboxes far more reliably than PHP's mail(). Without SMTP settings, mail() is used.
 */

/** Sender address, or '' when email is not set up (then nothing is sent). */
function mail_from(): string
{
    return (string)(config('mail.from') ?: config('mail_from', ''));
}

function mail_configured(): bool
{
    return mail_from() !== '';
}

/** Header-safe encoded words for non-ASCII text (Arabic subjects and names). */
function mail_encode(string $text): string
{
    return preg_match('/[^\x20-\x7E]/', $text) ? '=?UTF-8?B?' . base64_encode($text) . '?=' : $text;
}

/** Send an email with an HTML body and a plain-text alternative. Returns false on failure (logged). */
function send_mail(string $to, string $subject, string $html, string $text): bool
{
    $from = mail_from();
    if ($from === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) return false;
    $fromName = (string)config('mail.from_name', 'MyKid Wedjat');
    $boundary = 'mkw' . bin2hex(random_bytes(12));
    $domain = substr(strrchr($from, '@') ?: '@localhost', 1);

    $headers = [
        'Date: ' . date('r'),
        'From: ' . mail_encode($fromName) . " <{$from}>",
        'Reply-To: ' . (config('support_email') ?: $from),
        'Message-ID: <' . bin2hex(random_bytes(16)) . "@{$domain}>",
        'MIME-Version: 1.0',
        "Content-Type: multipart/alternative; boundary=\"{$boundary}\"",
    ];
    $body = "--{$boundary}\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
          . chunk_split(base64_encode($text))
          . "--{$boundary}\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
          . chunk_split(base64_encode($html))
          . "--{$boundary}--\r\n";

    try {
        if (config('mail.smtp_host')) {
            $message = implode("\r\n", array_merge($headers, ["To: <{$to}>", 'Subject: ' . mail_encode($subject)])) . "\r\n\r\n" . $body;
            smtp_send($from, $to, $message);
            return true;
        }
        if (!@mail($to, mail_encode($subject), $body, implode("\r\n", $headers), '-f' . $from)) {
            throw new RuntimeException('mail() returned false');
        }
        return true;
    } catch (Throwable $ex) {
        error_log("Mail to {$to} failed: " . $ex->getMessage());
        return false;
    }
}

/** Minimal SMTP client: SSL (465) or STARTTLS (587), AUTH LOGIN. */
function smtp_send(string $from, string $to, string $message): void
{
    $host = (string)config('mail.smtp_host');
    $secure = (string)config('mail.smtp_secure', 'ssl');
    $port = (int)config('mail.smtp_port', $secure === 'ssl' ? 465 : 587);
    $fp = @stream_socket_client(($secure === 'ssl' ? 'ssl://' : 'tcp://') . "{$host}:{$port}", $errno, $errstr, 20);
    if (!$fp) throw new RuntimeException("SMTP connect {$host}:{$port} failed: {$errstr}");
    stream_set_timeout($fp, 20);

    // $label is what an error message shows instead of the line (never the credentials)
    $talk = function (?string $line, array $expect, ?string $label = null) use ($fp): string {
        if ($line !== null) fwrite($fp, $line . "\r\n");
        $reply = '';
        while (($l = fgets($fp, 1024)) !== false) {
            $reply .= $l;
            if (strlen($l) < 4 || $l[3] === ' ') break; // last line of a multi-line reply
        }
        if (!in_array((int)substr($reply, 0, 3), $expect, true)) {
            throw new RuntimeException('SMTP ' . ($label ?? ($line === null ? 'greeting' : strtok($line, "\r\n"))) . ': ' . trim($reply));
        }
        return $reply;
    };
    try {
        $me = parse_url(site_base(), PHP_URL_HOST) ?: 'localhost';
        $talk(null, [220]);
        $talk("EHLO {$me}", [250]);
        if ($secure === 'tls') {
            $talk('STARTTLS', [220]);
            if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) throw new RuntimeException('STARTTLS failed');
            $talk("EHLO {$me}", [250]);
        }
        if (config('mail.smtp_user')) {
            $talk('AUTH LOGIN', [334]);
            $talk(base64_encode((string)config('mail.smtp_user')), [334], 'AUTH user');
            $talk(base64_encode((string)config('mail.smtp_pass')), [235], 'AUTH password (check smtp_user / smtp_pass)');
        }
        $talk("MAIL FROM:<{$from}>", [250]);
        $talk("RCPT TO:<{$to}>", [250, 251]);
        $talk('DATA', [354]);
        // Dot-stuffing: a line starting with "." gets a second "."
        $talk(preg_replace('/^\./m', '..', $message) . "\r\n.", [250], 'message body');
        $talk('QUIT', [221]);
    } finally {
        fclose($fp);
    }
}

/**
 * Branded email in the user's language. $paragraphs are plain text; $button is [label, url].
 * Returns [html, text].
 */
function email_body(string $lang, string $title, array $paragraphs, ?array $button = null, string $footer = ''): array
{
    $rtl = $lang === 'ar';
    $dir = $rtl ? 'rtl' : 'ltr';
    $align = $rtl ? 'right' : 'left';
    $esc = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    $html = '<!doctype html><html lang="' . $lang . '" dir="' . $dir . '"><body style="margin:0;background:#FBF7EF;font-family:Cairo,Segoe UI,Tahoma,Arial,sans-serif;color:#16213E">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#FBF7EF;padding:24px 12px"><tr><td align="center">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;background:#ffffff;border-radius:16px;border:1px solid #E9E2D3">'
        . '<tr><td style="background:#1B3F8F;border-radius:16px 16px 0 0;padding:18px 24px;color:#ffffff;font-size:20px;font-weight:800;text-align:' . $align . '">MyKid <span style="color:#F2B544">Wedjat</span></td></tr>'
        . '<tr><td dir="' . $dir . '" style="padding:24px;text-align:' . $align . ';font-size:16px;line-height:1.6">'
        . '<h1 style="margin:0 0 12px;font-size:22px;color:#122C66">' . $esc($title) . '</h1>';
    foreach ($paragraphs as $p) $html .= '<p style="margin:0 0 12px">' . $esc($p) . '</p>';
    if ($button) {
        $html .= '<p style="margin:20px 0"><a href="' . $esc($button[1]) . '" style="display:inline-block;background:#1B3F8F;color:#ffffff;text-decoration:none;font-weight:700;padding:12px 24px;border-radius:999px">' . $esc($button[0]) . '</a></p>'
            . '<p style="margin:0 0 12px;font-size:13px;color:#5A6478;word-break:break-all" dir="ltr">' . $esc($button[1]) . '</p>';
    }
    if ($footer !== '') $html .= '<p style="margin:16px 0 0;font-size:13px;color:#5A6478">' . $esc($footer) . '</p>';
    $html .= '</td></tr></table></td></tr></table></body></html>';

    $text = $title . "\n\n" . implode("\n\n", $paragraphs) . ($button ? "\n\n{$button[0]}: {$button[1]}" : '') . ($footer !== '' ? "\n\n{$footer}" : '') . "\n\nMyKid Wedjat";
    return [$html, $text];
}
