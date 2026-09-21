<?php

use App\Support\QrCode;
use chillerlan\QRCode\Common\GDLuminanceSource;
use chillerlan\QRCode\Decoder\Decoder;
use chillerlan\QRCode\QROptions;

/**
 * Rasterise an SVG QR output (dark modules only) into a binary matrix grid.
 *
 * @return array{int, array<int, array<int, int>>}
 */
function qrSvgToMatrix(string $svg): array
{
    $doc = new DOMDocument;
    $doc->loadXML($svg);

    preg_match('/viewBox="0 0 (\d+) (\d+)"/', $svg, $vb);
    $size = (int) $vb[1];

    $matrix = array_fill(0, $size, array_fill(0, $size, 0));

    foreach ($doc->getElementsByTagName('path') as $path) {
        if (! in_array('dark', explode(' ', $path->getAttribute('class')), true)) {
            continue;
        }

        preg_match_all('/M(-?\d+) (-?\d+) h1 v1 h-1Z/', $path->getAttribute('d'), $matches, PREG_SET_ORDER);

        foreach ($matches as $segment) {
            $x = (int) $segment[1];
            $y = (int) $segment[2];
            $matrix[$y][$x] = 1;
        }
    }

    return [$size, $matrix];
}

test('qr svg yang dihasilkan dapat didekode menjadi payload asli', function () {
    $payload = 'http://localhost:8000/petugas/absensi/scan/178994090.dda4043f803ce9dcedb8dc9238978381';

    [$size, $matrix] = qrSvgToMatrix(QrCode::encodeText($payload)->toSvg());

    expect($size)->toBeLessThanOrEqual(58);

    $scale = 8;
    $image = imagecreatetruecolor($size * $scale, $size * $scale);
    $white = imagecolorallocate($image, 255, 255, 255);
    $black = imagecolorallocate($image, 0, 0, 0);
    imagefill($image, 0, 0, $white);

    for ($y = 0; $y < $size; $y++) {
        for ($x = 0; $x < $size; $x++) {
            if ($matrix[$y][$x] === 1) {
                imagefilledrectangle($image, $x * $scale, $y * $scale, ($x + 1) * $scale - 1, ($y + 1) * $scale - 1, $black);
            }
        }
    }

    $result = (new Decoder(new QROptions))->decode(new GDLuminanceSource($image));

    expect($result->data)->toBe($payload);
});

test('svg berisi modul gelap dan tidak membocorkan tataran payload', function () {
    $payload = 'https://sppg.example.test/scan/token.abc';
    $svg = QrCode::encodeText($payload)->toSvg();

    expect($svg)->toContain('<svg', 'dark', 'fill="#000"', 'fill="#fff"')
        ->and(str_contains($svg, $payload))->toBeFalse();
});

test('kode terlalu panjang memicu RuntimeException', function () {
    QrCode::encodeText(str_repeat('a', 5000))->toSvg();
})->throws(RuntimeException::class, 'Konten terlalu panjang untuk QR code.');
