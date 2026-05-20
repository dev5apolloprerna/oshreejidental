
<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php if (isset($client)) { ?>
<h4 class="customer-profile-group-heading"><?php echo _l('client_add_edit_profile'); ?></h4>
<?php } ?>
<?php if (isset($client)) { ?>
<h4 class="customer-profile-group-heading"><?php echo _l('client_add_edit_profile'); ?></h4>
<?php } ?>
<?php
$contact = isset($contact) ? $contact : null;
$medical_history = isset($medical_history) ? $medical_history : null;
?>

<div class="row">
    <?php echo form_open_multipart($this->uri->uri_string(), ['class' => 'client-form','id' => 'patint_form', 'autocomplete' => 'off']); ?>
    <!-- <?php echo validation_errors(); ?> -->
    <div class="additional"></div>
    <div class="col-md-12">
        <div class="horizontal-scrollable-tabs panel-full-width-tabs">
            <div class="scroller arrow-left"><i class="fa fa-angle-left"></i></div>
            <div class="scroller arrow-right"><i class="fa fa-angle-right"></i></div>
            <div class="horizontal-tabs">
                <ul class="nav nav-tabs customer-profile-tabs nav-tabs-horizontal" role="tablist">
                    <li role="presentation" class="<?php echo !$this->input->get('tab') ? 'active' : ''; ?>">
                        <a href="#contact_info" aria-controls="contact_info" role="tab" data-toggle="tab">
                            <?php echo _l('customer_profile_details'); ?>
                        </a>
                    </li>
                    <?php
                  $customer_custom_fields = false;
                  if (total_rows(db_prefix() . 'customfields', ['fieldto' => 'customers', 'active' => 1]) > 0) {
                      $customer_custom_fields = true; ?>
                    <li role="presentation" class="<?php if ($this->input->get('tab') == 'custom_fields') {
                          echo 'active';
                      }; ?>">
                        <a href="#custom_fields" aria-controls="custom_fields" role="tab" data-toggle="tab">
                            <?php echo hooks()->apply_filters('customer_profile_tab_custom_fields_text', _l('custom_fields')); ?>
                        </a>
                    </li>
                    <?php
                  } ?>
                    <li role="presentation">
                        <a href="#billing_and_shipping" aria-controls="billing_and_shipping" role="tab"
                            data-toggle="tab">
                            <?php echo _l('billing_shipping'); ?>
                        </a>
                    </li>
                    <li role="presentation">
                        <a href="#medical_history" aria-controls="medical_history" role="tab"
                            data-toggle="tab">
                            <?php echo _l('Medical History'); ?>
                        </a>
                    </li>
                    <?php hooks()->do_action('after_customer_billing_and_shipping_tab', isset($client) ? $client : false); ?>
                    <?php if (isset($client)) { ?>
                    <li role="presentation">
                        <a href="#customer_admins" aria-controls="customer_admins" role="tab" data-toggle="tab">
                            <?php echo _l('customer_admins'); ?>
                            <?php if (count($customer_admins) > 0) { ?>
                            <span class="badge bg-default"><?php echo count($customer_admins) ?></span>
                            <?php } ?>
                        </a>
                    </li>
                    <?php hooks()->do_action('after_customer_admins_tab', $client); ?>
                    <?php } ?>
                </ul>
            </div>
        </div>
        <div class="tab-content mtop15">
            <?php hooks()->do_action('after_custom_profile_tab_content', isset($client) ? $client : false); ?>
            <?php if ($customer_custom_fields) { ?>
            <div role="tabpanel" class="tab-pane <?php if ($this->input->get('tab') == 'custom_fields') {
                      echo ' active';
                  }; ?>" id="custom_fields">
                <?php $rel_id = (isset($client) ? $client->userid : false); ?>
                <?php echo render_custom_fields('customers', $rel_id); ?>
            </div>
            <?php } ?>
            <div role="tabpanel" class="tab-pane<?php if (!$this->input->get('tab')) {
                      echo ' active';
                  }; ?>" id="contact_info">
                <div class="row">
                    <div class="col-md-12 <?php if (isset($client) && (!is_empty_customer_company($client->userid) && total_rows(db_prefix() . 'contacts', ['userid' => $client->userid, 'is_primary' => 1]) > 0)) {
                      echo '';
                  } else {
                      echo ' hide';
                  } ?>" id="client-show-primary-contact-wrapper">
                       <!--  <div class="checkbox checkbox-info mbot20 no-mtop">
                            <input type="checkbox" name="show_primary_contact" <?php if (isset($client) && $client->show_primary_contact == 1) {
                      echo ' checked';
                  }?> value="1" id="show_primary_contact">
                            <label
                                for="show_primary_contact"><?php echo _l('show_primary_contact', _l('invoices') . ', ' . _l('estimates') . ', ' . _l('payments') . ', ' . _l('credit_notes')); ?></label>
                        </div> -->
                    </div>
                    <div class="col-md-<?php echo !isset($client) ? 12 : 12; ?>">
                        <?php hooks()->do_action('before_customer_profile_company_field', $client ?? null); ?>
                        <!-- <?php $value = (isset($client) ? $client->company : ''); ?>
                        <?php $attrs = (isset($client) ? [] : ['autofocus' => true]); ?>
                        <?php 
                        echo render_input('company', 'client_company', $value, 'text', $attrs); 
                        ?> -->
                        <div id="company_exists_info" class="hide"></div>
                        <!-- <?php hooks()->do_action('after_customer_profile_company_field', $client ?? null); ?>
                        <?php if (get_option('company_requires_vat_number_field') == 1) {
                        $value = (isset($client) ? $client->vat : '');
                          echo render_input('vat', 'client_vat_number', $value);
                        } ?> -->
                        <!-- <?php hooks()->do_action('before_customer_profile_phone_field', $client ?? null); ?>
                        <?php $value = (isset($client) ? $client->phonenumber : ''); ?>
                        <?php  
                        echo render_input('phonenumber', 'client_phonenumber', $value); 
                        ?> -->
                        <?php hooks()->do_action('after_customer_profile_company_phone', $client ?? null); ?>
                        <?php if ((isset($client) && empty($client->website)) || !isset($client)) {
                            
                      $value = (isset($client) ? $client->website : '');
                        // echo  render_input('website', 'client_website', $value);
                        } else { ?>
                        <!-- <div class="form-group">
                            <label for="website"><?php echo _l('client_website'); ?></label>
                            <div class="input-group">
                                <input type="text" name="website" id="website" value="<?php echo e($client->website); ?>"
                                    class="form-control">
                                <span class="input-group-btn">
                                    <a href="<?php echo e(maybe_add_http($client->website)); ?>" class="btn btn-default"
                                        target="_blank" tabindex="-1">
                                        <i class="fa fa-globe"></i></a>
                                </span>
                            </div>
                        </div> -->
                        <?php }
                    
                     $selected = [];
                     if (isset($customer_groups)) {
                         foreach ($customer_groups as $group) {
                             array_push($selected, $group['groupid']);
                         }
                     }
                     if (is_admin() || get_option('staff_members_create_inline_customer_groups') == '1') {
                        //  echo render_select_with_input_group('groups_in[]', $groups, ['id', 'name'], 'customer_groups', $selected, '<div class="input-group-btn"><a href="#" class="btn btn-default" data-toggle="modal" data-target="#customer_group_modal"><i class="fa fa-plus"></i></a></div>', ['multiple' => true, 'data-actions-box' => true], [], '', '', false);
                     } else {
                         echo render_select('groups_in[]', $groups, ['id', 'name'], 'customer_groups', $selected, ['multiple' => true, 'data-actions-box' => true], [], '', '', false);
                     }
                     ?>
                        <div class="row">
                            <div class="col-md-<?php echo !is_language_disabled() ? 6 : 12; ?>">
                                <!-- <i class="fa-regular fa-circle-question pull-left tw-mt-0.5 tw-mr-1"
                                    data-toggle="tooltip"
                                    data-title="<?php echo _l('customer_currency_change_notice'); ?>"></i> -->
                                <?php
                     $s_attrs  = ['data-none-selected-text' => _l('system_default_string')];
                     $selected = '';
                     if (isset($client) && client_have_transactions($client->userid)) {
                         $s_attrs['disabled'] = true;
                     }
                     $currencies = [];

                     foreach ($currencies as $currency) {
                         if (isset($client)) {
                             if ($currency['id'] == $client->default_currency) {
                                 $selected = $currency['id'];
                             }
                         }
                     }
                            // Do not remove the currency field from the customer profile!
                        //  echo render_select('default_currency', $currencies, ['id', 'name', 'symbol'], 'invoice_add_edit_currency', $selected, $s_attrs);
                     ?>
                            </div>
                            <?php if (!is_language_disabled()) { ?>
                            <div class="col-md-6">

                                <div class="form-group select-placeholder" id="patient-language" style="display:none">
                                    <label for="default_language"
                                        class="control-label">
                                        <?php echo _l('localization_default_language'); ?>
                                    </label>
                                    <select name="default_language" id="default_language"
                                        class="form-control selectpicker"
                                        data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                                        <option value=""><?php echo _l('system_default_string'); ?></option>
                                        <?php 
                                        foreach ($this->app->get_available_languages() as $availableLanguage) {
                                            $selected = '';
                                                if (isset($client)) {
                                                    if ($client->default_language == $availableLanguage) {
                                                    $selected = 'selected';
                                                }
                                        }
                                         ?>
                                        <option value="<?php echo e($availableLanguage); ?>" <?php echo e($selected); ?>>
                                            <?php echo e(ucfirst($availableLanguage)); ?></option>
                                        <?php
                                        } ?>
                                    </select>
                                </div>
                            </div>
                            <?php } ?>
                    <div class="row patient_reg">
                        <div class="col-lg-6">
                            <div class="patient_firstname">                   
                                <?php if (isset($contact) && property_exists($contact, 'firstname')) {
                                    $value = $contact->firstname;
                                } 
                                echo render_input('firstname', '<span class="req text-danger">*</span> First Name', $value);?>
                                <div class="text-danger">
                                    <?php echo form_error('firstname');?>
                                </div>
                            </div>
                            <div class="patient_gender">
                                <label for="gender" class="control-label"><span class="req text-danger">*</span> Gender</label>
                                <div class="dropdown bootstrap-select bs3" style="width: 100%;">
                                    <select class="selectpicker form-group" data-none-selected-text="" data-width=" 100%" name="gender" id="gender" tabindex="-98">
                                        <option disabled selected value>Nothing selected</option>
                                        
                                        <option value="Male" <?php echo ($contact && $contact->gender == 'Male') ? '    selected' : '' ?>>Male</option>
                                        <option value="Female" <?php echo ($contact && $contact->gender == 'Female')    ? 'selected' : '' ?>>Female</option>
                                        <option value="Other" <?php echo ($contact && $contact->gender == 'Other') ?    'selected' : '' ?>>Other</option>
                                        </select>
                                        <div class="text-danger">
                                            <?php echo form_error('gender');?>
                                        </div>
                                </div>
                            </div>
                            <div class="patient_email">
                                <?php if (isset($contact) && property_exists($contact, 'email')) {
                                    $value = $contact->email;
                                } 
                                echo render_input('email', '<span class="req text-danger">*</span> Email', $value,true);?>
                                <div class="text-danger">
                                    <?php echo form_error('email');?>
                                </div>
                            </div>
                            <div class="blood-group contact-direction-option">
                            <label for="blood-group">Blood Group</label>
                            <div class="dropdown bootstrap-select bs3 form-group" style="width: 100%;">
                            <select class="selectpicker" data-none-selected-text="" data-width="100%" name="blood_group" id="blood-group">
                                    
                                    <option disabled selected value>Nothing selected</option>
                                    <option value="A Positive" <?php if($contact && $contact->blood_group == "A Positive") echo "selected"; ?>>A Positive</option>
                                    <option value="A Negative" <?php if($contact && $contact->blood_group == "A Negative") echo "selected"; ?>>A Negative</option>
                                    <option value="AB Positive" <?php if($contact && $contact->blood_group == "AB Positive") echo "selected"; ?>>AB Positive</option>
                                    <option value="AB Negative" <?php if($contact && $contact->blood_group == "AB Negative") echo "selected"; ?>>AB Negative</option>
                                    <option value="B Positive" <?php if($contact && $contact->blood_group == "B Positive") echo "selected"; ?>>B Positive</option>
                                    <option value="B Negative" <?php if($contact && $contact->blood_group == "B Negative") echo "selected"; ?>>B Negative</option>
                                    <option value="O Positive" <?php if($contact && $contact->blood_group == "O Positive") echo "selected"; ?>>O Positive</option>
                                    <option value="O Negative" <?php if($contact && $contact->blood_group == "O Negative") echo "selected"; ?>>O Negative</option>
                                </select>
                                <?php echo form_error('blood_group');?>
                            </div>
                        </div> 
                            <div class="patient_profile_image" id="contact-profile-image" >
                                <label for="profile_image" class="profile-image">Profile image</label>
                                <input type="file" name="profile_image" class="form-control" id="profile_image">
                            </div>
                        </div>
                        <div class="col-lg-6">
                       <!-- <?php hooks()->do_action('customer_profile_middlename_field', $client ?? null); ?>
                        <?php $value = (isset($client) ? $client->middlename : ''); ?>
                        <?php  echo render_input('middlename', 'Middle Name', $value); ?>   -->
                            <div class="patient_lastname">   
                                <?php if (isset($contact) && property_exists($contact, 'lastname')) {
                                    $value = $contact->lastname;
                                } 
                                echo render_input('lastname', '<span class="req text-danger">*</span> Last Name', $value, true);?>
                                <div class="text-danger">
                                    <?php echo form_error('lastname');?>
                                </div>
                            </div>                       
                            <div class="patient_dob" app-field-wrapper="dob">
                                <label for="dob" class="control-label"><span class="req text-danger">*</span> Date  of birth</label>
                                    <input type="date" id="dob" name="dob" class="form-control form-group" value="<?php echo $contact->dob ?? ''; ?>">
                                    <div class="text-danger">
                                        <?php echo form_error('dob');?>
                                    </div> 
                            </div>
                            <div class="patient_phone">
                                <?php hooks()->do_action('customer_profile_phone_field', $client ?? null); ?>
                                <?php $value = (isset($client) ? $client->phonenumber : ''); ?>
                                <?php  echo render_input('phonenumber', '<span class="req text-danger">*</span> Phone', $value, true);?>
                                <div class="text-danger">
                                    <?php echo form_error('phonenumber');?>
                                </div>
                            </div>
                            <div class="patient_rx_start">
                                <label for="rx-str-date" class="control-label">Current RX Start Date</label>
                                <input type="date" id="rx-str-date" name="rx_str_date" class="form-control form-group" value="<?php echo $contact->rx_str_date ?? ''; ?>">
                            </div>
                            <div class="patient_rx_end">
                                <label for="rx-end-date" class="control-label">Current RX End Date</label>
                                <input type="date" id="rx-end-date" name="rx_end_date" class="form-control" value="<?php echo $contact->rx_end_date ?? ''; ?>">
                            </div>
                        </div>
                    </div>

                        <!-- <div class="uid">
                        <?php if (isset($contact) && property_exists($contact, 'uid')) {
                            $value = $contact->uid;
                        }else{
                            $value = $uid;
                        } 
                        echo render_input('uid', 'Identification Number (UID)', $value, true,['readonly' => 'readonly']);
                        ?>
                        </div> -->
                        <!-- <?php hooks()->do_action('customer_profile_identificationnumber_field', $client ?? null); ?>
                        <?php $value = (isset($client) ? $client->uid : ''); ?>
                        <?php  echo render_input('uid', 'Identification Number (UID)', $value); ?>  -->

                        <!-- <?php $value = (isset($client) ? $client->address : ''); ?>
                        <?php 
                        echo render_textarea('address', 'client_address', $value); 
                        ?>
                        <?php $value = (isset($client) ? $client->city : ''); ?>
                        <?php 
                        echo render_input('city', 'client_city', $value); 
                        ?>
                        <?php $value = (isset($client) ? $client->state : ''); ?>
                        <?php 
                        echo render_input('state', 'client_state', $value); 
                        ?>
                        <?php $value = (isset($client) ? $client->zip : ''); ?>
                        <?php 
                        echo render_input('zip', 'client_postal_code', $value); 
                        ?> -->
                        <!-- <?php $countries       = get_all_countries();
                     $customer_default_country = get_option('customer_default_country');
                     $selected                 = (isset($client) ? $client->country : $customer_default_country);

                    echo render_select('country', $countries, [ 'country_id', [ 'short_name']], 'clients_country', $selected, ['data-none-selected-text' => _l('dropdown_non_selected_tex')]);
                     ?>  -->
                    </div>
                </div>
            </div>
        </div>
            <?php if (isset($client)) { ?>
            <div role="tabpanel" class="tab-pane" id="customer_admins">
                <?php if (staff_can('create',  'customers') || staff_can('edit',  'customers')) { ?>
                <a href="#" data-toggle="modal" data-target="#customer_admins_assign"
                    class="btn btn-primary mbot30"><?php echo _l('assign_admin'); ?></a>
                <?php } ?>
                <table class="table dt-table">
                    <thead>
                        <tr>
                            <th><?php echo _l('staff_member'); ?></th>
                            <th><?php echo _l('customer_admin_date_assigned'); ?></th>
                            <?php if (staff_can('create',  'customers') || staff_can('edit',  'customers')) { ?>
                            <th><?php echo _l('options'); ?></th>
                            <?php } ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customer_admins as $c_admin) { ?>
                        <tr>
                            <td><a href="<?php echo admin_url('profile/' . $c_admin['staff_id']); ?>">
                                    <?php echo staff_profile_image($c_admin['staff_id'], [
                           'staff-profile-image-small',
                           'mright5',
                           ]);
                           echo e(get_staff_full_name($c_admin['staff_id'])); ?></a>
                            </td>
                            <td data-order="<?php echo e($c_admin['date_assigned']); ?>">
                                <?php echo e(_dt($c_admin['date_assigned'])); ?></td>
                            <?php if (staff_can('create',  'customers') || staff_can('edit',  'customers')) { ?>
                            <td>
                                <a href="<?php echo admin_url('clients/delete_customer_admin/' . $client->userid . '/' . $c_admin['staff_id']); ?>"
                                    class="tw-mt-px tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700 _delete">
                                    <i class="fa-regular fa-trash-can fa-lg"></i>
                                </a>
                            </td>
                            <?php } ?>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            <?php } ?>
            <div role="tabpanel" class="tab-pane" id="billing_and_shipping">
                <div class="row">
                    <div class="col-md-12">
                        <div class="row">
                            <div class="col-md-6">
                                <h4
                                    class="tw-font-medium tw-text-base tw-text-neutral-700 tw-flex tw-justify-between tw-items-center tw-mt-0 tw-mb-6">
                                    <?php echo _l('billing_address'); ?>
                                    <!-- <a href="#"
                                        class="billing-same-as-customer tw-text-sm tw-text-neutral-500 hover:tw-text-neutral-700 active:tw-text-neutral-700">
                                        <?php echo _l('customer_billing_same_as_profile'); ?>
                                    </a> -->
                                </h4>

                                <?php $value = (isset($client) ? $client->billing_street : ''); ?>
                                <?php echo render_textarea('billing_street', 'billing_street', $value); ?>
                                <div class="patient-billing-state" style="margin-top:30px">
                                <?php $value = (isset($client) ? $client->billing_state : ''); ?>
                                <?php echo render_input('billing_state', 'billing_state', $value); ?>
                            </div>
                            </div>
                            <div class="col-md-6">
                                <h4
                                    class="tw-font-medium tw-text-base tw-text-neutral-700 tw-flex tw-justify-between tw-items-center tw-mt-0 tw-mb-6">
                                </h4>
                                <br>
                                <?php $value = (isset($client) ? $client->billing_city : ''); ?>
                                <?php echo render_input('billing_city', 'billing_city', $value); ?>
                                <?php $value = (isset($client) ? $client->billing_zip : ''); ?>
                                <?php echo render_input('billing_zip', 'billing_zip', $value); ?>
                                <?php $selected = (isset($client) ? $client->billing_country : '102'); ?>
                                <?php echo render_select('billing_country', $countries, [ 'country_id', [ 'short_name']], 'billing_country', $selected, ['data-none-selected-text' => _l('dropdown_non_selected_tex')]); ?>
                            </div>
                            <!-- <div class="col-md-6">
                                <h4
                                    class="tw-font-medium tw-text-base tw-text-neutral-700 tw-flex tw-justify-between tw-items-center tw-mt-0 tw-mb-6">
                                    <span>
                                        <i class="fa-regular fa-circle-question tw-mr-1" data-toggle="tooltip"
                                            data-title="<?php echo _l('customer_shipping_address_notice'); ?>"></i>

                                        <?php echo _l('shipping_address'); ?>
                                    </span>
                                    
                                </h4>

                                <?php $value = (isset($client) ? $client->shipping_street : ''); ?>
                                <?php echo render_textarea('shipping_street', 'shipping_street', $value); ?>
                                <?php $value = (isset($client) ? $client->shipping_city : ''); ?>
                                <?php echo render_input('shipping_city', 'shipping_city', $value); ?>
                                <?php $value = (isset($client) ? $client->shipping_state : ''); ?>
                                <?php echo render_input('shipping_state', 'shipping_state', $value); ?>
                                <?php $value = (isset($client) ? $client->shipping_zip : ''); ?>
                                <?php echo render_input('shipping_zip', 'shipping_zip', $value); ?>
                                <?php $selected = (isset($client) ? $client->shipping_country : ''); ?>
                                <?php echo render_select('shipping_country', $countries, [ 'country_id', [ 'short_name']], 'shipping_country', $selected, ['data-none-selected-text' => _l('dropdown_non_selected_tex')]); ?>
                            </div> -->
                            <?php if (isset($client) &&
                        (total_rows(db_prefix() . 'invoices', ['clientid' => $client->userid]) > 0 || total_rows(db_prefix() . 'estimates', ['clientid' => $client->userid]) > 0 || total_rows(db_prefix() . 'creditnotes', ['clientid' => $client->userid]) > 0)) { ?>
                            <div class="col-md-12">
                                <div class="alert alert-warning">
                                    <div class="checkbox checkbox-default -tw-mb-0.5">
                                        <input type="checkbox" name="update_all_other_transactions"
                                            id="update_all_other_transactions">
                                        <label for="update_all_other_transactions">
                                            <?php echo _l('customer_update_address_info_on_invoices'); ?><br />
                                        </label>
                                    </div>
                                    <p class="tw-ml-7 tw-mb-0">
                                        <?php echo _l('customer_update_address_info_on_invoices_help'); ?>
                                    </p>
                                    <div class="checkbox checkbox-default">
                                        <input type="checkbox" name="update_credit_notes" id="update_credit_notes">
                                        <label for="update_credit_notes">
                                            <?php echo _l('customer_profile_update_credit_notes'); ?>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <?php } ?>
                            </div>
                    </div>
                </div>
            </div>

            <div role="tabpanel" class="tab-pane" id="medical_history">
                <div class="row">
                    <div class="col-md-12">
                        <h4 class="tw-font-medium tw-text-base tw-text-neutral-700 tw-flex tw-justify-between tw-items-center tw-mt-0 tw-mb-6">
                            <?php echo _l('Personal and Social History'); ?></h4>
                        <div class="row">
                            <div class="col-md-6">
                                <h4
                                    class="tw-font-medium tw-text-base tw-text-neutral-700 tw-flex tw-justify-between tw-items-center tw-mt-0 tw-mb-6">
                                    <!-- <?php echo _l('Personal and Social History'); ?> -->
                                </h4>
                                <?php if(isset($medical_history) && is_object($medical_history)) {
                                        $value = $medical_history->occupation;
                                    }
                                        echo render_input('occupation', 'Occupation', $value);
                                    ?>
                                    
                            </div>
                            <div class="col-md-6">
                                <h4
                                    class="tw-font-medium tw-text-base tw-text-neutral-700 tw-flex tw-justify-between tw-items-center tw-mt-0 tw-mb-6">
                                </h4>

                                <div class="form-group contact-direction-option">
                                    <label for="marital-status">Marital Status</label>
                                        <div class="dropdown bootstrap-select bs3" style="width: 100%;">
                                        <select class="selectpicker" data-none-selected-text="" data-width="100%" name="marital_status" id="marital-status">
                                    <option value=""></option>
                                    <option value="Single" <?php if($medical_history && $medical_history->marital_status == "Single") echo "selected"; ?>>Single</option>
                                    <option value="Married" <?php if($medical_history && $medical_history->marital_status == "Married") echo "selected"; ?>>Married</option>
                                    <option value="Divorced" <?php if($medical_history && $medical_history->marital_status == "Divorced") echo "selected"; ?>>Divorced</option>
                                    <option value="Widow" <?php if($medical_history && $medical_history->marital_status == "Widow") echo "selected"; ?>>Widow</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                        <div class="chief-complaint">
                            <?php if(isset($medical_history) && is_object($medical_history)) {
                                $value = $medical_history->chief_complaint;
                            }
                            echo render_textarea('chief_complaint', 'Chief Complaint', $value);
                            ?>
                        </div>
                        <div class="history-comment">
                            <!-- <?php if(isset($medical_history) && is_object($medical_history)) {
                                $value = $medical_history->history_comment;
                            }
                            echo render_textarea('history_comment', 'Medical History', $value);
                            ?> -->
                            <?php
                                if ($medical_history !== null) {
                                    $medical_array = explode(', ', $medical_history->medical_history);
                                } else {
                                    $medical_array = array(); 
                                }
                            ?>
                            <div class="history-checkbox">
                                <label for="medical-history">Medical History</label><br>
                                <div class="col-md-6">
                                <input type="checkbox" name="medical_history[]" value="Drug Allergy" <?php if (in_array("Drug Allergy", $medical_array)) echo "checked"; ?>>
                                <label>Drug Allergy</label><br>
                                <input type="checkbox" name="medical_history[]" value="Blood thinner medicine" <?php if (in_array("Blood thinner medicine", $medical_array)) echo "checked"; ?>>
                                <label>Blood thinner medicine</label><br>
                                <input type="checkbox" name="medical_history[]" value="Antacid drugs" <?php if (in_array("Antacid drugs", $medical_array)) echo "checked"; ?>>
                                <label>Antacid drugs</label><br>
                                <input type="checkbox" name="medical_history[]" value="Thyroid" <?php if (in_array("Thyroid", $medical_array)) echo "checked"; ?>>
                                <label>Thyroid</label><br>
                                <input type="checkbox" name="medical_history[]" value="Diabetes" <?php if (in_array("Diabetes", $medical_array)) echo "checked"; ?>>
                                <label>Diabetes</label><br>
                                <input type="checkbox" name="medical_history[]" value="H.T." class="medical-option" <?php if (in_array("H.T.", $medical_array)) echo "checked"; ?>>
                                <label>H.T.</label><br>
                                </div>
                                <div class="col-md-6">
                                <input type="checkbox" name="medical_history[]" value="Bleeding disorder" <?php if (in_array("Bleeding disorder", $medical_array)) echo "checked"; ?>>
                                <label>Bleeding disorder</label><br>
                                <input type="checkbox" name="medical_history[]" value="Radiation" <?php if (in_array("Radiation", $medical_array)) echo "checked"; ?>>
                                <label>Radiation</label><br>
                                <input type="checkbox" name="medical_history[]" value="Kidney disorder" <?php if (in_array("Kidney disorder", $medical_array)) echo "checked"; ?>>
                                <label>Kidney disorder</label><br>
                                <input type="checkbox" name="medical_history[]" value="Liver disorder" <?php if (in_array("Liver disorder", $medical_array)) echo "checked"; ?>>
                                <label>Liver disorder</label><br>
                                <input type="checkbox" name="medical_history[]" value="Pregnancy" <?php if (in_array("Pregnancy", $medical_array)) echo "checked"; ?>>
                                <label>Pregnancy</label><br>
                                <input type="checkbox" name="medical_history[]" value="Lactating mother" <?php if (in_array("Lactating mother", $medical_array)) echo "checked"; ?>>
                                <label>Lactating mother</label><br>
                                </div>
                                </div>
                </div>
                        <div class="row">
                            <div class="col-md-6">
                                <h4
                                    class="tw-font-medium tw-text-base tw-text-neutral-700 tw-flex tw-justify-between tw-items-center tw-mt-0 tw-mb-6">
                                    <!-- <?php echo _l('Personal and Social History'); ?> -->
                                </h4>
                                <h4
                                    class="tw-font-medium tw-text-base tw-text-neutral-700 tw-flex tw-justify-between tw-items-center tw-mt-0 tw-mb-6">
                                    <?php echo _l('Allergies, Medical and Surgical History'); ?>
                                </h4>
                                <?php if(isset($medical_history) && is_object($medical_history)) {
                                        $value = $medical_history->allergies;
                                    }
                                    echo render_textarea('allergies', 'Allergies', $value);
                                    ?>
                                    <?php if(isset($medical_history) && is_object($medical_history)) {
                                        $value = $medical_history->medication;
                                    }
                                    echo render_textarea('medication', 'Medication', $value);
                                ?>
                                <?php if(isset($medical_history) && is_object($medical_history)) {
                                        $value = $medical_history->dental_history;
                                    }
                                    echo render_textarea('dental_history', 'Previous dental history', $value);
                                ?>
                                <?php if(isset($medical_history) && is_object($medical_history)) {
                                        $value = $medical_history->diagnosis;
                                    }
                                    echo render_textarea('diagnosis', 'Provisional Diagnosis', $value);
                                ?>
                                <?php if(isset($medical_history) && is_object($medical_history)) {
                                        $value = $medical_history->previous_medication;
                                    }
                                    echo render_textarea('previous_medication', 'Previous Medication', $value);
                                ?>
                                <h4
                                    class="tw-font-medium tw-text-base tw-text-neutral-700 tw-flex tw-justify-between tw-items-center tw-mt-0 tw-mb-6">
                                    <?php echo _l('Risk Factors'); ?>
                                </h4>
                                <?php if(isset($medical_history) && is_object($medical_history)) {
                                        $value = $medical_history->tobaco_past;
                                    }
                                    echo render_input('tobaco_past', 'Tobacco Consumption (Past)', $value);
                                ?>
                                <?php if(isset($medical_history) && is_object($medical_history)) {
                                        $value = $medical_history->alcohol_past;
                                    }
                                    echo render_input('alcohol_past', 'Alcohol Consumption (Past)', $value);
                                ?>
                                <?php if(isset($medical_history) && is_object($medical_history)) {
                                        $value = $medical_history->enviro_factors;
                                    }
                                    echo render_textarea('enviro_factors', 'Occupational Hazards and Environmental Factors', $value);
                                ?>
                            </div>
                            <div class="col-md-6">
                                <h4
                                    class="tw-font-medium tw-text-base tw-text-neutral-700 tw-flex tw-justify-between tw-items-center tw-mt-0 tw-mb-6">
                                </h4>

                                <br><br>
                                <?php if(isset($medical_history) && is_object($medical_history)) {
                                        $value = $medical_history->surgical_history;
                                    }
                                    echo render_textarea('surgical_history', 'Surgical History', $value);
                                ?>
                                <?php if(isset($medical_history) && is_object($medical_history)) {
                                        $value = $medical_history->disease;
                                    }
                                    echo render_textarea('disease', 'Patient taking drug for systemmetic disease', $value);
                                ?>
                                <?php if(isset($medical_history) && is_object($medical_history)) {
                                        $value = $medical_history->clinical_findings;
                                    }
                                    echo render_textarea('clinical_findings', 'Clinical Findings', $value);
                                ?>
                                <?php if(isset($medical_history) && is_object($medical_history)) {
                                        $value = $medical_history->current_treatment;
                                    }
                                    echo render_textarea('current_treatment', 'Current Treatment', $value);
                                ?>
                                <?php if(isset($medical_history) && is_object($medical_history)) {
                                        $value = $medical_history->current_medication;
                                    }
                                    echo render_textarea('current_medication', 'Current Medication', $value);
                                ?>
                                <br><br>
                                <?php if(isset($medical_history) && is_object($medical_history)) {
                                        $value = $medical_history->tobaco_present;
                                    }
                                    echo render_input('tobaco_present', 'Tobacco Consumption (Present)', $value);
                                ?>
                                <?php if(isset($medical_history) && is_object($medical_history)) {
                                        $value = $medical_history->alcohol_present;
                                    }
                                    echo render_input('alcohol_present', 'Alcohol Consumption (Present)', $value);
                                ?>
                                <?php if(isset($medical_history) && is_object($medical_history)) {
                                        $value = $medical_history->risk_factors;
                                    }
                                    echo render_textarea('risk_factors', 'Other Risk Factors', $value);
                                ?>
                            </div>
                        </div>
                            <div class="treatment-plan">
                                <label for="treatment-plan">Treatment Plan</label><br>
                                <input type="radio" name="treatment_plan" id="immediate" value="Immediate" <?php if($medical_history && $medical_history->treatment_plan == "Immediate") echo "checked"; ?>>
                                <label for="immediate">Immediate</label>
                                <input type="radio" name="treatment_plan" id="planned" value="Planned" <?php if($medical_history && $medical_history->treatment_plan == "Planned") echo "checked"; ?>>
                                <label for="planned">Planned</label>
                            </div> 
                            <div class="history-comment">
                            <?php if(isset($medical_history) && is_object($medical_history)) {
                                $value = $medical_history->history_comment;
                                }
                                echo render_textarea('history_comment', 'Add a comment', $value);
                            ?>
                        </div> 
                    </div>
                    
                </div>     
            </div>
        </div>
    </div>
    
    <?php echo form_close(); ?>
