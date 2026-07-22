<?php
define('MANGA_DIR', __DIR__ . '/manga');
define('MANGA_URL', 'manga');
define('SITE_NAME', 'kuchinashi');

define('IMG_EXT', ['jpg','jpeg','png','gif','webp']);

if (!headers_sent()) {
    header('Referrer-Policy: no-referrer');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

function is_image($file) {
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    return in_array($ext, IMG_EXT);
}

function list_manga() {
    $list = [];
    if (!is_dir(MANGA_DIR)) return $list;
    foreach (scandir(MANGA_DIR) as $entry) {
        if ($entry === '.' || $entry === '..') continue;
        $path = MANGA_DIR . '/' . $entry;
        if (is_dir($path)) {
            $list[] = $entry;
        }
    }
    natsort($list);
    return array_values($list);
}

function list_chapters($manga) {
    $list = [];
    $dir = MANGA_DIR . '/' . $manga;
    if (!is_dir($dir)) return $list;
    foreach (scandir($dir) as $entry) {
        if ($entry === '.' || $entry === '..') continue;
        $path = $dir . '/' . $entry;
        if (is_dir($path)) {
            $list[] = $entry;
        }
    }
    natsort($list);
    return array_values($list);
}

function list_pages($manga, $chapter) {
    $list = [];
    $dir = MANGA_DIR . '/' . $manga . '/' . $chapter;
    if (!is_dir($dir)) return $list;
    foreach (scandir($dir) as $entry) {
        if (is_image($entry)) $list[] = $entry;
    }
    natsort($list);
    return array_values($list);
}

function get_cover($manga) {
    $dir = MANGA_DIR . '/' . $manga;
    foreach (IMG_EXT as $ext) {
        if (file_exists("$dir/cover.$ext")) {
            return MANGA_URL . '/' . rawurlencode($manga) . '/cover.' . $ext;
        }
    }
    $chapters = list_chapters($manga);
    if (count($chapters) > 0) {
        $pages = list_pages($manga, $chapters[0]);
        if (count($pages) > 0) {
            return MANGA_URL . '/' . rawurlencode($manga) . '/' . rawurlencode($chapters[0]) . '/' . rawurlencode($pages[0]);
        }
    }
    return null;
}

function e($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
