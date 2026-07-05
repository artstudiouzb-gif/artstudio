<?php

use App\Core\Flash;
use App\Core\HeaderConfig;
use App\Core\Locale;
use App\Models\Language;
use App\Models\MenuItem;
use App\Models\Setting;

/** @var string $metaTitle */
/** @var string $metaDescription */
/** @var string $extraHeadCss */
/** @var string $ogImage */
/** @var string $ogType */

$siteName = Setting::get('site_name', 'ArtStudio');
$logo = Setting::get('logo_url', '');
$primaryColor = Setting::get('color_primary', '#1a1a1a');
$accentColor = Setting::get('color_accent', '#e63946');
$font = Setting::get('font_family', "'Inter', sans-serif");
$extraHeadCss = $extraHeadCss ?? '';

// --- SEO / Open Graph ---
$appUrl = rtrim((string) \App\Core\Config::get('app.url', ''), '/');
$canonicalUrl = $appUrl . Locale::url(Locale::path());
$ogType = $ogType ?? 'website';
$ogImageRaw = $ogImage ?? ($logo !== '' ? $logo : '');
// Абсолютный URL для og:image.
if ($ogImageRaw !== '' && !preg_match('#^https?://#', $ogImageRaw)) {
    $ogImageRaw = $appUrl . '/' . ltrim($ogImageRaw, '/');
}

$hcfg = HeaderConfig::get();
$currentLang = Locale::current();

// --- Логотип ---
$logoHtml = '<a href="' . htmlspecialchars(Locale::url('/', $currentLang), ENT_QUOTES) . '" class="site-header__logo">';
if ($logo !== '') {
    $logoHtml .= '<img src="' . htmlspecialchars($logo, ENT_QUOTES) . '" alt="' . htmlspecialchars($siteName, ENT_QUOTES) . '">';
} else {
    $logoHtml .= '<span>' . htmlspecialchars($siteName, ENT_QUOTES) . '</span>';
}
$logoHtml .= '</a>';

// --- Меню ---
$menuHtml = '';
$menuItems = MenuItem::activeForLang($currentLang);
if (!empty($menuItems)) {
    $menuHtml = '<nav class="site-menu">';
    foreach ($menuItems as $mi) {
        $url = MenuItem::resolveUrl($mi, $currentLang);
        $menuHtml .= '<a href="' . htmlspecialchars($url, ENT_QUOTES) . '">' . htmlspecialchars($mi['title'], ENT_QUOTES) . '</a>';
    }
    $menuHtml .= '</nav>';
}

// --- Переключатель языков ---
$langHtml = '';
$activeLangs = Language::active();
if ($hcfg['language_switcher']['enabled'] && count($activeLangs) > 1) {
    $flags = ['ru' => '🇷🇺', 'uz' => '🇺🇿', 'en' => '🇬🇧', 'kk' => '🇰🇿', 'tr' => '🇹🇷', 'de' => '🇩🇪'];
    $format = $hcfg['language_switcher']['format'];
    $path = Locale::path();
    $langHtml = '<div class="site-lang-switcher">';
    foreach ($activeLangs as $l) {
        $code = (string) $l['code'];
        $label = match ($format) {
            'name' => $l['name'],
            'flag' => $flags[$code] ?? strtoupper($code),
            default => strtoupper($code),
        };
        $href = Locale::url($path, $code);
        $isActive = $code === $currentLang ? ' is-active' : '';
        $langHtml .= '<a class="site-lang-switcher__item' . $isActive . '" href="' . htmlspecialchars($href, ENT_QUOTES) . '">' . htmlspecialchars((string) $label, ENT_QUOTES) . '</a>';
    }
    $langHtml .= '</div>';
}

