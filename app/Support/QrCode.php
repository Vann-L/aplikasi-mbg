<?php

namespace App\Support;

use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Data\QRCodeDataException;
use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode as ChillerlanQRCode;
use chillerlan\QRCode\QROptions;
use RuntimeException;

/**
 * QR code generator for attendance tokens backed by chillerlan/php-qrcode
 * (byte mode, error correction level L, SVG output). Keeps the original
 * minimal API so controllers and views stay unchanged.
 */
class QrCode
{
    private function __construct(
        private readonly string $payload,
        private readonly QROptions $options,
    ) {}

    /**
     * Encode the given text as a QR code (byte mode, EC level L).
     */
    public static function encodeText(string $text): self
    {
        return new self($text, new QROptions([
            'outputType' => QRMarkupSVG::class,
            'eccLevel' => EccLevel::L,
            'addQuietzone' => true,
            'quietzoneSize' => 4,
            'outputBase64' => false,
            'drawLightModules' => true,
            'svgUseFillAttributes' => true,
        ]));
    }

    /**
     * Render the QR code as SVG markup with a quiet zone around it.
     */
    public function toSvg(int $moduleSize = 4, int $quietZone = 4): string
    {
        if ($quietZone < 0) {
            throw new RuntimeException('Quiet zone tidak boleh negatif.');
        }

        if ($quietZone === 0) {
            $options = clone $this->options;
            $options->addQuietzone = false;
            $options->quietzoneSize = 4;
        } else {
            $options = clone $this->options;
            $options->quietzoneSize = $quietZone;
        }

        try {
            return (new ChillerlanQRCode($options))->render($this->payload);
        } catch (QRCodeDataException) {
            throw new RuntimeException('Konten terlalu panjang untuk QR code.');
        }
    }
}
