<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<!-- Modal Contact -->
<div class="modal fade" id="xray" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <?php echo form_open_multipart(admin_url('clients/upload_attachment/' . $client->userid), ['id' => 'client-attachments-upload']); ?>
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
                        <div class="tw-flex">
                            <!-- <div class="tw-mr-4 tw-flex-shrink-0 tw-relative">
                                <?php if (isset($contact)) { ?>
                                    <?php if (!empty($contact->profile_image)) { ?>
                                    <a href="#" onclick="delete_contact_profile_image(<?php echo e($contact->id); ?>); return false;"
                                        class="tw-bg-neutral-500/30 tw-text-neutral-600 hover:tw-text-neutral-500 tw-h-8 tw-w-8 tw-inline-flex tw-items-center tw-justify-center tw-rounded-full tw-absolute tw-inset-0"
                                        id="contact-remove-img"><i class="fa fa-remove tw-mt-1"></i></a>
                                    <?php } ?>
                                <?php } ?>
                            </div> -->
                            <div>
                                <h4 class="modal-title tw-mb-0"> Upload Image</h4>
                            </div>
                        </div>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div id="xray_image_head" class="form-group">
                           <!--  <label for="xray_image_title">Title</label>
                            <input type="text" name="xray_title" class="form-control" id="xray_image_title" required> -->
                             <?php echo render_input('xray_title', 'Title', ''); ?>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <!-- <div id="contact-profile-image" class="form-group<?php if (isset($contact) && !empty($contact->profile_image)) {
                            echo ' hide';} ?>"> -->
                        <label for="profile_image"><small class="req text-danger">* </small>Xray image</label>
                            <input type="file" name="file" class="form-control" id="file" accept="image/*">

                        </div>
                    </div>
                </div>
            
    
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
                <button type="submit" class="btn btn-primary" id="upload-button"><?php echo _l('submit'); ?></button>
            </div>
            <?php echo form_close(); ?>
        </div>
    </div>
</div>
<?php if (!isset($contact)) { ?>
<?php 

} ?>