</div>

<div role="tabpanel" class="tab-pane" id="billing_and_shipping">
                <div class="row">
                    <div class="col-md-12">
                        <div class="row">
                            <div class="col-md-6">
                                <h4
                                    class="tw-font-medium tw-text-base tw-text-neutral-700 tw-flex tw-justify-between tw-items-center tw-mt-0 tw-mb-6">
                                    <?php echo _l('billing_address'); ?>
                                    <a href="#"
                                        class="billing-same-as-customer tw-text-sm tw-text-neutral-500 hover:tw-text-neutral-700 active:tw-text-neutral-700">
                                        <?php echo _l('customer_billing_same_as_profile'); ?>
                                    </a>
                                </h4>

                                <?php $value = (isset($client) ? $client->billing_street : ''); ?>
                                <?php echo render_textarea('billing_street', 'billing_street', $value); ?>
                                <?php $value = (isset($client) ? $client->billing_city : ''); ?>
                                <?php echo render_input('billing_city', 'billing_city', $value); ?>
                                <?php $value = (isset($client) ? $client->billing_state : ''); ?>
                                <?php echo render_input('billing_state', 'billing_state', $value); ?>
                                <?php $value = (isset($client) ? $client->billing_zip : ''); ?>
                                <?php echo render_input('billing_zip', 'billing_zip', $value); ?>
                                <?php $selected = (isset($client) ? $client->billing_country : ''); ?>
                                <?php echo render_select('billing_country', $countries, [ 'country_id', [ 'short_name']], 'billing_country', $selected, ['data-none-selected-text' => _l('dropdown_non_selected_tex')]); ?>
                            </div>
                            <div class="col-md-6">
                                <h4
                                    class="tw-font-medium tw-text-base tw-text-neutral-700 tw-flex tw-justify-between tw-items-center tw-mt-0 tw-mb-6">
                                    <span>
                                        <i class="fa-regular fa-circle-question tw-mr-1" data-toggle="tooltip"
                                            data-title="<?php echo _l('customer_shipping_address_notice'); ?>"></i>

                                        <?php echo _l('shipping_address'); ?>
                                    </span>

                                </h4>

                                <?php $value = (isset($client) ? $client->shipping_street : ''); ?>
                                <?php echo render_textarea('shipping_street', 'shipping_street', $value); ?>
                                <?php $value = (isset($client) ? $client->shipping_city : ''); ?>
                                <?php echo render_input('shipping_city', 'shipping_city', $value); ?>
                                <?php $value = (isset($client) ? $client->shipping_state : ''); ?>
                                <?php echo render_input('shipping_state', 'shipping_state', $value); ?>
                                <?php $value = (isset($client) ? $client->shipping_zip : ''); ?>
                                <?php echo render_input('shipping_zip', 'shipping_zip', $value); ?>
                                <?php $selected = (isset($client) ? $client->shipping_country : ''); ?>
                                <?php echo render_select('shipping_country', $countries, [ 'country_id', [ 'short_name']], 'shipping_country', $selected, ['data-none-selected-text' => _l('dropdown_non_selected_tex')]); ?>
                            </div>
                            <?php if (isset($client) &&
                        (total_rows(db_prefix() . 'invoices', ['clientid' => $client->userid]) > 0 || total_rows(db_prefix() . 'estimates', ['clientid' => $client->userid]) > 0 || total_rows(db_prefix() . 'creditnotes', ['clientid' => $client->userid]) > 0)) { ?>
                            <div class="col-md-12">
                                <div class="alert alert-warning">
                                    <div class="checkbox checkbox-default -tw-mb-0.5">
                                        <input type="checkbox" name="update_all_other_transactions"
                                            id="update_all_other_transactions">
                                        <label for="update_all_other_transactions">
                                            <?php echo _l('customer_update_address_info_on_invoices'); ?><br />
                                        </label>
                                    </div>
                                    <p class="tw-ml-7 tw-mb-0">
                                        <?php echo _l('customer_update_address_info_on_invoices_help'); ?>
                                    </p>
                                    <div class="checkbox checkbox-default">
                                        <input type="checkbox" name="update_credit_notes" id="update_credit_notes">
                                        <label for="update_credit_notes">
                                            <?php echo _l('customer_profile_update_credit_notes'); ?>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
<?php if (isset($client)) { ?>
<?php if (staff_can('create',  'customers') || staff_can('edit',  'customers')) { ?>
<div class="modal fade" id="customer_admins_assign" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <?php echo form_open(admin_url('clients/assign_admins/' . $client->userid)); ?>
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><?php echo _l('assign_admin'); ?></h4>
            </div>
            <div class="modal-body">
                <?php
               $selected = [];
               foreach ($customer_admins as $c_admin) {
                   array_push($selected, $c_admin['staff_id']);
               }
               echo render_select('customer_admins[]', $staff, ['staffid', ['firstname', 'lastname']], '', $selected, ['multiple' => true], [], '', '', false); ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
                <button type="submit" class="btn btn-primary"><?php echo _l('submit'); ?></button>
            </div>
        </div>
        <!-- /.modal-content -->
        <?php echo form_close(); ?>
    </div>
    <!-- /.modal-dialog -->
</div>
<!-- /.modal -->
<?php } ?>
<?php } ?>
<?php $this->load->view('admin/clients/client_group'); ?>





                                                

                        