<?php

namespace App\Services;

class PdfService
{
    public static function makeLandscape(string $html, string $filename = 'document.pdf'): void
    {
        $fontDir = APPPATH . 'ThirdParty/fonts';
        $mpdf = new \Mpdf\Mpdf([
            'mode'          => 'utf-8',
            'format'        => 'A4-L',
            'margin_top'    => 8,
            'margin_bottom' => 8,
            'margin_left'   => 10,
            'margin_right'  => 10,
            'fontDir'       => [$fontDir],
            'fontdata'      => ['latha' => ['R' => 'latha.ttf', 'useOTL' => 0xFF]],
            'default_font'  => 'latha',
        ]);
        $mpdf->WriteHTML($html);
        $mpdf->Output($filename, \Mpdf\Output\Destination::INLINE);
        exit;
    }

    public static function make(string $html, string $filename = 'document.pdf'): void
    {
        $fontDir = APPPATH . 'ThirdParty/fonts';
        $mpdf = new \Mpdf\Mpdf([
            'mode'         => 'utf-8',
            'format'       => 'A4',
            'margin_top'   => 12,
            'margin_bottom'=> 20,
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
        $mpdf->SetHTMLFooter('
            <table width="100%"><tr>
                <td style="text-align:center;font-size:10px;color:#555;font-family:latha;">
                    Page {PAGENO} of {nbpg}
                </td>
            </tr></table>
        ');
        $mpdf->WriteHTML($html);
        $mpdf->Output($filename, \Mpdf\Output\Destination::INLINE);
        exit;
    }

    public static function makeA5Portrait(string $html, string $filename = 'document.pdf'): void
    {
        $fontDir = APPPATH . 'ThirdParty/fonts';
        $mpdf = new \Mpdf\Mpdf([
            'mode'          => 'utf-8',
            'format'        => 'A5',
            'margin_top'    => 6,
            'margin_bottom' => 6,
            'margin_left'   => 8,
            'margin_right'  => 8,
            'fontDir'       => [$fontDir],
            'fontdata'      => ['latha' => ['R' => 'latha.ttf', 'useOTL' => 0xFF]],
            'default_font'  => 'latha',
        ]);
        $mpdf->WriteHTML($html);
        $mpdf->Output($filename, \Mpdf\Output\Destination::INLINE);
        exit;
    }
}
