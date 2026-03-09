<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="tw-mt-0 tw-font-semibold"><?php echo e($title); ?></h4>
                        <p class="text-muted">Search by <strong>Patient ID</strong> or <strong>Mobile Number</strong> to get patient list from all 4 branch databases (from each branch <code>tblcontacts</code>/<code>tblclients</code>). Pagination is enabled for large result sets.</p>
                        <?php
                        $table_data = [
                            _l('the_number_sign'),
                            'Patient ID',
                            'Branch',
                            _l('clients_list_company'),
                            _l('contact_primary'),
                            _l('company_primary_email'),
                            _l('clients_list_phone'),
                            _l('date_created'),
                        ];
                        render_datatable($table_data, 'global-patient-search', ['number-index-2'], [
                            'id' => 'global-patient-search',
                        ]);
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
$(function() {
    var patientSearchTable = initDataTable('.table-global-patient-search', admin_url + 'clients/global_search_table', [0], [0], {}, [7, 'desc']);

    if (patientSearchTable) {
        patientSearchTable.page.len(25).draw(false);
    }
});
</script>
</body>
</html>
