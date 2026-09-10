<?php

namespace App\Services;

use App\Models\TrackedLink;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Str;
use RuntimeException;

class TrackedLinkQrCodeService
{
    public function png(TrackedLink $link, int $size = 480): string
    {
        return Builder::create()
            ->writer(new PngWriter())
            ->data($link->short_url)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::High)
            ->size($size)
            ->margin(16)
            ->roundBlockSizeMode(RoundBlockSizeMode::Margin)
            ->build()
            ->getString();
    }

    public function pdf(TrackedLink $link): string
    {
        return $this->wrapPngInPdf(
            $this->png($link, 640),
            $link->nom,
            $link->short_url
        );
    }

    public function downloadBasename(TrackedLink $link): string
    {
        $base = Str::slug($link->nom) ?: $link->slug;

        return 'qr-'.$base;
    }

    private function wrapPngInPdf(string $png, string $title, string $url): string
    {
        $jpeg = $this->pngToJpeg($png);
        $size = @getimagesizefromstring($jpeg);
        if ($size === false) {
            throw new RuntimeException('Impossible de lire le QR code pour le PDF.');
        }
        [$imgW, $imgH] = $size;

        $pageW = 595;
        $pageH = 842;
        $qrPts = 280;
        $qrX = ($pageW - $qrPts) / 2;
        $qrY = 390;

        $titlePdf = $this->pdfEscape($this->toWinAnsi($title));
        $urlPdf = $this->pdfEscape($this->toWinAnsi($url));

        $content = "q\n"
            .number_format($qrPts, 2, '.', '')." 0 0 ".number_format($qrPts, 2, '.', '').' '
            .number_format($qrX, 2, '.', '').' '.number_format($qrY, 2, '.', '')." cm\n"
            ."/Im0 Do\nQ\n"
            ."BT\n/F1 16 Tf\n50 340 Td\n(".$titlePdf.") Tj\n"
            ."/F1 10 Tf\n0 -18 Td\n(".$urlPdf.") Tj\nET\n";

        $objects = [];
        $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[] = '<< /Type /Pages /Kids [3 0 R] /Count 1 >>';
        $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 '.$pageW.' '.$pageH.'] '
            .'/Resources << /XObject << /Im0 4 0 R >> /Font << /F1 5 0 R >> >> /Contents 6 0 R >>';
        $objects[] = '<< /Type /XObject /Subtype /Image /Width '.$imgW.' /Height '.$imgH
            .' /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length '.strlen($jpeg).' >>'
            ."\nstream\n".$jpeg."\nendstream";
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
        $objects[] = '<< /Length '.strlen($content)." >>\nstream\n".$content.'endstream';

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $i => $body) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1)." 0 obj\n".$body."\nendobj\n";
        }

        $xrefPos = strlen($pdf);
        $pdf .= 'xref'."\n".'0 '.(count($objects) + 1)."\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= 'trailer << /Size '.(count($objects) + 1).' /Root 1 0 R >>'."\n";
        $pdf .= 'startxref'."\n".$xrefPos."\n%%EOF";

        return $pdf;
    }

    private function pngToJpeg(string $png): string
    {
        $image = @imagecreatefromstring($png);
        if ($image === false) {
            throw new RuntimeException('Impossible de générer le QR code JPEG.');
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $canvas = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);
        imagecopy($canvas, $image, 0, 0, 0, 0, $width, $height);

        ob_start();
        imagejpeg($canvas, null, 92);
        $jpeg = (string) ob_get_clean();
        imagedestroy($image);
        imagedestroy($canvas);

        return $jpeg;
    }

    private function toWinAnsi(string $text): string
    {
        $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text);

        return $converted !== false ? $converted : preg_replace('/[^\x20-\x7E]/', '?', $text) ?? $text;
    }

    private function pdfEscape(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }
}
