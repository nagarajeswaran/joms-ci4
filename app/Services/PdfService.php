<?php

namespace App\Services;

class PdfService
{
    public static function make(string $html, string $filename = 'document.pdf'): void
    {
        $fontDir = APPPATH . 'ThirdParty/fonts';
        $mpdf = new \Mpdf\Mpdf([
            'mode'         => 'utf-8',
            'format'       => 'A4',
            'margin_top'   => 12,
            'margin_bottom'=> 10,
            'margin_left'  => 12,
            'margin_right' => 12,
            'fontDir'      => [$fontDir],
            'fontdata'     => [
                'latha' => [
                    'R'      => 'latha.ttf',
                    'useOTL' => 0xFF,
                ],
            ],
            'default_font' => 'latha',
        ]);
        $mpdf->WriteHTML($html);
        $mpdf->Output($filename, \Mpdf\Output\Destination::INLINE);
        exit;
    }
}
