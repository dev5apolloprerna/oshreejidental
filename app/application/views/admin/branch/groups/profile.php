<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<?php 
// printr($branch);
if (isset($branch)) { ?>
<h4 class="customer-profile-group-heading"><?php echo _l('branch_add_edit_profile'); ?></h4>
<?php } ?>

<div class="row">
    <?php echo form_open($this->uri->uri_string(), ['class' => 'branch-form', 'autocomplete' => 'off']); ?>
    <div class="additional"></div>
    <div class="col-md-12">
        <div class="horizontal-scrollable-tabs panel-full-width-tabs">
            <div class="scroller arrow-left"><i class="fa fa-angle-left"></i></div>
            <div class="scroller arrow-right"><i class="fa fa-angle-right"></i></div>
            <div class="horizontal-tabs">
                <ul class="nav nav-tabs branch-profile-tabs nav-tabs-horizontal" role="tablist">
                    <li role="presentation" class="<?php echo !$this->input->get('tab') ? 'active' : ''; ?>">
                        <a href="#branch_info" aria-controls="branch_info" role="tab" data-toggle="tab">
                            <?php echo _l('branch_profile_details'); ?>
                        </a>
                    </li>
                    <?php
                  $branch_custom_fields = false;
                  if (total_rows(db_prefix() . 'customfields', ['fieldto' => 'branch', 'active' => 1]) > 0) {
                      $branch_custom_fields = true; ?>
                    <li role="presentation" class="<?php if ($this->input->get('tab') == 'custom_fields') {
                          echo 'active';
                      }; ?>">
                        <a href="#custom_fields" aria-controls="custom_fields" role="tab" data-toggle="tab">
                            <?php echo hooks()->apply_filters('branch_profile_tab_custom_fields_text', _l('custom_fields')); ?>
                        </a>
                    </li>
                    <?php
                  } ?>
                    <li role="presentation" style="display: none;">
                        <a href="#billing_and_shipping" aria-controls="billing_and_shipping" role="tab"
                            data-toggle="tab">
                            <?php echo _l('billing_shipping'); ?>
                        </a>
                    </li>
                    <?php hooks()->do_action('after_branch_billing_and_shipping_tab', isset($branch) ? $branch : false); ?>
                    <?php if (isset($branch)) { ?>
                    <li role="presentation">
                        <a href="#branch_admins" aria-controls="branch_admins" role="tab" data-toggle="tab">
                            <?php echo _l('branch_admins'); ?>
                            <?php if (count($branch_admins) > 0) { ?>
                            <span class="badge bg-default"><?php echo count($branch_admins) ?></span>
                            <?php } ?>
                        </a>
                    </li>
                    <?php hooks()->do_action('after_branch_admins_tab', $branch); ?>
                    <?php } ?>
                </ul>
            </div>
        </div>
        <div class="tab-content mtop15">
            <?php hooks()->do_action('after_custom_profile_tab_content', isset($branch) ? $branch : false); ?>
            <?php if ($branch_custom_fields) { ?>
            <div role="tabpanel" class="tab-pane <?php if ($this->input->get('tab') == 'custom_fields') {
                      echo ' active';
                  }; ?>" id="custom_fields">
                <?php $rel_id = (isset($branch) ? $branch->branchid : false); ?>
                <?php echo render_custom_fields('customers', $rel_id); ?>
            </div>
            <?php } ?>
            <div role="tabpanel" class="tab-pane<?php if (!$this->input->get('tab')) {
                      echo ' active';
                  }; ?>" id="branch_info">
                <div class="row">
                    <div class="col-md-12 <?php if (isset($branch) && (!is_empty_branch($branch->branchid) && total_rows(db_prefix() . 'contacts', ['branchid' => $branch->branchid, 'is_primary' => 1]) > 0)) {
                      echo '';
                  } else {
                      echo ' hide';
                  } ?>" id="client-show-primary-contact-wrapper">
                        <div class="checkbox checkbox-info mbot20 no-mtop">
                            <input type="checkbox" name="show_primary_contact" <?php if (isset($branch) && $branch->show_primary_contact == 1) {
                      echo ' checked';
                  }?> value="1" id="show_primary_contact">
                            <label
                                for="show_primary_contact"><?php echo _l('show_primary_contact', _l('invoices') . ', ' . _l('estimates') . ', ' . _l('payments') . ', ' . _l('credit_notes')); ?></label>
                        </div>
                    </div>
                    <div class="col-md-<?php echo !isset($branch) ? 12 : 8; ?>">
                        <?php hooks()->do_action('before_branch_profile_branch_field', $branch ?? null); ?>
                        <?php $value = (isset($branch) ? $branch->branch : ''); 
                        ?>
                        <?php $attrs = (isset($branch) ? [] : ['autofocus' => true]); ?>
                        <?php echo render_input('branch', 'branch', $value, 'text', $attrs); ?>
                        <div id="branch_exists_info" class="hide"></div>
                        <?php hooks()->do_action('after_branch_profile_branch_field', $branch ?? null); ?>
                        <?php 
                            if (get_option('branch_requires_vat_number_field') == 1) 
                            {
                                $value = (isset($branch) ? $branch->vat : '');
                                echo render_input('vat', 'branch_vat_number', $value);
                            } 
                        ?>
                                <?php hooks()->do_action('before_branch_profile_phone_field', $branch ?? null); ?>
                        <?php $value = (isset($branch) ? $branch->phonenumber : ''); ?>
                        <?php echo render_input('phonenumber', 'branch_phonenumber', $value); ?>
                        <?php hooks()->do_action('after_branch_profile_branch_phone', $branch ?? null); ?>
                        <?php if ((isset($branch) && empty($branch->website)) || !isset($branch)) {
                      $value = (isset($branch) ? $branch->website : '');
                      echo render_input('website', 'branch_website', $value);
                  } else { ?>
                        <div class="form-group">
                            <label for="website"><?php echo _l('branch_website'); ?></label>
                            <div class="input-group">
                                <input type="text" name="website" id="website" value="<?php echo e($branch->website); ?>"
                                    class="form-control">
                                <span class="input-group-btn">
                                    <a href="<?php echo e(maybe_add_http($branch->website)); ?>" class="btn btn-default"
                                        target="_blank" tabindex="-1">
                                        <i class="fa fa-globe"></i></a>
                                </span>

                            </div>
                        </div>
                        <?php }
                    //  $selected = [];
                    //  if (isset($branch_groups)) {
                    //      foreach ($branch_groups as $group) {
                    //          array_push($selected, $group['groupid']);
                    //      }
                    //  }
                    //  if (is_admin() || get_option('staff_members_create_inline_branch_groups') == '1') {
                    //      echo render_select_with_input_group('groups_in[]', $groups, ['id', 'name'], 'branch_groups', $selected, '<div class="input-group-btn"><a href="#" class="btn btn-default" data-toggle="modal" data-target="#branch_group_modal"><i class="fa fa-plus"></i></a></div>', ['multiple' => true, 'data-actions-box' => true], [], '', '', false);
                    //  } else {
                    //      echo render_select('groups_in[]', $groups, ['id', 'name'], 'branch_groups', $selected, ['multiple' => true, 'data-actions-box' => true], [], '', '', false);
                    //  }
                     ?>
                        <div class="row" style="display: none;">
                            <div class="col-md-<?php echo !is_language_disabled() ? 6 : 12; ?>">
                                <i class="fa-regular fa-circle-question pull-left tw-mt-0.5 tw-mr-1"
                                    data-toggle="tooltip"
                                    data-title="<?php echo _l('branch_currency_change_notice'); ?>"></i>
                                <?php
                     $s_attrs  = ['data-none-selected-text' => _l('system_default_string')];
                     $selected = '';
                     if (isset($branch) && branch_have_transactions($branch->branchid)) {
                         $s_attrs['disabled'] = true;
                     }
                     foreach ($currencies as $currency) {
                         if (isset($branch)) {
                             if ($currency['id'] == $branch->default_currency) {
                                 $selected = $currency['id'];
                             }
                         }
                     }
                            // Do not remove the currency field from the customer profile!
                     echo render_select('default_currency', $currencies, ['id', 'name', 'symbol'], 'invoice_add_edit_currency', $selected, $s_attrs);
                     ?>
                            </div>
                            <?php if (!is_language_disabled()) { ?>
                            <div class="col-md-6" style="display: none;">

                                <div class="form-group select-placeholder">
                                    <label for="default_language"
                                        class="control-label"><?php echo _l('localization_default_language'); ?>
                                    </label>
                                    <select name="default_language" id="default_language"
                                        class="form-control selectpicker"
                                        data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>">
                                        <option value=""><?php echo _l('system_default_string'); ?></option>
                                        <?php foreach ($this->app->get_available_languages() as $availableLanguage) {
                         $selected = '';
                         if (isset($branch)) {
                             if ($branch->default_language == $availableLanguage) {
                                 $selected = 'selected';
                             }
                         } ?>
                                        <option value="<?php echo e($availableLanguage); ?>" <?php echo e($selected); ?>>
                                            <?php echo e(ucfirst($availableLanguage)); ?></option>
                                        <?php
                     } ?>
                                    </select>
                                </div>
                            </div>
                            <?php } ?>
                        </div>

                        <hr />

                        <?php $value = (isset($branch) ? $branch->address : ''); ?>
                        <?php echo render_textarea('address', 'branch_address', $value); ?>
                        <?php $value = (isset($branch) ? $branch->city : ''); ?>
                        <?php echo render_input('city', 'branch_city', $value); ?>
                        <?php $value = (isset($branch) ? $branch->state : ''); ?>
                        <?php echo render_input('state', 'branch_state', $value); ?>
                        <?php $value = (isset($branch) ? $branch->zip : ''); ?>
                        <?php echo render_input('zip', 'branch_postal_code', $value); ?>
                        <?php $countries       = get_all_countries();
                     $branch_default_country = get_option('branch_default_country');
                     $selected                 = (isset($branch) ? $branch->country : $branch_default_country);
                     echo render_select('country', $countries, [ 'country_id', [ 'short_name']], 'clients_country', $selected, ['data-none-selected-text' => _l('dropdown_non_selected_tex')]);
                     ?>
                    </div>
                </div>
            </div>
            <?php if (isset($branch)) { ?>
            <div role="tabpanel" class="tab-pane" id="branch_admins"  >
                <?php if (staff_can('create',  'customers') || staff_can('edit',  'customers')) { ?>
                <a href="#" data-toggle="modal" data-target="#branch_admins_assign"
                    class="btn btn-primary mbot30"><?php echo _l('assign_admin'); ?></a>
                <?php } ?>
                <table class="table dt-table">
                    <thead>
                        <tr>
                            <th><?php echo _l('staff_member'); ?></th>
                            <th><?php echo _l('branch_admin_date_assigned'); ?></th>
                            <?php if (staff_can('create',  'customers') || staff_can('edit',  'customers')) { ?>
                            <th><?php echo _l('options'); ?></th>
                            <?php } ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($branch_admins as $c_admin) { ?>
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
                                <a href="<?php echo admin_url('branches/delete_branch_admin/' . $branch->branchid . '/' . $c_admin['staff_id']); ?>"
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
            <div role="tabpanel" class="tab-pane" id="billing_and_shipping" style="display: none;">
                <div class="row">
                    <div class="col-md-12">
                        <div class="row">
                            <div class="col-md-6">
                                <h4
                                    class="tw-font-medium tw-text-base tw-text-neutral-700 tw-flex tw-justify-between tw-items-center tw-mt-0 tw-mb-6">
                                    <?php echo _l('billing_address'); ?>
                                    <a href="#"
                                        class="billing-same-as-customer tw-text-sm tw-text-neutral-500 hover:tw-text-neutral-700 active:tw-text-neutral-700">
                                        <?php echo _l('branch_billing_same_as_profile'); ?>
                                    </a>
                                </h4>

                                <?php $value = (isset($branch) ? $branch->billing_street : ''); ?>
                                <?php echo render_textarea('billing_street', 'billing_street', $value); ?>
                                <?php $value = (isset($branch) ? $branch->billing_city : ''); ?>
                                <?php echo render_input('billing_city', 'billing_city', $value); ?>
                                <?php $value = (isset($branch) ? $branch->billing_state : ''); ?>
                                <?php echo render_input('billing_state', 'billing_state', $value); ?>
                                <?php $value = (isset($branch) ? $branch->billing_zip : ''); ?>
                                <?php echo render_input('billing_zip', 'billing_zip', $value); ?>
                                <?php $selected = (isset($branch) ? $branch->billing_country : ''); ?>
                                <?php echo render_select('billing_country', $countries, [ 'country_id', [ 'short_name']], 'billing_country', $selected, ['data-none-selected-text' => _l('dropdown_non_selected_tex')]); ?>
                            </div>
                            <div class="col-md-6">
                                <h4
                                    class="tw-font-medium tw-text-base tw-text-neutral-700 tw-flex tw-justify-between tw-items-center tw-mt-0 tw-mb-6">
                                    <span>
                                        <i class="fa-regular fa-circle-question tw-mr-1" data-toggle="tooltip"
                                            data-title="<?php echo _l('branch_shipping_address_notice'); ?>"></i>

                                        <?php echo _l('shipping_address'); ?>
                                    </span>
                                    <a href="#"
                                        class="customer-copy-billing-address tw-text-sm tw-text-neutral-500 hover:tw-text-neutral-700 active:tw-text-neutral-700">
                                        <?php echo _l('branch_billing_copy'); ?>
                                    </a>
                                </h4>

                                <?php $value = (isset($branch) ? $branch->shipping_street : ''); ?>
                                <?php echo render_textarea('shipping_street', 'shipping_street', $value); ?>
                                <?php $value = (isset($branch) ? $branch->shipping_city : ''); ?>
                                <?php echo render_input('shipping_city', 'shipping_city', $value); ?>
                                <?php $value = (isset($branch) ? $branch->shipping_state : ''); ?>
                                <?php echo render_input('shipping_state', 'shipping_state', $value); ?>
                                <?php $value = (isset($branch) ? $branch->shipping_zip : ''); ?>
                                <?php echo render_input('shipping_zip', 'shipping_zip', $value); ?>
                                <?php $selected = (isset($branch) ? $branch->shipping_country : ''); ?>
                                <?php echo render_select('shipping_country', $countries, [ 'country_id', [ 'short_name']], 'shipping_country', $selected, ['data-none-selected-text' => _l('dropdown_non_selected_tex')]); ?>
                            </div>
                            <?php if (isset($branch) &&
                        (total_rows(db_prefix() . 'invoices', ['branchid' => $branch->branchid]) > 0 || total_rows(db_prefix() . 'estimates', ['branchid' => $branch->branchid]) > 0 || total_rows(db_prefix() . 'creditnotes', ['branchid' => $branch->branchid]) > 0)) { ?>
                            <div class="col-md-12">
                                <div class="alert alert-warning">
                                    <div class="checkbox checkbox-default -tw-mb-0.5">
                                        <input type="checkbox" name="update_all_other_transactions"
                                            id="update_all_other_transactions">
                                        <label for="update_all_other_transactions">
                                            <?php echo _l('branch_update_address_info_on_invoices'); ?><br />
                                        </label>
                                    </div>
                                    <p class="tw-ml-7 tw-mb-0">
                                        <?php echo _l('branch_update_address_info_on_invoices_help'); ?>
                                    </p>
                                    <div class="checkbox checkbox-default">
                                        <input type="checkbox" name="update_credit_notes" id="update_credit_notes">
                                        <label for="update_credit_notes">
                                            <?php echo _l('branch_profile_update_credit_notes'); ?>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php echo form_close(); ?>
</div>
<?php if (isset($branch)) { ?>
<?php if (staff_can('create',  'customers') || staff_can('edit',  'customers')) { ?>
<div class="modal fade" id="branch_admins_assign" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <?php echo form_open(admin_url('branchesassign_admins/' . $branch->branchid)); ?>
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><?php echo _l('assign_admin'); ?></h4>
            </div>
            <div class="modal-body">
                <?php
               $selected = [];
               foreach ($branch_admins as $c_admin) {
                   array_push($selected, $c_admin['staff_id']);
               }
               echo render_select('branch_admins[]', $staff, ['staffid', ['firstname', 'lastname']], '', $selected, ['multiple' => true], [], '', '', false); ?>
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
<?php $this->load->view('admin/branch/branch_group'); ?>