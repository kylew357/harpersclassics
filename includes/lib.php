<?php
declare(strict_types=1);

// Core helpers: reads the cars/ and sold/ folders on every request,
// so adding or removing a folder updates the site immediately.

define('SITE_ROOT', dirname(__DIR__));
const IMAGE_EXT = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif', 'svg'];

function h(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function site(): array
{
    static $cfg = null;
    if ($cfg !== null) {
        return $cfg;
    }
    $cfg = [
        'name' => 'My Classic Cars',
        'tagline' => '',
        'headline' => 'Classic cars, hand-picked.',
        'subheadline' => '',
        'since' => '',
        'phone' => '',
        'email' => '',
        'address' => '',
        'hours' => [],
        'social' => [],
    ];
    $file = SITE_ROOT . '/site.json';
    if (is_file($file)) {
        $json = json_decode((string)file_get_contents($file), true);
        if (is_array($json)) {
            $cfg = array_merge($cfg, $json);
        }
    }
    foreach (['hours', 'social'] as $k) {
        if (!is_array($cfg[$k])) {
            $cfg[$k] = [];
        }
    }
    return $cfg;
}

function url_path(string ...$parts): string
{
    return implode('/', array_map('rawurlencode', $parts));
}

function phone_href(string $phone): string
{
    return 'tel:' . preg_replace('/[^\d+]/', '', $phone);
}

function spec_get(array $specs, string $key): string
{
    foreach ($specs as [$k, $v]) {
        if (strcasecmp($k, $key) === 0) {
            return $v;
        }
    }
    return '';
}

// description.txt: optional "Key: Value" lines, a blank line, then paragraphs.
function parse_description(string $raw): array
{
    $raw = str_replace(["\r\n", "\r"], "\n", trim($raw));
    $blocks = $raw === '' ? [] : preg_split('/\n\s*\n/', $raw);
    $specs = [];

    if ($blocks) {
        $parsed = [];
        $allSpecs = true;
        foreach (explode("\n", $blocks[0]) as $line) {
            if (preg_match('/^([A-Za-z][A-Za-z0-9 \/]{0,24}):\s*(.+)$/', trim($line), $m)) {
                $parsed[] = [trim($m[1]), trim($m[2])];
            } else {
                $allSpecs = false;
                break;
            }
        }
        if ($allSpecs && $parsed) {
            $specs = $parsed;
            array_shift($blocks);
        }
    }

    $paragraphs = [];
    foreach ($blocks as $b) {
        $p = trim((string)preg_replace('/\s*\n\s*/', ' ', $b));
        if ($p !== '') {
            $paragraphs[] = $p;
        }
    }

    $short = $paragraphs[0] ?? '';
    if (mb_strlen($short) > 200) {
        $short = rtrim((string)preg_replace('/\s+\S*$/u', '', mb_substr($short, 0, 197))) . '…';
    }

    return ['specs' => $specs, 'paragraphs' => $paragraphs, 'short' => $short];
}

function load_car(string $type, string $dir): array
{
    $path = SITE_ROOT . '/' . $type . '/' . $dir;
    $files = [];
    foreach (scandir($path) ?: [] as $f) {
        if ($f[0] !== '.' && is_file($path . '/' . $f)) {
            $files[] = $f;
        }
    }

    $txts = array_values(array_filter($files, fn($f) => strtolower(pathinfo($f, PATHINFO_EXTENSION)) === 'txt'));
    natcasesort($txts);
    $txts = array_values($txts);
    $desc = parse_description($txts ? (string)file_get_contents($path . '/' . $txts[0]) : '');

    $images = array_values(array_filter(
        $files,
        fn($f) => in_array(strtolower(pathinfo($f, PATHINFO_EXTENSION)), IMAGE_EXT, true)
    ));
    usort($images, 'strnatcasecmp');
    foreach ($images as $i => $img) {
        if ($i > 0 && strtolower(pathinfo($img, PATHINFO_FILENAME)) === 'cover') {
            array_splice($images, $i, 1);
            array_unshift($images, $img);
            break;
        }
    }

    $status = spec_get($desc['specs'], 'status');
    if ($status === '' && $type === 'sold') {
        $status = 'Sold';
    }

    return [
        'type' => $type,
        'name' => $dir,
        'images' => $images,
        'specs' => $desc['specs'],
        'paragraphs' => $desc['paragraphs'],
        'short' => $desc['short'],
        'price' => spec_get($desc['specs'], 'price'),
        'status' => $status,
        'mtime' => (int)filemtime($path),
    ];
}

// $type is 'cars' (for sale) or 'sold'. Newest folders first.
function scan_cars(string $type): array
{
    if (!in_array($type, ['cars', 'sold'], true)) {
        return [];
    }
    $base = SITE_ROOT . '/' . $type;
    if (!is_dir($base)) {
        return [];
    }
    $cars = [];
    foreach (scandir($base) ?: [] as $d) {
        if ($d[0] === '.' || $d[0] === '_' || !is_dir($base . '/' . $d)) {
            continue;
        }
        $cars[] = load_car($type, $d);
    }
    usort($cars, fn($a, $b) => $b['mtime'] <=> $a['mtime'] ?: strnatcasecmp($a['name'], $b['name']));
    return $cars;
}

// Looks the car up in the scanned list, never builds a path from user input.
function find_car(string $type, string $name): ?array
{
    foreach (scan_cars($type) as $car) {
        if ($car['name'] === $name) {
            return $car;
        }
    }
    return null;
}

function car_url(array $car): string
{
    return 'car.php?type=' . rawurlencode($car['type']) . '&car=' . rawurlencode($car['name']);
}

function img_url(array $car, string $file): string
{
    return url_path($car['type'], $car['name'], $file);
}

function about_paragraphs(): array
{
    $file = SITE_ROOT . '/about.txt';
    $raw = is_file($file) ? trim((string)file_get_contents($file)) : '';
    if ($raw === '') {
        return ['Add your story to about.txt and it will show up here.'];
    }
    $out = [];
    foreach (preg_split('/\n\s*\n/', str_replace("\r\n", "\n", $raw)) as $p) {
        $p = trim((string)preg_replace('/\s*\n\s*/', ' ', $p));
        if ($p !== '') {
            $out[] = $p;
        }
    }
    return $out;
}
