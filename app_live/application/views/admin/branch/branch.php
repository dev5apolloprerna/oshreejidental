<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper" class="branch_profile">
    <div class="content">
        <?php  if (isset($branch) && $branch->registration_confirmed == 0 && is_admin()) { ?>
        <div class="alert alert-warning">
            <h4>
                <?php echo _l('branch_requires_registration_confirmation'); ?>
            </h4>
            <a href="<?php echo admin_url('branches/confirm_registration/' . $branch->branchid); ?>">
                <?php echo _l('confirm_registration'); ?>
            </a>
        </div>
        <?php } elseif (isset($branch) && $branch->active == 0 && $branch->registration_confirmed == 1) { ?>
        <div class="alert alert-warning">
            <?php echo _l('branch_inactive_message'); ?>
            <br />
            <a href="<?php echo admin_url('branches/mark_as_active/' . $branch->branchid); ?>">
                <?php echo _l('mark_as_active'); ?>
            </a>
        </div>
        <?php } ?>
        <?php if (isset($branch) && (staff_cant('view', 'branch') && is_branch_admin($branch->branchid))) {?>
        <div class="alert alert-info">
            <?php echo e(_l('branch_admin_login_as_client_message', get_staff_full_name(get_staff_user_id()))); ?>
        </div>
        <?php } ?>
        <div class="row">
            <div class="col-md-3">
                <?php if (isset($branch)) { ?>
                <h4 class="tw-text-lg tw-font-semibold tw-text-neutral-800 tw-mt-0">
                    <div class="tw-space-x-3 tw-flex tw-items-center">
                        <span class="tw-truncate">
                            #<?php echo $branch->branchid . ' ' . $title; ?>
                        </span>
                        <?php if (staff_can('delete',  'branch') || is_admin()) { ?>
                        <div class="btn-group">
                            <a href="#" class="dropdown-toggle btn-link" data-toggle="dropdown" aria-haspopup="true"
                                aria-expanded="false">
                                <span class="caret"></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-right">
                                <?php if (is_admin()) { ?>
                                <li>
                                    <a href="<?php echo admin_url('branches/login_as_client/' . $branch->branchid); ?>"
                                        target="_blank">
                                        <i class="fa-regular fa-share-from-square"></i>
                                        <?php echo _l('login_as_client'); ?>
                                    </a>
                                </li>
                                <?php } ?>
                                <?php if (staff_can('delete',  'branch')) { ?>
                                <li>
                                    <a href="<?php echo admin_url('branches/delete/' . $branch->branchid); ?>"
                                        class="text-danger delete-text _delete"><i class="fa fa-remove"></i>
                                        <?php echo _l('delete'); ?>
                                    </a>
                                </li>
                                <?php } ?>
                            </ul>
                        </div>
                        <?php } ?>
                    </div>
                    <?php if (isset($branch)) { ?>
                    <small class="tw-block">
                        <b><?php echo e(_l('branch_from_lead', _l('lead'))); ?></b>
                        <a href="<?php echo admin_url('leads/index/' . $branch->leadid); ?>"
                            onclick="init_lead(<?php echo e($branch->leadid); ?>); return false;">
                            - <?php echo _l('view'); ?>
                        </a>
                    </small>
                    <?php } ?>
                </h4>
                <?php } ?>
            </div>
            <div class="clearfix"></div>

            <?php if (isset($branch)) { ?>
            <div class="col-md-3">
                <?php $this->load->view('admin/branch/tabs'); ?>
            </div>
            <?php } ?>

            <div class="tw-mt-12 sm:tw-mt-0 <?php echo isset($branch) ? 'col-md-9' : 'col-md-8 col-md-offset-2'; ?>">
                <div class="panel_s">
                    <div class="panel-body">
                        <?php if (isset($branch)) { ?>
                        <?php echo form_hidden('isedit'); ?>
                        <?php echo form_hidden('branchid', $branch->branchid); ?>
                        <div class="clearfix"></div>
                        <?php } ?>
                        <div>
                            <div class="tab-content">
                                <?php 
                                $this->load->view((isset($tab) ? $tab['view'] : 'admin/branch/groups/profile')); ?>
                            </div>
                        </div>
                    </div>
                    <?php if ($group == 'profile') { ?>
                    <div class="panel-footer text-right tw-space-x-1" id="profile-save-section">
                        <?php if (!isset($branch)) { ?>
                        <button class="btn btn-default save-and-add-contact branch-form-submiter">
                            <?php echo _l('save_branch_and_add_contact'); ?>
                        </button>
                        <?php } ?>
                        <button class="btn btn-primary only-save branch-form-submiter">
                            <?php echo _l('submit'); ?>
                        </button>
                    </div>
                    <?php } ?>
                </div>
            </div>
        </div>

    </div>
</div>
<?php init_tail(); ?>
<?php if (isset($branch)) { ?>
<script>
$(function() {
    init_rel_tasks_table(<?php echo e($branch->branchid); ?>, 'customer');
});
</script>
<?php } ?>
<?php $this->load->view('admin/branch/branch_js'); ?>
</body>

</html>