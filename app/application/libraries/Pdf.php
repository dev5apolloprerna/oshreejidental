<?php defined('BASEPATH') or exit('No direct script access allowed');

use Mpdf\Mpdf;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;

class Pdf
{
    /** @var Mpdf */
    public $mpdf;

    public function __construct($params = [])
    {
        // Composer autoload
        $autoload = APPPATH . 'vendor/autoload.php';
        if (!file_exists($autoload)) {
            show_error('Composer autoload not found. Run: composer require mpdf/mpdf');
        }
        require_once $autoload;

        $defaultConfig = (new ConfigVariables())->getDefaults();
        $defaultFontConfig = (new FontVariables())->getDefaults();

        $tempDir = APPPATH . 'cache/mpdf';
        if (!is_dir($tempDir)) @mkdir($tempDir, 0775, true);

        $fontDir = array_merge($defaultConfig['fontDir'], [
            APPPATH . 'third_party/mpdf_fonts',
        ]);

        $fontData = $defaultFontConfig['fontdata'] + [
            // Gujarati font
            'notosansgujarati' => [
                'R' => 'NotoSansGujarati-Regular.ttf',
                'B' => 'NotoSansGujarati-Bold.ttf',
            ],
        ];

        $config = [
            'mode' => 'utf-8',
            'format' => 'A4',
            'tempDir' => $tempDir,

            'fontDir' => $fontDir,
            'fontdata' => $fontData,

            // good defaults (auto pick fonts based on script)
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'default_font' => 'dejavusans',
        ];

        // merge user overrides
        if (is_array($params)) $config = array_merge($config, $params);

        $this->mpdf = new Mpdf($config);
        $this->mpdf->SetDisplayMode('fullpage');
    }

 public function html_to_pdf_binary(string $html, string $css = ''): string
    {
        // IMPORTANT: DO NOT strip tags, DO NOT escape html, DO NOT use strip_tags().
        // Just pass the raw HTML to mPDF.

        if ($css !== '') {
            $this->mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);
        }

        // ✅ Parse full HTML document (head + style + body)
        $this->mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::DEFAULT_MODE);

        

        return $this->mpdf->Output('', \Mpdf\Output\Destination::STRING_RETURN);
    }

    public function inline(string $binary, string $filename)
    {
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="'.$filename.'"');
        echo $binary;
        exit;
    }

    public function download(string $binary, string $filename)
    {
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        echo $binary;
        exit;
    }

    
}