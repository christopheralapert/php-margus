<?php

namespace App\Services\Leaflet;

use Endroid\QrCode\Color\Color;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;

final class QrCodeFactory
{
    public function dataUri(string $url): string
    {
        $qr = new QrCode(
            data: $url,
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: 400,
            margin: 0,
            roundBlockSizeMode: RoundBlockSizeMode::Enlarge,
            foregroundColor: new Color(31, 41, 55),
            backgroundColor: new Color(255, 255, 255),
        );

        // Keep exact brand ink: GD palette quantization changes RGB channels.
        return (new PngWriter)->write($qr, options: [PngWriter::WRITER_OPTION_NUMBER_OF_COLORS => null])->getDataUri();
    }
}
