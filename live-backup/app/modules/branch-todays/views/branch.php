<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin"><?php echo $title; ?></h4>
                        <hr class="hr-panel-heading" />
                        <?php echo form_open_multipart($this->uri->uri_string(), array('autocomplete'=>'off') ); ?>
                        <?php $attrs = (isset($branch) ? array() : array('autofocus'=>true)); ?>
                        
                        
                        <?php $value = (isset($branch) ? $branch->branch : '');  ?>
                        
                        <?php echo render_input('branch','branch',$value,'text',$attrs); ?>

                        <?php $value = (isset($branch) ? $branch->email : '');  ?>
                        <?php echo render_input('email','email',$value,'text',$attrs); ?>

                        <?php if (!isset($branch) || is_admin() || !is_admin() && $branch->admin == 0) { ?>
                                <!-- fake fields are a workaround for chrome autofill getting the wrong fields -->
                                <!-- <input type="text" class="fake-autofill-field" name="fakeusernameremembered" value=''
                                    tabindex="-1" /> -->
                                <!-- <input type="password" class="fake-autofill-field" name="fakepasswordremembered"
                                    value='' tabindex="-1" /> -->
                                <div class="clearfix form-group"></div>
                                <label for="password"
                                    class="control-label"><?php echo _l('staff_add_edit_password'); ?></label>
                                <div class="input-group">
                                    <input type="password" class="form-control password" name="password"
                                        autocomplete="off">
                                    <span class="input-group-addon tw-border-l-0">
                                        <a href="#password" class="show_password"
                                            onclick="showPassword('password'); return false;"><i
                                                class="fa fa-eye"></i></a>
                                    </span>
                                    <span class="input-group-addon">
                                        <a href="#" class="generate_password"
                                            onclick="generatePassword(this);return false;"><i
                                                class="fa fa-refresh"></i></a>
                                    </span>
                                </div>
                                <?php if (isset($branch)) { ?>
                                <p class="text-muted tw-mt-2"><?php echo _l('staff_add_edit_password_note'); ?></p>
                                <?php if ($branch->last_password_change != null) { ?>
                                <?php echo _l('staff_add_edit_password_last_changed'); ?>:
                                <span class="text-has-action" data-toggle="tooltip"
                                    data-title="<?php echo e(_dt($branch->last_password_change)); ?>">
                                    <?php echo e(time_ago($branch->last_password_change)); ?>
                                </span>
                                <?php } } ?>
                                <?php } ?>
                                              
                        <?php if(isset($branch)){ ?>
                        <img src="<?php echo branch_icon_image_url($branch->branchid,'IMG'); ?>" id="visitorspurpose-img" class="service-image-thumb">
                        <?php if(!empty($branch->image)){ ?>
                            <a href="<?php echo admin_url('branch/delete_branch_icon_image/'.$branch->branchid); ?>" class="text-danger"><i class="fa fa-remove"></i></a>
                        <?php } ?>
                        <hr />
                        <?php } ?>

                        <div class="form-group<?php if(isset($branch) && !empty($branch->icon)){echo ' hide';} ?>" id="visitorspurpose-profile-image">
                            <label for="icon" class="profile-image"><?php echo _l('icon'); ?></label>
                            <input type="file" name="image" class="form-control" id="icon">
                        </div>


                        <?php $value = (isset($branch) ? $branch->vat : '');  ?>
                        <?php echo render_input('vat','vat',$value,'text',$attrs); ?>
                       
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
                        <div class="form-group">
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
                        </div>
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
                        echo render_select('country', $countries, [ 'country_id', [ 'short_name']], 'branchs_country', $selected, ['data-none-selected-text' => _l('dropdown_non_selected_tex')]);
                        ?>
                      <div class="btn-bottom-toolbar text-right">
                            <button type="submit" class="btn btn-primary"><?php echo _l('submit'); ?></button>
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
    $(function(){
       appValidateForm($('form'), {
        branch: 'required',
        });
    });
    </script>
</body>
</html>

 