// --- Кнопки соцсетей ---
$socialHtml = '';
if (!empty($hcfg['social_buttons'])) {
    $socialHtml = '<div class="site-social">';
    foreach ($hcfg['social_buttons'] as $btn) {
        $socialHtml .= '<a class="site-social__link site-social__link--' . htmlspecialchars($btn['network'], ENT_QUOTES) . '" href="'
            . htmlspecialchars($btn['url'], ENT_QUOTES) . '" target="_blank" rel="noopener" aria-label="' . htmlspecialchars($btn['network'], ENT_QUOTES) . '">'
            . htmlspecialchars(mb_strtoupper(mb_substr($btn['network'], 0, 1)), ENT_QUOTES) . '</a>';
    }
    $socialHtml .= '</div>';
}

// --- CTA-кнопка ---
$ctaHtml = '';
if ($hcfg['cta']['enabled'] && $hcfg['cta']['text'] !== '') {
    $ctaHtml = '<a class="site-cta site-cta--' . htmlspecialchars($hcfg['cta']['style'], ENT_QUOTES) . '" href="'
        . htmlspecialchars($hcfg['cta']['url'] !== '' ? $hcfg['cta']['url'] : '#', ENT_QUOTES) . '">'
        . htmlspecialchars($hcfg['cta']['text'], ENT_QUOTES) . '</a>';
}

// --- Раскладка по зонам ---
$zones = ['left' => '', 'center' => '', 'right' => ''];
$zones[$hcfg['logo_position']] .= $logoHtml;
$zones[$hcfg['menu_position']] .= $menuHtml;
// Утилиты (язык, соцсети, CTA) — в правую зону.
$zones['right'] .= $langHtml . $socialHtml . $ctaHtml;
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($currentLang, ENT_QUOTES) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($metaTitle, ENT_QUOTES) ?></title>
<?php if (!empty($metaDescription)): ?>
<meta name="description" content="<?= htmlspecialchars($metaDescription, ENT_QUOTES) ?>">
<?php endif; ?>
<link rel="canonical" href="<?= htmlspecialchars($canonicalUrl, ENT_QUOTES) ?>">
<meta property="og:site_name" content="<?= htmlspecialchars($siteName, ENT_QUOTES) ?>">
<meta property="og:type" content="<?= htmlspecialchars($ogType, ENT_QUOTES) ?>">
<meta property="og:title" content="<?= htmlspecialchars($metaTitle, ENT_QUOTES) ?>">
<?php if (!empty($metaDescription)): ?>
<meta property="og:description" content="<?= htmlspecialchars($metaDescription, ENT_QUOTES) ?>">
<?php endif; ?>
<meta property="og:url" content="<?= htmlspecialchars($canonicalUrl, ENT_QUOTES) ?>">
<?php if ($ogImageRaw !== ''): ?>
<meta property="og:image" content="<?= htmlspecialchars($ogImageRaw, ENT_QUOTES) ?>">
<meta name="twitter:card" content="summary_large_image">
<?php endif; ?>
<link rel="stylesheet" href="/assets/css/frontend.css">
<style>
:root {
    --color-primary: <?= htmlspecialchars($primaryColor, ENT_QUOTES) ?>;
    --color-accent: <?= htmlspecialchars($accentColor, ENT_QUOTES) ?>;
    --font-family: <?= htmlspecialchars($font, ENT_QUOTES) ?>;
}
</style>
<?php if ($extraHeadCss !== ''): ?>
<style id="block-styles">
<?= $extraHeadCss ?>
</style>
<?php endif; ?>
</head>
<body>
<header class="site-header site-header--logo-<?= htmlspecialchars($hcfg['logo_position'], ENT_QUOTES) ?>">
    <div class="site-header__inner">
        <div class="site-header__zone site-header__zone--left"><?= $zones['left'] ?></div>
        <div class="site-header__zone site-header__zone--center"><?= $zones['center'] ?></div>
        <div class="site-header__zone site-header__zone--right"><?= $zones['right'] ?></div>
    </div>
</header>
<main class="site-content">
<?php foreach (Flash::pull() as $flash): ?>
    <div class="site-alert site-alert--<?= htmlspecialchars($flash['type'], ENT_QUOTES) ?>">
        <?= htmlspecialchars($flash['message'], ENT_QUOTES) ?>
    </div>
<?php endforeach; ?>
