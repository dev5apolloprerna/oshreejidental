<?php

defined('BASEPATH') or exit('No direct script access allowed');

$dimensions = $pdf->getPageDimensions();

$info_right_column = '';
$info_left_column  = '';

$info_right_column .= '<br /><br /><br /><br /><br /><br /><br />';

// $info_right_column .= '<span style="font-weight:bold;font-size:27px;">' . strtoupper(_l('prescription')) . '</span><br />';
// $info_right_column .= '<b style="color:#4e4e4e;">Appointment #' . $invoice->appointment_id . '</b>';



// Add logo
//$info_left_column .= pdf_logo_url();

// Write top left logo and right column info/text
pdf_multi_row($info_left_column, $info_right_column, $pdf, ($dimensions['wk'] / 2) - $dimensions['lm']);

$pdf->ln(10);

// $organization_info = '<div style="color:#424242;">';

// $organization_info .= format_organization_info();

// $organization_info .= '</div>';

$organization_info = '<b>For,</b><br /><br />';
$organization_info .= format_customer_info($invoice, 'invoice', 'billing');
$organization_info .= '<br/ ><b>Age : </b>'.(date('Y') - date('Y',strtotime($invoice->client->dob)));
$organization_info .= '<br/ ><b>Gender : </b>'.$invoice->client->gender;

// Bill to
// $invoice_info = '<b>' . _l('patient_details') . ':</b>';
// $invoice_info .= '<div style="color:#424242;">';
//     $invoice_info .= format_customer_info($invoice, 'invoice', 'billing');
// $invoice_info .= '</div>';


$invoice_info .= '<br />' . _l('RX') . ' : ' . _d(date('Y-m-d',strtotime($invoice->date))) . '<br />';

$invoice_info = hooks()->apply_filters('invoice_pdf_header_after_date', $invoice_info, $invoice);

if (!empty($invoice->duedate)) {
    $invoice_info .= _l('invoice_data_duedate') . ' ' . _d($invoice->duedate) . '<br />';
    $invoice_info = hooks()->apply_filters('invoice_pdf_header_after_due_date', $invoice_info, $invoice);
}

if ($invoice->sale_agent && get_option('show_sale_agent_on_invoices') == 1) {
    $invoice_info .= _l('sale_agent_string') . ': ' . get_staff_full_name($invoice->sale_agent) . '<br />';
    $invoice_info = hooks()->apply_filters('invoice_pdf_header_after_sale_agent', $invoice_info, $invoice);
}

if ($invoice->project_id && get_option('show_project_on_invoice') == 1) {
    $invoice_info .= _l('project') . ': ' . get_project_name_by_id($invoice->project_id) . '<br />';
    $invoice_info = hooks()->apply_filters('invoice_pdf_header_after_project_name', $invoice_info, $invoice);
}

$invoice_info = hooks()->apply_filters('invoice_pdf_header_before_custom_fields', $invoice_info, $invoice);

foreach ($pdf_custom_fields as $field) {
    $value = get_custom_field_value($invoice->id, $field['id'], 'invoice');
    if ($value == '') {
        continue;
    }
    $invoice_info .= $field['name'] . ': ' . $value . '<br />';
}

$invoice_info      = hooks()->apply_filters('invoice_pdf_header_after_custom_fields', $invoice_info, $invoice);
$organization_info = hooks()->apply_filters('invoicepdf_organization_info', $organization_info, $invoice);
$invoice_info      = hooks()->apply_filters('invoice_pdf_info', $invoice_info, $invoice);

$left_info  = $swap == '1' ? $invoice_info : $organization_info;
$right_info = $swap == '1' ? $organization_info : $invoice_info;

pdf_multi_row($left_info, $right_info, $pdf, ($dimensions['wk'] / 2) - $dimensions['lm']);

// The Table
$pdf->Ln(hooks()->apply_filters('pdf_info_and_table_separator', 6));

// The items table
$items = get_items_table_data_new($invoice, 'invoice', 'pdf');

$tblhtml = $items->table();

$pdf->writeHTML($tblhtml, true, false, false, false, '');

$pdf->Ln(8);



if (get_option('total_to_words_enabled') == 1) {
    // Set the font bold
    $pdf->SetFont($font_name, 'B', $font_size);
    $pdf->writeHTMLCell('', '', '', '', _l('num_word') . ': ' . $CI->numberword->convert($invoice->total, $invoice->currency_name), 0, 1, false, true, 'C', true);
    // Set the font again to normal like the rest of the pdf
    $pdf->SetFont($font_name, '', $font_size);
    $pdf->Ln(4);
}



if (!empty($invoice->note)) {
    $pdf->Ln(4);
    $pdf->SetFont($font_name, 'B', $font_size);
    $pdf->Cell(0, 0, _l('invoice_note'), 0, 1, 'L', 0, '', 0);
    $pdf->SetFont($font_name, '', $font_size);
    $pdf->Ln(2);
    $pdf->writeHTMLCell('', '', '', '', $invoice->note, 0, 1, false, true, 'L', true);
}

// if (!empty($invoice->terms)) {
//     $pdf->Ln(4);
//     $pdf->SetFont($font_name, 'B', $font_size);
//     $pdf->Cell(0, 0, _l('terms_and_conditions') . ':', 0, 1, 'L', 0, '', 0);
//     $pdf->SetFont($font_name, '', $font_size);
//     $pdf->Ln(2);
//     $pdf->writeHTMLCell('', '', '', '', $invoice->terms, 0, 1, false, true, 'L', true);
// }