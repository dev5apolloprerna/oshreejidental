<?php

defined('BASEPATH') or exit('No direct script access allowed');

return App_table::find('global_patient_search')
    ->outputUsing(function ($params) {
        extract($params);

        $aColumns = [
            db_prefix() . 'clients.userid as userid',
            db_prefix() . 'contacts.uid as uid',
            'SUBSTRING_INDEX(' . db_prefix() . 'contacts.uid, "/", -1) as branch_code',
            'company',
            'CONCAT(' . db_prefix() . 'contacts.firstname, " ", ' . db_prefix() . 'contacts.lastname) as fullname',
            db_prefix() . 'contacts.email as email',
            db_prefix() . 'clients.phonenumber as phonenumber',
            db_prefix() . 'clients.datecreated as datecreated',
        ];

        $sIndexColumn = 'userid';
        $sTable       = db_prefix() . 'clients';
        $join         = [
            'LEFT JOIN ' . db_prefix() . 'contacts ON ' . db_prefix() . 'contacts.userid=' . db_prefix() . 'clients.userid AND ' . db_prefix() . 'contacts.is_primary=1',
        ];
        $where        = [];

        if (staff_cant('view', 'customers')) {
            $where[] = 'AND ' . db_prefix() . 'clients.userid IN (SELECT customer_id FROM ' . db_prefix() . 'customer_admins WHERE staff_id=' . get_staff_user_id() . ')';
        }

        $result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [
            db_prefix() . 'contacts.id as contact_id',
        ]);

        $output  = $result['output'];
        $rResult = $result['rResult'];

        foreach ($rResult as $aRow) {
            $row   = [];
            $url   = admin_url('clients/client/' . $aRow['userid'] . '?group=patient_profile');
            $cName = e($aRow['company']);

            $row[] = $aRow['userid'];
            $row[] = $aRow['uid'] ? '<a href="' . $url . '">' . e($aRow['uid']) . '</a>' : '';
            $row[] = $aRow['branch_code'] ? e($aRow['branch_code']) : '-';
            $row[] = $cName ? '<a href="' . $url . '">' . $cName . '</a>' : '-';
            $row[] = $aRow['fullname'] ? '<a href="' . $url . '">' . e(trim($aRow['fullname'])) . '</a>' : '-';
            $row[] = $aRow['email'] ? '<a href="mailto:' . e($aRow['email']) . '">' . e($aRow['email']) . '</a>' : '-';
            $row[] = $aRow['phonenumber'] ? '<a href="tel:' . e($aRow['phonenumber']) . '">' . e($aRow['phonenumber']) . '</a>' : '-';
            $row[] = e(_dt($aRow['datecreated']));

            $output['aaData'][] = $row;
        }

        return $output;
    })
    ->setRules([
        App_table_filter::new('uid', 'TextRule')->label('Patient ID'),
        App_table_filter::new('phonenumber', 'TextRule')->label(_l('clients_phone')),
        App_table_filter::new('branch_code', 'TextRule')->label('Branch'),
    ]);
