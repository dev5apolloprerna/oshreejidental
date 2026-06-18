<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
 * The general report pages use server-side DataTables POST requests only to
 * read filtered report rows. In some deployments the CSRF cookie can be
 * rotated before DataTables sends its POST, which makes the initial report load
 * fail with a 419 "Page Expired" response. Excluding this read-only endpoint
 * keeps the report tables loadable while controller permissions still protect
 * access to the report data.
 */
return [
    'admin/generalreport',
];
