<?php
declare(strict_types=1);

const SUPPORTED_LANGS = ['en', 'ar'];

function current_lang(): string
{
    if (isset($_SESSION['lang']) && in_array($_SESSION['lang'], SUPPORTED_LANGS, true)) {
        return $_SESSION['lang'];
    }
    // First visit: follow the browser (useful on the public NFC page)
    $accept = strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '');
    return str_starts_with($accept, 'ar') ? 'ar' : 'en';
}

function set_lang(string $lang): void
{
    if (in_array($lang, SUPPORTED_LANGS, true)) {
        $_SESSION['lang'] = $lang;
        // Remember it on the account so notifications use the same language
        if (!empty($_SESSION['user_id'])) q('UPDATE users SET lang=? WHERE id=?', [$lang, $_SESSION['user_id']]);
    }
}

function is_rtl(): bool
{
    return current_lang() === 'ar';
}

/** Translate a key; :placeholders are replaced from $vars (escaped later by the view). */
function t(string $key, array $vars = [], ?string $lang = null): string
{
    static $strings = [];
    $lang = in_array($lang, SUPPORTED_LANGS, true) ? $lang : current_lang();
    if (!isset($strings[$lang])) {
        $strings[$lang] = require BASE_PATH . "/lang/{$lang}.php";
    }
    $text = $strings[$lang][$key] ?? $key;
    foreach ($vars as $k => $v) {
        $text = str_replace(':' . $k, (string)$v, $text);
    }
    return $text;
}
