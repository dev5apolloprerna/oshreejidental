<?php defined('BASEPATH') or exit('No direct script access allowed');

class Pdf
{
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

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }
}