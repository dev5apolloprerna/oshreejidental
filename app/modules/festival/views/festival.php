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
                        <?php $attrs = (isset($offer) ? array() : array('autofocus'=>true)); ?>
                        <?php $value = (isset($festival) ? $festival->title : ''); ?>
                        <?php echo render_input('title','festival_name',$value,'text'); ?>
                        <?php $value = (isset($festival) ? $festival->date : ''); ?>
                        <?php echo render_date_input('date','festival_date',$value,'number'); ?>
                        <?php $attrs['class'] = isset($attrs['class']) ? $attrs['class'] . ' tinymce' : 'tinymce'; 
                        $value = (isset($festival) ? $festival->message : ''); 
                        echo render_textarea('message','festival_message',$value,$attrs); ?>
                        
                        
                        <input  name="submit" type="submit" class="btn btn-primary pull-right">
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
        title: 'required',
        message: 'required',
        date: 'required'
        });
    });
    </script>
</body>
</html>

 