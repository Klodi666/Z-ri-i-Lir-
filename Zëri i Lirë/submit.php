<?php
declare(strict_types=1);

/*
 * Pranon raportet. Skedarët ruhen JASHTË DocumentRoot, me emra të rastësishëm.
 * Nuk regjistron IP, user-agent apo ndonjë të dhënë tjetër të dërguesit.
 */

const BASE_DIR   = '/var/lib/zeri/incoming';   // jashtë /var/www
const MAX_FILES  = 10;
const MAX_BYTES  = 10 * 1024 * 1024;           // 10 MB për skedar
const MAX_TEXT   = 20000;

// Lejohen vetëm këto lloje (kontrollohet përmbajtja reale, jo emri)
const ALLOWED = [
    'image/jpeg'      => 'jpg',
    'image/png'       => 'png',
    'image/webp'      => 'webp',
    'application/pdf' => 'pdf',
    'text/plain'      => 'txt',
];

function fail(int $code, string $msg): never {
    http_response_code($code);
    page('Gabim', $msg);
    exit;
}

function page(string $title, string $msg): void {
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-store');
    $t = htmlspecialchars($title, ENT_QUOTES);
    $m = htmlspecialchars($msg, ENT_QUOTES);
    echo "<!DOCTYPE html><html lang=\"sq\"><head><meta charset=\"utf-8\">"
       . "<meta name=\"viewport\" content=\"width=device-width,initial-scale=1\">"
       . "<title>$t</title></head><body style=\"font:1rem/1.6 system-ui,sans-serif;max-width:40rem;margin:3rem auto;padding:0 1rem\">"
       . "<h1>$t</h1><p>$m</p><p><a href=\"faqja.html\">Kthehu te faqja kryesore</a></p></body></html>";
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail(405, 'Metodë e palejuar.');
}

$mesazhi = trim((string)($_POST['mesazhi'] ?? ''));
if ($mesazhi === '' || mb_strlen($mesazhi) > MAX_TEXT) {
    fail(400, 'Përshkrimi mungon ose është shumë i gjatë.');
}
if (empty($_POST['miratim'])) {
    fail(400, 'Duhet të pranosh kushtin para dërgimit.');
}

// Dosje e re për çdo raport, me emër të rastësishëm
$id  = bin2hex(random_bytes(16));
$dir = BASE_DIR . '/' . $id;
if (!mkdir($dir, 0700, true)) {
    fail(500, 'Raporti nuk u ruajt. Provo përsëri.');
}

file_put_contents($dir . '/mesazhi.txt', $mesazhi);
chmod($dir . '/mesazhi.txt', 0600);

// Skedarët
$saved = 0;
if (!empty($_FILES['skedaret']['name'][0])) {
    $names = $_FILES['skedaret']['name'];
    if (count($names) > MAX_FILES) {
        fail(400, 'Shumë skedarë. Maksimumi është ' . MAX_FILES . '.');
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);

    foreach ($names as $i => $_) {
        $err = $_FILES['skedaret']['error'][$i];
        $tmp = $_FILES['skedaret']['tmp_name'][$i];
        $size = $_FILES['skedaret']['size'][$i];

        if ($err === UPLOAD_ERR_NO_FILE) continue;
        if ($err !== UPLOAD_ERR_OK || !is_uploaded_file($tmp)) {
            fail(400, 'Një skedar nuk u ngarkua saktë.');
        }
        if ($size > MAX_BYTES) {
            fail(400, 'Një skedar kalon 10 MB.');
        }
        $mime = $finfo->file($tmp);
        if (!isset(ALLOWED[$mime])) {
            fail(400, 'Lloj skedari i palejuar. Lejohen JPG, PNG, WEBP, PDF dhe TXT.');
        }

        // Emri origjinal hidhet poshtë (mund të përmbajë të dhëna personale)
        $dest = $dir . '/' . bin2hex(random_bytes(8)) . '.' . ALLOWED[$mime];
        if (!move_uploaded_file($tmp, $dest)) {
            fail(500, 'Skedari nuk u ruajt.');
        }
        chmod($dest, 0600);
        $saved++;
    }
}

page('Raporti u dërgua', "Faleminderit. Raporti u pranua me $saved skedarë dhe do të shqyrtohet para çdo publikimi.");
