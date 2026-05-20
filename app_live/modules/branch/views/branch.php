<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-<?php echo !isset($branch) ? '12' : 12; ?>">
                <h4 class="tw-mt-0 tw-font-semibold tw-text-lg tw-text-neutral-700"><?php echo e($title); ?></h4>
                <?php echo form_open_multipart($this->uri->uri_string(), array('autocomplete'=>'off') ); ?>
                <?php $attrs = (isset($branch) ? array() : array('autofocus'=>true)); ?>
                <div class="panel_s">
                    <div class="panel-body">
                    <?php $value = (isset($branch) ? $branch->branch_db : '');  ?>
                    <?php echo render_input('branch_db','',$value,'hidden',$attrs); ?>
                    <?php
                    $selected = (isset($branch) ? $branch->staff_id : '');
                    echo render_select('staff_id', $members, ['staffid', ['firstname', 'lastname']], 'assign_staff_branch', 
                    $selected, ['data-none-selected-text' => _l('all_staff_members')]);   
                    ?>

                    <?php $value = (isset($branch) ? $branch->branch : '');  ?>
                        
                        <?php echo render_input('branch','branch',$value,'text',$attrs); ?>

                        <?php echo render_input('branch_code','Branch Code',$value,'text',$attrs); ?>

                        <?php $value = (isset($branch) ? $branch->email : '');  ?>
                        <!-- <?php echo render_input('email','email',$value,'text',$attrs); ?> -->

                        


                       <!--  <?php $value = (isset($branch) ? $branch->vat : '');  ?>
                        <?php echo render_input('vat','vat',$value,'text',$attrs); ?> -->
                       
                        <?php hooks()->do_action('before_customer_profile_branch_field', $branch ?? null); ?>
                        <?php $value = (isset($branch) ? $branch->branch : ''); ?>
                        <?php $attrs = (isset($branch) ? [] : ['autofocus' => true]); ?>
                        <div id="branch_exists_info" class="hide"></div>
                        <?php hooks()->do_action('after_customer_profile_branch_field', $branch ?? null); ?>
                        <?php if (get_option('branch_requires_vat_number_field') == 1) {
                            $value = (isset($branch) ? $branch->vat : '');
                            echo render_input('vat', 'branch_vat_number', $value);
                        } ?>

                        <?php hooks()->do_action('before_customer_profile_phone_field', $branch ?? null); ?>
                        <?php $value = (isset($branch) ? $branch->phonenumber : ''); ?>
                        <?php echo render_input('phonenumber', 'branch_phonenumber', $value); ?>

                        <?php hooks()->do_action('after_customer_profile_branch_phone', $branch ?? null); ?>
                        <?php if ((isset($branch) && empty($branch->website)) || !isset($branch)) 
                        {
                            $value = (isset($branch) ? $branch->website : '');
                            // echo render_input('website', 'branch_website', $value);
                        ?>
                       <!--  <div class="form-group">
                            <label for="website"><?php echo _l('branch_website'); ?></label>
                            <div class="input-group">
                                <input type="text" name="website" id="website" value="<?php echo e($value); ?>"
                                    class="form-control">
                                <span class="input-group-btn">
                                    <a href="<?php echo e(maybe_add_http($value)); ?>" class="btn btn-default"
                                        target="_blank" tabindex="-1">
                                        <i class="fa fa-globe"></i></a>
                                </span>

                            </div>
                        </div> -->
                        <?php } ?>
                      
                        <hr />



                        <?php $value = (isset($branch) ? $branch->address : ''); ?>
                        <?php echo render_textarea('address', 'branch_address', $value); ?>

                        <?php $value = (isset($branch) ? $branch->city : ''); ?>
                        <?php echo render_input('city', 'branch_city', $value); ?>

                        <?php $value = (isset($branch) ? $branch->state : ''); ?>
                        <?php echo render_input('state', 'branch_state', $value); ?>

                        <?php $value = (isset($branch) ? $branch->zip : ''); ?>
                        <?php echo render_input('zip', 'branch_postal_code', $value); ?>

                        <?php 
                        $countries       = get_all_countries();
                        $customer_default_country = get_option('customer_default_country');
                        $selected                 = (isset($branch) ? $branch->country : $customer_default_country);
                        // echo render_select('country', $countries, [ 'country_id', [ 'short_name']], 'branchs_country', $selected, ['data-none-selected-text' => _l('dropdown_non_selected_tex')]);
                        ?>
                    </div>
                    <div class="panel-footer text-right">
                        <button type="submit" class="btn btn-primary "><?php echo _l('submit'); ?></button>
                    </div>
                </div>
                <?php echo form_close(); ?>
            </div>
         
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
$(function() {
    appValidateForm($('form'), {
        staff_id: 'required',
        branch: 'required',
        branch_code: 'required',
        address: 'required',
        city: 'required',
        state: 'required',
        // email: 'required',
        // vat: 'required',
        phonenumber: 'required',
    });

    <?php if (isset($branch)) { ?>
        appValidateForm($('form'), {
        staff_id: 'required',
        branch: 'required',
         address: 'required',
        city: 'required',
        state: 'required',
        // email: 'required',
        // vat: 'required',
        phonenumber: 'required',
    });
<?php } ?>
   
    var branch_type = $('select[name="branch_type"]').val();
    if (branch_type == 5 || branch_type == 7) {
        $('#contract_types').removeClass('hide');
    }
    $('select[name="branch_type"]').on('change', function() {
        var branch_type = $(this).val();
        if (branch_type == 5 || branch_type == 7) {
            $('#contract_types').removeClass('hide');
        } else {
            $('#contract_types').addClass('hide');
            $('#contract_type').selectpicker('val', '');
        }
    });
});
</script>
</body>

</html>