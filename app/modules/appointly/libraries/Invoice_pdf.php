<?php

defined('BASEPATH') or exit('No direct script access allowed');

include_once(__DIR__ . '/App_pdf.php');

class Invoice_pdf extends App_pdf
{
    protected $invoice;

    private $invoice_number;

    public function __construct($invoice, $tag = '')
    {
        $this->load_language($invoice->clientid);
        $invoice                = hooks()->apply_filters('invoice_html_pdf_data', $invoice);
        $GLOBALS['invoice_pdf'] = $invoice;

        parent::__construct();

        if (!class_exists('Invoices_model', false)) {
            $this->ci->load->model('invoices_model');
        }

        $this->tag            = $tag;
        $this->invoice        = $invoice;
        $this->invoice_number = $this->invoice->id;

        $this->SetTitle($this->invoice_number);
    }

    public function prepare()
    {
        $this->with_number_to_word($this->invoice->clientid);

        $this->set_view_vars([
            'status'         => $this->invoice->status,
            'invoice_number' => $this->invoice_number,
            'invoice'        => $this->invoice,
        ]);

        return $this->build();
    }

    protected function type()
    {
        return 'invoice';
    }

    protected function file_path()
    {
        $actualPath = FCPATH .'modules/'.APPOINTLY_MODULE_NAME . '/views/invoicepdf.php';

    
        return $actualPath;
    }

    
}
