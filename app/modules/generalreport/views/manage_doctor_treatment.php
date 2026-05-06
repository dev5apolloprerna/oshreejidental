<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>

<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <?php if (is_admin()) { ?>
                            <div class="clearfix" style="color: #415165;"><?php echo '<h3 class="no-margin bold">' . _l('General Report') . '</h3>'; ?></div>
                            <hr class="hr-panel-heading" />
                        <?php } ?>

                        <form id="doctor_treatment_report_form" method="get" action="">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="select-placeholder">
                                        <label><?php echo _l('filter_by_g'); ?></label>
                                        <select name="repo_type" id="repo_type" class="selectpicker" data-width="100%" data-live-search="true">
                                            <option value="appointments">Appointments</option>
                                            <option value="leads">Leads</option>
                                            <option value="patients">Patients</option>
                                            <option value="task">Task</option>
                                            <option value="lab_work">Lab Work</option>
                                            <option value="payment_receipts">Payment Receipts</option>
                                            <option value="doctor_treatment" selected>Doctor Treatment Report</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <?php echo render_date_input('start_date', 'from_date', $this->input->get('start_date')); ?>
                                </div>

                                <div class="col-md-3">
                                    <?php echo render_date_input('end_date', 'to_date', $this->input->get('end_date')); ?>
                                </div>

                                <div class="col-md-3">
                                    <div class="select-placeholder">
                                        <label><?php echo _l('doctor'); ?></label>
                                        <select name="staff_id" id="staff_id" class="selectpicker" data-width="100%" data-live-search="true">
                                            <option value=""></option>
                                            <?php foreach ($staff as $stf) {
                                                $selected = ($stf['staffid'] == $this->input->get('staff_id')) ? 'selected' : '';
                                                echo '<option value="' . $stf['staffid'] . '" ' . $selected . '>' . $stf['firstname'] . ' ' . $stf['lastname'] . '</option>';
                                            } ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div style="margin: 15px 0 70px;">
                                <button type="submit" class="btn btn-primary p7 pull-left" style="margin-right: 10px;"><?php echo _l('apply'); ?></button>
                                <a href="<?php echo admin_url('generalreport?repo_type=doctor_treatment'); ?>" class="btn btn-primary p7 pull-left"><?php echo _l('reset'); ?></a>
                            </div>
                        </form>

                        <hr class="hr-panel-heading" />

                        <?php
                        $table_data = [
                            _l('id'),
                            _l('client'),
                            _l('doctor'),
                            _l('date'),
                            _l('treatment_details'),
                            _l('appointment_description'),
                        ];
                        render_datatable($table_data, 'service_details');
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
        initDataTable('.table-service_details', window.location.href, [], [0]);
    });
</script>
