<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="_buttons">
                            <?php if(is_admin()) { ?>
                            <a href="<?php echo admin_url('festival/festival/add'); ?>" class="btn btn-primary pull-left display-block"><?php echo _l('add_new_festival'); ?></a>
                            <div class="clearfix"></div>
                            <hr class="hr-panel-heading" />
                            <?php } else { echo '<h4 class="no-margin bold">'._l('offer').'</h4>';} ?>
                        </div>
                        <div class="clearfix"></div>
                        <?php render_datatable(array(
                        _l('Title'),     // Displaying title
                        _l('Date'),      // Displaying date
                        _l('Message'),   // Displaying message
                        ), 'holidays'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
    $(function(){
        initDataTable('.table-holidays', window.location.href);
    });
</script>
</body>
</html>
