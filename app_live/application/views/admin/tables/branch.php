<?php

defined('BASEPATH') or exit('No direct script access allowed');

return App_table::find('branch')
    ->outputUsing(function ($params) {
        extract($params);

        $hasPermissionDelete = staff_can('delete',  'branch');

        $custom_fields = get_table_custom_fields('branch');
        $this->ci->db->query("SET sql_mode = ''");

        $aColumns = [
            '1',
            db_prefix() . 'branch.branchid as branchid',
            'branch',
            'vat',
            'email',
            db_prefix() . 'branch.phonenumber as phonenumber',
            db_prefix() . 'branch.active',
            '(SELECT GROUP_CONCAT(name SEPARATOR ",") FROM ' . db_prefix() . 'customer_groups JOIN ' . db_prefix() . 'branch_groups ON ' . db_prefix() . 'customer_groups.groupid = ' . db_prefix() . 'branch_groups.id WHERE customer_id = ' . db_prefix() . 'branch.branchid ORDER by name ASC) as customerGroups',
            db_prefix() . 'branch.datecreated as datecreated',
        ];

        $sIndexColumn = 'branchid';
        $sTable       = db_prefix() . 'branch';
        $where        = [];

        if ($filtersWhere = $this->getWhereFromRules()) {
            $where[] = $filtersWhere;
        }

        $join = [
            // 'LEFT JOIN ' . db_prefix() . 'contacts ON ' . db_prefix() . 'contacts.branchid=' . db_prefix() . 'branch.branchid AND ' . db_prefix() . 'contacts.is_primary=1',
        ];

        foreach ($custom_fields as $key => $field) {
            $selectAs = (is_cf_date($field) ? 'date_picker_cvalue_' . $key : 'cvalue_' . $key);
            array_push($customFieldsColumns, $selectAs);
            array_push($aColumns, 'ctable_' . $key . '.value as ' . $selectAs);
            array_push($join, 'LEFT JOIN ' . db_prefix() . 'customfieldsvalues as ctable_' . $key . ' ON ' . db_prefix() . 'branch.branchid = ctable_' . $key . '.relid AND ctable_' . $key . '.fieldto="' . $field['fieldto'] . '" AND ctable_' . $key . '.fieldid=' . $field['id']);
        }

        $join = hooks()->apply_filters('branch_table_sql_join', $join);

        if (staff_cant('view', 'branch')) {
            array_push($where, 'AND ' . db_prefix() . 'branch.branchid IN (SELECT customer_id FROM ' . db_prefix() . 'customer_admins WHERE staff_id=' . get_staff_user_id() . ')');
        }

        $aColumns = hooks()->apply_filters('branch_table_sql_columns', $aColumns);

        // Fix for big queries. Some hosting have max_join_limit
        if (count($custom_fields) > 4) {
            @$this->ci->db->query('SET SQL_BIG_SELECTS=1');
        }

        $result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [
            db_prefix() . 'contacts.id as contact_id',
            'lastname',
            db_prefix() . 'branch.zip as zip',
            'registration_confirmed',
            'vat'
        ]);

        $output  = $result['output'];
        $rResult = $result['rResult'];

        foreach ($rResult as $aRow) {
            $row = [];

            // Bulk actions
            $row[] = '<div class="checkbox"><input type="checkbox" value="' . $aRow['branchid'] . '"><label></label></div>';
            // User id
            $row[] = $aRow['branchid'];

            // Company
            $company  = e($aRow['company']);
            $isPerson = false;

            if ($company == '') {
                $company  = _l('no_company_view_profile');
                $isPerson = true;
            }

            $url = admin_url('branch/client/' . $aRow['branchid']);

            if ($isPerson && $aRow['contact_id']) {
                $url .= '?contactid=' . $aRow['contact_id'];
            }

            $company = '<a href="' . $url . '">' . $company . '</a>';

            $company .= '<div class="row-options">';
            $company .= '<a href="' . admin_url('branch/client/' . $aRow['branchid'] . ($isPerson && $aRow['contact_id'] ? '?group=contacts' : '')) . '">' . _l('view') . '</a>';

            if ($aRow['registration_confirmed'] == 0 && is_admin()) {
                $company .= ' | <a href="' . admin_url('branch/confirm_registration/' . $aRow['branchid']) . '" class="text-success bold">' . _l('confirm_registration') . '</a>';
            }

            if (!$isPerson) {
                $company .= ' | <a href="' . admin_url('branch/client/' . $aRow['branchid'] . '?group=contacts') . '">' . _l('customer_contacts') . '</a>';
            }

            if ($hasPermissionDelete) {
                $company .= ' | <a href="' . admin_url('branch/delete/' . $aRow['branchid']) . '" class="text-danger _delete">' . _l('delete') . '</a>';
            }

            $company .= '</div>';

            $row[] = $company;

            // Primary contact
            $row[] = ($aRow['contact_id'] ? '<a href="' . admin_url('branch/client/' . $aRow['branchid'] . '?contactid=' . $aRow['contact_id']) . '" target="_blank">' . e(trim($aRow['fullname'])) . '</a>' : '');

            // Primary contact email
            $row[] = ($aRow['email'] ? '<a href="mailto:' . e($aRow['email']) . '">' . e($aRow['email']) . '</a>' : '');

            // Primary contact phone
            $row[] = ($aRow['phonenumber'] ? '<a href="tel:' . e($aRow['phonenumber']) . '">' . e($aRow['phonenumber']) . '</a>' : '');

            // Toggle active/inactive customer
            $toggleActive = '<div class="onoffswitch" data-toggle="tooltip" data-title="' . _l('customer_active_inactive_help') . '">
    <input type="checkbox"' . ($aRow['registration_confirmed'] == 0 ? ' disabled' : '') . ' data-switch-url="' . admin_url() . 'branch/change_client_status" name="onoffswitch" class="onoffswitch-checkbox" id="' . $aRow['branchid'] . '" data-id="' . $aRow['branchid'] . '" ' . ($aRow[db_prefix() . 'branch.active'] == 1 ? 'checked' : '') . '>
    <label class="onoffswitch-label" for="' . $aRow['branchid'] . '"></label>
    </div>';

            // For exporting
            $toggleActive .= '<span class="hide">' . ($aRow[db_prefix() . 'branch.active'] == 1 ? _l('is_active_export') : _l('is_not_active_export')) . '</span>';

            $row[] = $toggleActive;

            // Customer groups parsing
            $groupsRow = '';
            if ($aRow['customerGroups']) {
                $groups = explode(',', $aRow['customerGroups']);
                foreach ($groups as $group) {
                    $groupsRow .= '<span class="label label-default mleft5 customer-group-list pointer">' . e($group) . '</span>';
                }
            }

            $row[] = $groupsRow;

            $row[] = e(_dt($aRow['datecreated']));

            // Custom fields add values
            foreach ($customFieldsColumns as $customFieldColumn) {
                $row[] = (strpos($customFieldColumn, 'date_picker_') !== false ? _d($aRow[$customFieldColumn]) : $aRow[$customFieldColumn]);
            }

            $row['DT_RowClass'] = 'has-row-options';

            if ($aRow['registration_confirmed'] == 0) {
                $row['DT_RowClass'] .= ' info requires-confirmation';
                $row['Data_Title']  = _l('customer_requires_registration_confirmation');
                $row['Data_Toggle'] = 'tooltip';
            }

            if ($aRow[db_prefix().'branch.active'] == 0) {
                $row['DT_RowClass'] .= ' secondary';
            }
            
            $row = hooks()->apply_filters('branch_table_row_data', $row, $aRow);

            $output['aaData'][] = $row;
        }
        return $output;
    })->setRules([
        App_table_filter::new('phonenumber', 'TextRule')->label(_l('branch_phone')),
        App_table_filter::new('active', 'BooleanRule')->label(_l('customer_active')),
        App_table_filter::new('invoice_statuses', 'MultiSelectRule')->label(_l('invoices'))
            ->options(function ($ci) {
                $ci->load->model('invoices_model');
                return collect($ci->invoices_model->get_statuses())->map(fn ($status) => [
                    'value' => $status,
                    'label' =>  _l('customer_have_invoices_by', format_invoice_status($status, '', false))
                ]);
            })
            ->raw(function ($value, $operator, $sqlOperator) {
                return db_prefix() . 'branch.branchid IN (SELECT clientid FROM ' . db_prefix() . 'invoices WHERE status ' . $sqlOperator['operator'] . ' (' . implode(', ', $value) . '))';
            }),

        App_table_filter::new('estimate_statuses', 'MultiSelectRule')->label(_l('estimates'))
            ->options(function ($ci) {
                $ci->load->model('estimates_model');
                return collect($ci->estimates_model->get_statuses())->map(fn ($status) => [
                    'value' => $status,
                    'label' =>  _l('customer_have_estimates_by', format_estimate_status($status, '', false))
                ]);
            })
            ->raw(function ($value, $operator, $sqlOperator) {
                return db_prefix() . 'branch.branchid IN (SELECT clientid FROM ' . db_prefix() . 'estimates WHERE status ' . $sqlOperator['operator'] . ' (' . implode(', ', $value) . '))';
            }),

        App_table_filter::new('proposal_statuses', 'MultiSelectRule')->label(_l('proposals'))
            ->options(function ($ci) {
                $ci->load->model('proposals_model');
                return collect($ci->proposals_model->get_statuses())->map(fn ($status) => [
                    'value' => $status,
                    'label' =>  _l('customer_have_proposals_by', format_proposal_status($status, '', false))
                ]);
            })
            ->raw(function ($value, $operator, $sqlOperator) {
                return db_prefix() . 'branch.branchid IN (SELECT rel_id FROM ' . db_prefix() . 'proposals WHERE status ' . $sqlOperator['operator'] . ' (' . implode(', ', $value) . ') AND rel_type="customer")';
            }),

        App_table_filter::new('project_statuses', 'MultiSelectRule')->label(_l('projects'))
            ->options(function ($ci) {
                $ci->load->model('projects_model');
                return collect($ci->projects_model->get_project_statuses())->map(fn ($data) => [
                    'value' => $data['id'],
                    'label' => _l('customer_have_projects_by', $data['name'])
                ]);
            })->raw(function ($value, $operator, $sqlOperator) {
                return db_prefix() . 'branch.branchid IN (SELECT clientid FROM ' . db_prefix() . 'projects WHERE status ' . $sqlOperator['operator'] . ' (' . implode(', ', $value) . '))';
            }),

        App_table_filter::new('contracts_types', 'MultiSelectRule')->label(_l('contract_types'))
            ->options(function ($ci) {
                $ci->load->model('contracts_model');
                return collect($ci->contracts_model->get_contract_types())->map(fn ($data) => [
                    'value' => $data['id'],
                    'label' => _l('customer_have_contracts_by_type', $data['name'])
                ]);
            })
            ->raw(function ($value, $operator, $sqlOperator) {
                return   db_prefix() . 'branch.branchid IN (SELECT client FROM ' . db_prefix() . 'contracts WHERE contract_type ' . $sqlOperator['operator'] . ' (' . implode(', ', $value) . '))';
            }),
        App_table_filter::new('city', 'TextRule')->label(_l('branch_city')),
        App_table_filter::new('zip', 'TextRule')->label(_l('branch_zip')),
        App_table_filter::new('state', 'TextRule')->label(_l('branch_state')),
        App_table_filter::new('country', 'SelectRule')->label(_l('branch_country'))
            ->options(function ($ci) {
                return collect($ci->branch_model->get_branch_distinct_countries())->map(fn ($data) => [
                    'value' => $data['country_id'],
                    'label' => $data['short_name']
                ]);
            }),
        App_table_filter::new('customer_admins', 'MultiSelectRule')->label(_l('responsible_admin'))
            ->isVisible(fn () => staff_can('create', 'branch') || staff_can('edit', 'branch'))
            ->options(function ($ci) {
                return collect($ci->branch_model->get_branch_admin_unique_ids())->map(fn ($data) => [
                    'value' => $data['staff_id'],
                    'label' => get_staff_full_name($data['staff_id'])
                ]);
            })
            ->raw(function ($value, $operator, $sqlOperator) {
                return   db_prefix() . 'branch.branchid IN (SELECT customer_id FROM ' . db_prefix() . 'customer_admins WHERE staff_id ' . $sqlOperator['operator'] . ' (' . implode(', ', $value) . '))';
            }),
        App_table_filter::new('groups', 'MultiSelectRule')->label(_l('customer_groups'))
            ->options(function ($ci) {
                return collect($ci->branch_model->get_groups())->map(fn ($group) => [
                    'value' => $group['id'],
                    'label' => $group['name']
                ]);
            })->raw(function ($value, $operator, $sqlOperator) {
                return db_prefix() . 'branch.branchid IN (SELECT customer_id FROM ' . db_prefix() . 'customer_groups WHERE groupid ' . $sqlOperator['operator'] . ' (' . implode(', ', $value) . '))';
            }),
        App_table_filter::new('my_branch', 'BooleanRule')->label(_l('branch_assigned_to_me'))
            ->raw(function ($value) {
                return db_prefix() . 'branch.branchid ' . ($value == '1' ? 'IN' : 'NOT IN') . ' (SELECT customer_id FROM ' . db_prefix() . 'customer_admins WHERE staff_id=' . get_staff_user_id() . ')';
            }),
        App_table_filter::new('requires_confirmation', 'BooleanRule')
            ->label(_l('customer_requires_registration_confirmation'))
            ->raw(function ($value) {
                return db_prefix() . 'branch.registration_confirmed=' . ($value == '1' ? '0' : '1');
            }),
    ]);
