<?php defined('BASEPATH') or exit('No direct script access allowed');
use Dompdf\Dompdf;
use Dompdf\Options;

class Pdf
{
    public function __construct()
    {
        // optional: get CI instance if you need later
        $this->CI = &get_instance();
    }
    public function html_to_pdf_binary($html)
    {
        // Try composer autoload (Perfex usually has it)
        if (!class_exists('\Dompdf\Dompdf')) {
            // fallback: require vendor autoload if available
            $autoload1 = FCPATH . 'vendor/autoload.php';
            $autoload2 = APPPATH . '../vendor/autoload.php';

            if (file_exists($autoload1)) {
                require_once $autoload1;
            } elseif (file_exists($autoload2)) {
                require_once $autoload2;
            }
        }

        if (!class_exists('\Dompdf\Dompdf')) {
            throw new Exception('DOMPDF not found. Install dompdf/dompdf via composer.');
        }

        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('fontDir', APPPATH . 'third_party/dompdf-fonts');
        $options->set('fontCache', APPPATH . 'third_party/dompdf-fonts');

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

public function gujarati_pdf($html)
    {
        
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('chroot', FCPATH);   // VERY IMPORTANT


        // IMPORTANT: This should be a Gujarati capable font, not DejaVu
        // We'll still keep DejaVu as fallback.
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);

        // ensure UTF-8
        $html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');

        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();



        return $dompdf->output();
    }
}