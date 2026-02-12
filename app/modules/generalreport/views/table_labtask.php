<?php

defined('BASEPATH') or exit('No direct script access allowed');

$tasksPriorities     = get_tasks_priorities();
        $task_statuses = $this->ci->tasks_model->get_statuses();

        $aColumns = [
            '1', // bulk actions
            db_prefix() . 'tasks.id as id',
            db_prefix() . 'tasks.name as task_name',
            'status',
            'startdate',
            'duedate',
            get_sql_select_task_asignees_full_names() . ' as assignees',
            '(SELECT GROUP_CONCAT(name SEPARATOR ",") FROM ' . db_prefix() . 'taggables JOIN ' . db_prefix() . 'tags ON ' . db_prefix() . 'taggables.tag_id = ' . db_prefix() . 'tags.id WHERE rel_id = ' . db_prefix() . 'tasks.id and rel_type="task" ORDER by tag_order ASC) as tags',
            'priority',
        ];

        $sIndexColumn = 'id';
        $sTable       = db_prefix() . 'tasks';

        $where = [];


         $filters = [];


        if($this->ci->input->get('staff_id') != ''){
           $filters[] = 'AND ' . db_prefix() . 'tasks.id
    IN (SELECT taskid FROM ' . db_prefix() . 'task_assigned WHERE staffid=' . $this->ci->input->get('staff_id') . ')';
        }

        if ($this->ci->input->get('status') != '') {
            $filters[] = 'AND status = ' . $this->ci->input->get('status');
        }
       

        $beginOfDay = date('Y-m-d',strtotime('midnight'));

        $endOfDay   = date('Y-m-d',strtotime('tomorrow'));

        $beginThisMonth = date('Y-m-01');

        $endThisMonth   = date('Y-m-t');

        $beginThisWeek = date('Y-m-d', strtotime('monday this week'));

        $endThisWeek   = date('Y-m-d', strtotime('sunday this week'));


        $sql ='';
        $filter = $this->ci->input->get('range');
        $client_id = $this->ci->input->get('client_id');
        $area = $this->ci->input->get('area');
        $start_date = $this->ci->input->get('start_date');
        $end_date = $this->ci->input->get('end_date');
        $deliverymans = $this->ci->input->get('staff_id');
        $service_sche_where = '';

        if($filter == 'today')
        {
             $filters[] = 'AND (startdate BETWEEN "'.$beginOfDay.'" AND "'.$endOfDay.'" OR duedate BETWEEN "'.$beginOfDay.'" AND "'.$endOfDay.'")';
           

        }
        elseif($filter == 'this_week')
        {
            $filters[] = 'AND (startdate BETWEEN "'.$beginThisWeek.'" AND "'.$endThisWeek.'" OR duedate BETWEEN "'.$beginThisWeek.'" AND "'.$endThisWeek.'")';
           
        }
        elseif($filter == 'this_month')
        {
            $filters[]= 'AND (startdate BETWEEN "'.$beginThisMonth.'" AND "'.$endThisMonth.'" OR duedate BETWEEN "'.$beginThisMonth.'" AND "'.$endThisMonth.'")';
            
        }

        if($start_date != '' && $end_date != ''){

            if($filter == ''){
                 $filters[] = 'AND (startdate BETWEEN "'.$start_date.'" AND "'.$end_date.'" OR duedate BETWEEN "'.$start_date.'" AND "'.$end_date.'")';
            }
            
        }

        $client_idsql  = '';
        if($client_id != ''){

           
            $filters[] = 'AND  rel_id = '.$client_id . ' AND rel_type = "customer"';
            
        }





        if (count($filters) > 0) {
            array_push($where, 'AND (' . prepare_dt_filter($filters) . ')');
        }

        $join  = [];

        

        // Dashboard my tasks table
        if($this->ci->input->post('my_tasks')) {
            $where[] = 'AND (' . db_prefix() . 'tasks.id IN (SELECT taskid FROM ' . db_prefix() . 'task_assigned WHERE staffid = ' . get_staff_user_id() . ') AND status != '.Tasks_model::STATUS_COMPLETE.')';
        }

        array_push($where, 'AND is_lab_task = 1 AND CASE WHEN rel_type="project" AND rel_id IN (SELECT project_id FROM ' . db_prefix() . 'project_settings WHERE project_id=rel_id AND name="hide_tasks_on_main_tasks_table" AND value=1) THEN rel_type != "project" ELSE 1=1 END');

        $custom_fields = get_table_custom_fields('tasks');

        foreach ($custom_fields as $key => $field) {
            $selectAs = (is_cf_date($field) ? 'date_picker_cvalue_' . $key : 'cvalue_' . $key);
            array_push($customFieldsColumns, $selectAs);
            array_push($aColumns, '(SELECT value FROM ' . db_prefix() . 'customfieldsvalues WHERE ' . db_prefix() . 'customfieldsvalues.relid=' . db_prefix() . 'tasks.id AND ' . db_prefix() . 'customfieldsvalues.fieldid=' . $field['id'] . ' AND ' . db_prefix() . 'customfieldsvalues.fieldto="' . $field['fieldto'] . '" LIMIT 1) as ' . $selectAs);
        }

        $aColumns = hooks()->apply_filters('tasks_table_sql_columns', $aColumns);

        // Fix for big queries. Some hosting have max_join_limit
        if (count($custom_fields) > 4) {
            @$this->ci->db->query('SET SQL_BIG_SELECTS=1');
        }

       

        $result = data_tables_init(
            $aColumns,
            $sIndexColumn,
            $sTable,
            $join,
            $where,
            [
                'rel_type',
                'rel_id',
                'recurring',
                tasks_rel_name_select_query() . ' as rel_name',
                'billed',
                '(SELECT staffid FROM ' . db_prefix() . 'task_assigned WHERE taskid=' . db_prefix() . 'tasks.id AND staffid=' . get_staff_user_id() . ') as is_assigned',
                get_sql_select_task_assignees_ids() . ' as assignees_ids',
                '(SELECT MAX(id) FROM ' . db_prefix() . 'taskstimers WHERE task_id=' . db_prefix() . 'tasks.id and staff_id=' . get_staff_user_id() . ' and end_time IS NULL) as not_finished_timer_by_current_staff',
                '(SELECT staffid FROM ' . db_prefix() . 'task_assigned WHERE taskid=' . db_prefix() . 'tasks.id AND staffid=' . get_staff_user_id() . ') as current_user_is_assigned',
                '(SELECT CASE WHEN addedfrom=' . get_staff_user_id() . ' AND is_added_from_contact=0 THEN 1 ELSE 0 END) as current_user_is_creator',
            ]
        );

        $output  = $result['output'];
        $rResult = $result['rResult'];

        foreach ($rResult as $aRow) {
            $row = [];

            $row[] = '<div class="checkbox"><input type="checkbox" value="' . $aRow['id'] . '"><label></label></div>';

            $row[] = '<a href="' . admin_url('tasks/view/' . $aRow['id']) . '" onclick="init_task_modal(' . $aRow['id'] . '); return false;">' . $aRow['id'] . '</a>';

            $outputName = '';

            if ($aRow['not_finished_timer_by_current_staff']) {
                $outputName .= '<span class="pull-left text-danger"><i class="fa-regular fa-clock fa-fw tw-mr-1"></i></span>';
            }

            $outputName .= '<a href="' . admin_url('tasks/view/' . $aRow['id']) . '" class="display-block main-tasks-table-href-name' . (!empty($aRow['rel_id']) ? ' mbot5' : '') . '" onclick="init_task_modal(' . $aRow['id'] . '); return false;">' . e($aRow['task_name']) . '</a>';

            if ($aRow['rel_name']) {
                $relName = task_rel_name($aRow['rel_name'], $aRow['rel_id'], $aRow['rel_type']);

                $link = task_rel_link($aRow['rel_id'], $aRow['rel_type']);

                $outputName .= '<span class="hide"> - </span><a class="tw-text-neutral-700 task-table-related tw-text-sm" data-toggle="tooltip" title="' . _l('task_related_to') . '" href="' . $link . '?group=patient_profile' .'">' . e($relName) . '</a>';
            }

            if ($aRow['recurring'] == 1) {
                $outputName .= '<br /><span class="label label-primary inline-block mtop4"> ' . _l('recurring_task') . '</span>';
            }

            $outputName .= '<div class="row-options">';

            $class = 'text-success bold';
            $style = '';

            $tooltip = '';
            if ($aRow['billed'] == 1 || !$aRow['is_assigned'] || $aRow['status'] == Tasks_model::STATUS_COMPLETE) {
                $class = 'text-dark disabled';
                $style = 'style="opacity:0.6;cursor: not-allowed;"';
                if ($aRow['status'] == Tasks_model::STATUS_COMPLETE) {
                    $tooltip = ' data-toggle="tooltip" data-title="' . e(format_task_status($aRow['status'], false, true)) . '"';
                } elseif ($aRow['billed'] == 1) {
                    $tooltip = ' data-toggle="tooltip" data-title="' . _l('task_billed_cant_start_timer') . '"';
                } elseif (!$aRow['is_assigned']) {
                    $tooltip = ' data-toggle="tooltip" data-title="' . _l('task_start_timer_only_assignee') . '"';
                }
            }

        //     if ($aRow['not_finished_timer_by_current_staff']) {
        //         $outputName .= '<a href="#" class="text-danger tasks-table-stop-timer" onclick="timer_action(this,' . $aRow['id'] . ',' . $aRow['not_finished_timer_by_current_staff'] . '); return false;">' . _l('task_stop_timer') . '</a>';
        //     } else {
        //         $outputName .= '<span' . $tooltip . ' ' . $style . '>
        // <a href="#" class="' . $class . ' tasks-table-start-timer" onclick="timer_action(this,' . $aRow['id'] . '); return false;">' . _l('task_start_timer') . '</a>
        // </span>';
        //     }

            if ($hasPermissionEdit) {
                $outputName .= '<span class="tw-text-neutral-300"> | </span><a href="#" onclick="edit_task(' . $aRow['id'] . '); return false">' . _l('edit') . '</a>';
            }

            if ($hasPermissionDelete) {
                $outputName .= '<span class="tw-text-neutral-300"> | </span><a href="' . admin_url('tasks/delete_task/' . $aRow['id']) . '" class="text-danger _delete task-delete">' . _l('delete') . '</a>';
            }
            $outputName .= '</div>';

            $row[] = $outputName;

            $canChangeStatus = ($aRow['current_user_is_creator'] != '0' || $aRow['current_user_is_assigned'] || staff_can('edit',  'tasks'));
            $status          = get_task_status_by_id($aRow['status']);
            $outputStatus    = '';

            $outputStatus .= '<span class="label" style="color:' . $status['color'] . ';border:1px solid ' . adjust_hex_brightness($status['color'], 0.4) . ';background: ' . adjust_hex_brightness($status['color'], 0.04) . ';" task-status-table="' . e($aRow['status']) . '">';

            $outputStatus .= e($status['name']);

            if ($canChangeStatus) {
                $outputStatus .= '<div class="dropdown inline-block mleft5 table-export-exclude">';
                $outputStatus .= '<a href="#" style="font-size:14px;vertical-align:middle;" class="dropdown-toggle text-dark" id="tableTaskStatus-' . $aRow['id'] . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
                $outputStatus .= '<span data-toggle="tooltip" title="' . _l('ticket_single_change_status') . '"><i class="fa-solid fa-chevron-down tw-opacity-70"></i></span>';
                $outputStatus .= '</a>';

                $outputStatus .= '<ul class="dropdown-menu dropdown-menu-right" aria-labelledby="tableTaskStatus-' . $aRow['id'] . '">';
                foreach ($task_statuses as $taskChangeStatus) {
                    if ($aRow['status'] != $taskChangeStatus['id']) {
                        $outputStatus .= '<li>
                  <a href="#" onclick="task_mark_as(' . $taskChangeStatus['id'] . ',' . $aRow['id'] . '); return false;">
                     ' . e(_l('task_mark_as', $taskChangeStatus['name'])) . '
                  </a>
               </li>';
                    }
                }
                $outputStatus .= '</ul>';
                $outputStatus .= '</div>';
            }

            $outputStatus .= '</span>';

            $row[] = $outputStatus;

            $row[] = e(_d($aRow['startdate']));

            $row[] = e(_d($aRow['duedate']));

            $row[] = format_members_by_ids_and_names($aRow['assignees_ids'], $aRow['assignees']);

            $row[] = render_tags($aRow['tags']);

            $outputPriority = '<span style="color:' . e(task_priority_color($aRow['priority'])) . ';" class="inline-block">' . e(task_priority($aRow['priority']));

            if (staff_can('edit',  'tasks') && $aRow['status'] != Tasks_model::STATUS_COMPLETE) {
                $outputPriority .= '<div class="dropdown inline-block mleft5 table-export-exclude">';
                $outputPriority .= '<a href="#" style="font-size:14px;vertical-align:middle;" class="dropdown-toggle text-dark" id="tableTaskPriority-' . $aRow['id'] . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
                $outputPriority .= '<span data-toggle="tooltip" title="' . _l('task_single_priority') . '"><i class="fa-solid fa-chevron-down tw-opacity-70"></i></span>';
                $outputPriority .= '</a>';

                $outputPriority .= '<ul class="dropdown-menu dropdown-menu-right" aria-labelledby="tableTaskPriority-' . $aRow['id'] . '">';
                foreach ($tasksPriorities as $priority) {
                    if ($aRow['priority'] != $priority['id']) {
                        $outputPriority .= '<li>
                  <a href="#" onclick="task_change_priority(' . $priority['id'] . ',' . $aRow['id'] . '); return false;">
                     ' . e($priority['name']) . '
                  </a>
               </li>';
                    }
                }
                $outputPriority .= '</ul>';
                $outputPriority .= '</div>';
            }

            $outputPriority .= '</span>';
            $row[] = $outputPriority;

            // Custom fields add values
            foreach ($customFieldsColumns as $customFieldColumn) {
                $row[] = (strpos($customFieldColumn, 'date_picker_') !== false ? _d($aRow[$customFieldColumn]) : $aRow[$customFieldColumn]);
            }

            $row['DT_RowClass'] = 'has-row-options';

            if ((!empty($aRow['duedate']) && $aRow['duedate'] < date('Y-m-d')) && $aRow['status'] != Tasks_model::STATUS_COMPLETE) {
                $row['DT_RowClass'] .= ' danger';
            }

            $row = hooks()->apply_filters('tasks_table_row_data', $row, $aRow);

            $output['aaData'][] = $row;
        }