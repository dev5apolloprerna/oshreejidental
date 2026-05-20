<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<ul class="nav navbar-pills navbar-pills-flat nav-tabs nav-stacked customer-tabs" role="tablist">
  <?php
  foreach(filter_branch_visible_tabs($branch_tabs, $branch->branchid) as $key => $tab){
    ?>
    <li class="<?php if($key == 'profile'){echo 'active ';} ?>branch_tab<?php echo e($key); ?>">
      <a data-group="<?php echo e($key); ?>" href="<?php echo admin_url('branches/branch/'.$branch->branchid.'?group='.$key); ?>">
        <?php if(!empty($tab['icon'])){ ?>
            <i class="<?php echo e($tab['icon']); ?> menu-icon" aria-hidden="true"></i>
        <?php } ?>
        <?php echo e($tab['name']); ?>
        <?php if (isset($tab['badge'], $tab['badge']['value']) && !empty($tab['badge'])) {?>
          <span class="badge pull-right 
            <?=isset($tab['badge']['type']) &&  $tab['badge']['type'] != '' ? "bg-{$tab['badge']['type']}" : 'bg-info' ?>"
              <?=(isset($tab['badge']['type']) &&  $tab['badge']['type'] == '') ||
                      isset($tab['badge']['color']) ? "style='background-color: {$tab['badge']['color']}'" : '' ?>>
              <?= e($tab['badge']['value']) ?>
          </span>
        <?php } ?>
      </a>
    </li>
  <?php } ?>
</ul>
