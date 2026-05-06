<?php defined('BASEPATH') or exit('No direct script access allowed');
init_head();
?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body modal-body">
                        <div class="row mtop5 mbot15">
                            <div class="col-md-8">
                                <h4 class="modal-title mtop0"><?= _l('appointment_create_label') . ' ' . _l('appointment_label'); ?></h4>
                                <p class="text-muted mbot0"><?= _l('appointment_create_cle'); ?> - <?= e($user->firstname . ' ' . $user->lastname); ?></p>
                            </div>
                            <div class="col-md-4 text-right">
                                <a href="<?= $return_url; ?>" class="btn btn-default close_btn"><?= _l('back'); ?></a>
                            </div>
                        </div>

                        <?php echo form_open('appointly/appointments/create', ['id' => 'patient-profile-appointment-form']); ?>
                        <input type="hidden" name="rel_type" value="internal">
                        <input type="hidden" name="contact_id" value="<?= (int) $user->id; ?>">

                        <?php echo render_input('subject', 'appointment_subject'); ?>
                        <?php echo render_textarea('description', 'appointment_description', '', ['rows' => 5]); ?>

                        <?php if (isset($staff)) : ?>
                            <div class="form-group">
                                <?php echo render_select('attendees[]', $staff, ['staffid', ['firstname', 'lastname']], 'appointment_select_attendees', [get_staff_user_id()], ['multiple' => true], [], '', '', false); ?>
                            </div>
                        <?php endif; ?>

                        <div class="row">
                            <div class="col-md-6">
                                <?php echo render_datetime_input('date', 'appointment_date_and_time', '', ['readonly' => 'readonly'], [], '', 'appointment-date'); ?>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="address"><?= _l('appointment_meeting_location') . ' ' . _l('appointment_optional'); ?></label>
                                    <input type="text" class="form-control" name="address" id="address">
                                </div>
                            </div>
                        </div>

                        <div class="form-group" id="div_name">
                            <label for="name"><?= _l('appointment_name'); ?></label>
                            <input type="text" value="<?= e($user->firstname . ' ' . $user->lastname); ?>" class="form-control" name="name" id="name" readonly>
                        </div>
                        <div class="form-group" id="div_email">
                            <label for="email"><?= _l('appointment_email'); ?></label>
                            <input type="email" value="<?= e($user->email); ?>" class="form-control" name="email" id="email" readonly>
                        </div>
                        <div class="form-group" id="div_phone">
                            <label for="phone"><?= _l('appointment_phone'); ?> (Ex: <?= _l('appointment_your_phone_example'); ?>)</label>
                            <input type="text" value="<?= e($user->phonenumber); ?>" class="form-control" name="phone" id="phone" readonly>
                        </div>

                        <?php $appointment_types = get_appointment_types(); ?>
                        <?php if (count($appointment_types) > 0) { ?>
                            <div class="form-group appointment_type_holder">
                                <label for="appointment_select_type" class="control-label"><?= _l('appointments_type_heading'); ?></label>
                                <select class="form-control" name="type_id" id="appointment_select_type">
                                    <option value=""><?= _l('dropdown_non_selected_tex'); ?></option>
                                    <?php foreach ($appointment_types as $app_type) { ?>
                                        <option class="form-control" data-color="<?= $app_type['color']; ?>" value="<?= $app_type['id']; ?>"><?= $app_type['type']; ?></option>
                                    <?php } ?>
                                </select>
                                <small id="appointment_color_type" class="pull-right appointment_color_type" style="background:#e1e6ec"></small>
                            </div>
                            <div class="clearfix mtop15"></div>
                            <hr>
                        <?php } ?>

                        <?php $this->load->view('view_includes/recurring_wrapper'); ?>

                        <?php
                        $rel_cf_id = false;
                        echo render_custom_fields('appointly', $rel_cf_id);
                        ?>

                        <div class="form-group mtop10">
                            <div class="row">
                                <div class="col-md-12 mbot5">
                                    <?= _l('appointment_modal_notification_info'); ?>
                                </div>
                                <div class="col-md-6">
                                    <div class="checkbox">
                                        <input type="checkbox" name="by_sms" id="by_sms">
                                        <label for="by_sms"><?= _l('appoontment_sms_notification'); ?></label>
                                    </div>
                                    <div class="checkbox">
                                        <input type="checkbox" name="by_email" id="by_email">
                                        <label for="by_email"><?= _l('appoontment_email_notification'); ?></label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group appointment-reminder hide">
                            <div class="row">
                                <div class="col-md-12">
                                    <label for="reminder_before"><?= _l('appointments_reminder_time_value'); ?></label><br>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <input type="number" class="form-control" name="reminder_before" value="" id="reminder_before">
                                        <span class="input-group-addon"><i class="fa fa-question-circle" data-toggle="tooltip" data-title="<?php echo _l('reminder_notification_placeholder'); ?>"></i></span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <select name="reminder_before_type" id="reminder_before_type" class="form-control">
                                        <option value="minutes"><?php echo _l('minutes'); ?></option>
                                        <option value="hours"><?php echo _l('hours'); ?></option>
                                        <option value="days"><?php echo _l('days'); ?></option>
                                        <option value="weeks"><?php echo _l('weeks'); ?></option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-info"><?php echo _l('submit'); ?></button>
                            <a href="<?= $return_url; ?>" class="btn btn-default mleft5"><?php echo _l('cancel'); ?></a>
                        </div>
                        <?php echo form_close(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
    $(function () {
        var appointly_please_wait = "<?= _l('appointment_please_wait'); ?>";
        var is_busy_times_enabled = "<?= get_option('appointly_busy_times_enabled'); ?>";
        var allowedLeadsHours = <?= json_encode(json_decode(get_option('appointly_available_hours'))); ?>;
        var appLeadsMinTime = <?= get_option('appointments_show_past_times'); ?>;
        var appLeadsWeekends = <?= (get_option('appointments_disable_weekends')) ? "[0, 6]" : "[]"; ?>;

        var todaysLeadsDate = new Date();
        var currentLeadDate = todaysLeadsDate.getFullYear() + "-" + (((todaysLeadsDate.getMonth() + 1) < 10) ? "0" : "") + (todaysLeadsDate.getMonth() + 1 + "-" + ((todaysLeadsDate.getDate() < 10) ? "0" : "") + todaysLeadsDate.getDate());

        init_selectpicker();
        initAppointmentScheduledDates();

        appValidateForm($('#patient-profile-appointment-form'), {
            subject: 'required',
            description: 'required',
            name: 'required',
            email: 'required',
            date: 'required',
            rel_type: 'required',
            'attendees[]': {
                required: true,
                minlength: 1
            }
        }, apply_appointments_form_data, {
            'attendees[]': 'Please select at least 1 staff member'
        });

        function apply_appointments_form_data(form) {
            $('#patient-profile-appointment-form button[type="submit"], .close_btn').prop('disabled', true);
            $('#patient-profile-appointment-form button[type="submit"]').html('<i class="fa fa-refresh fa-spin fa-fw"></i>');
            $('.panel-body').addClass('filterBlur');
            $('.modal-title').html(appointly_please_wait);

            var data = $(form).serialize();
            var url = form.action;

            $.post(url, data).done(function (response) {
                if (response.result) {
                    alert_float('success', "<?= _l('appointment_created'); ?>");
                    setTimeout(function () {
                        window.location.href = <?= json_encode($return_url); ?>;
                    }, 1000);
                }
            });
            return false;
        }

        function initAppointmentScheduledDates() {
            $.post(site_url + 'appointly/appointments_public/busyDates').done(function (r) {
                r = JSON.parse(r);
                var dateFormat = app.options.date_format;
                var appointmentDatePickerOptions = {
                    dayOfWeekStart: app.options.calendar_first_day,
                    minDate: 0,
                    format: dateFormat,
                    defaultTime: '09:00',
                    allowTimes: allowedLeadsHours,
                    closeOnDateSelect: 0,
                    closeOnTimeSelect: 1,
                    validateOnBlur: false,
                    minTime: appLeadsMinTime,
                    disabledWeekDays: appLeadsWeekends,
                    onGenerate: function (ct) {
                        if (is_busy_times_enabled == 1) {
                            var selectedGeneratedDate = ct.getFullYear() + '-' + (((ct.getMonth() + 1) < 10) ? '0' : '') + (ct.getMonth() + 1 + '-' + ((ct.getDate() < 10) ? '0' : '') + ct.getDate());
                            $(r).each(function (i, el) {
                                if (el.date == selectedGeneratedDate) {
                                    var currentTime = $('body').find('.xdsoft_time:contains("' + el.start_hour + '")');
                                    if (el.source == undefined) {
                                        currentTime.addClass('busy_google_time');
                                    } else {
                                        currentTime.addClass('busy_time');
                                    }
                                }
                            });
                        }
                    },
                    onSelectDate: function (ct) {
                        var selectedDate = ct.getFullYear() + '-' + (((ct.getMonth() + 1) < 10) ? '0' : '') + (ct.getMonth() + 1 + '-' + ((ct.getDate() < 10) ? '0' : '') + ct.getDate());
                        setTimeout(function () {
                            $('body').find('.xdsoft_time').removeClass('xdsoft_current xdsoft_today');
                            if (currentLeadDate !== selectedDate) {
                                $('body').find('.xdsoft_time.xdsoft_disabled').removeClass('xdsoft_disabled');
                            }
                        }, 200);
                    },
                    onChangeDateTime: function () {
                        $('body').find('.xdsoft_time').removeClass('busy_time');
                    }
                };

                if (app.options.time_format == 24) {
                    dateFormat = dateFormat + ' H:i';
                } else {
                    dateFormat = dateFormat + ' g:i A';
                    appointmentDatePickerOptions.formatTime = 'g:i A';
                }

                appointmentDatePickerOptions.format = dateFormat;
                $('.appointment-date').datetimepicker(appointmentDatePickerOptions);
            });
        }
    });
</script>
