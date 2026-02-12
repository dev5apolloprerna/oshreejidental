<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <?php if (staff_can('create',  'branch')) { ?>
                <div class="tw-mb-2 sm:tw-mb-4">
                    <a href="<?php echo admin_url('branch/add'); ?>" class="btn btn-primary">
                        <i class="fa-regular fa-plus tw-mr-1"></i>
                        <?php echo _l('new_branch'); ?>
                    </a>
                </div>
                <?php } ?>
                <div class="panel_s">
                    <div class="panel-body panel-table-full">
                        <?php render_datatable([
                        // _l('image'),
                        _l('Branch Name'),
                        // _l('email'),
                        _l('phonenumber'),
                        _l('Address'),
                        // _l('active'),
                        _l('Created Date'),
                        ], 'branch'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
$(function() {
    initDataTable('.table-branch', window.location.href, [3], [3]);
    $('.table-branch').DataTable().on('draw', function() {
        var rows = $('.table-branch').find('tr');
        // $.each(rows, function() {
        //     var td = $(this).find('td').eq(6);
        //     var percent = $(td).find('input[name="percent"]').val();
        //     $(td).find('.branch-progress').circleProgress({
        //         value: percent,
        //         size: 45,
        //         animation: false,
        //         fill: {
        //             gradient: ["#28b8da", "#059DC1"]
        //         }
        //     })
        // })
    })
});
</script>
</body>

</html>