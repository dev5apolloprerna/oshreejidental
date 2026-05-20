<?php defined('BASEPATH') or exit('No direct script access allowed');

use Mpdf\Mpdf;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;

/**
 * Create configured mPDF instance with English + Gujarati support.
 */
function mpdf_instance(array $opts = [])
{
    // Composer autoload
    $autoload = FCPATH . 'vendor/autoload.php';
    if (file_exists($autoload)) {
        require_once $autoload;
    } else {
        show_error('Composer autoload not found. Run: composer require mpdf/mpdf');
    }

    $defaultConfig = (new ConfigVariables())->getDefaults();
    $defaultFontConfig = (new FontVariables())->getDefaults();

    // Add custom font dir
    $fontDir = array_merge(
        $defaultConfig['fontDir'],
        [APPPATH . 'third_party/mpdf_fonts']
    );

    // Register Gujarati font family
    $fontData = $defaultFontConfig['fontdata'] + [
        'notosansgujarati' => [
            'R' => 'NotoSansGujarati-Regular.ttf',
            'B' => 'NotoSansGujarati-Bold.ttf',
        ],
    ];

    $config = [
        // Force UTF-8
        'mode' => 'utf-8',

        // A4 portrait default (change if needed)
        'format' => 'A4',

        // Use a font that exists for English too
        // For Gujarati we will set 'notosansgujarati' in CSS or via SetDefaultFont
        'default_font' => 'dejavusans',

        // Important for complex scripts
        'autoScriptToLang' => true,
        'autoLangToFont'   => true,

        // Font config
        'fontDir'  => $fontDir,
        'fontdata' => $fontData,

        // Temp dir (avoid permission issues)
        'tempDir' => APPPATH . 'cache/mpdf',
    ];

    // Ensure temp dir exists
    if (!is_dir($config['tempDir'])) {
        @mkdir($config['tempDir'], 0775, true);
    }

    // Merge user overrides
    $config = array_merge($config, $opts);

    return new Mpdf($config);
}

/**
 * Render a PDF from HTML + CSS.
 */
function mpdf_render_pdf($html, $css = '', $filename = 'document.pdf', $download = true, array $mpdfOpts = [])
{
    $mpdf = mpdf_instance($mpdfOpts);

    // Better defaults
    $mpdf->SetDisplayMode('fullpage');
    $mpdf->SetTitle($filename);

    // Write CSS first
    if ($css) {
        $mpdf->WriteHTML($css, \Mpdf\HTMLParserMode::HEADER_CSS);
    }

    // Then HTML
    $mpdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);

    // Output
    $dest = $download ? \Mpdf\Output\Destination::DOWNLOAD : \Mpdf\Output\Destination::INLINE;
    $mpdf->Output($filename, $dest);
}