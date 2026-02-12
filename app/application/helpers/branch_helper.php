<?php

use app\services\CustomerProfileBadges;

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Check whether the contact email is verified
 * @since  2.2.0
 * @param  mixed  $id contact id
 * @return boolean
 */
function is_contact1_email_verified1($id = null)
{
    $id = !$id ? get_contact1_branch_id() : $id;

    if (isset($GLOBALS['contact']) && $GLOBALS['contact']->id == $id) {
        return !is_null($GLOBALS['contact']->email_verified_at);
    }

    $CI = &get_instance();

    $CI->db->select('email_verified_at');
    $CI->db->where('id', $id);
    $contact = $CI->db->get(db_prefix() . 'contacts')->row();

    if (!$contact) {
        return false;
    }

    return !is_null($contact->email_verified_at);
}

/**
 * Check whether the user disabled verification emails for contacts
 * @return boolean
 */
function is_email_verification_enabled1()
{
    return total_rows(db_prefix() . 'emailtemplates', ['slug' => 'contact-verification-email', 'active' => 0]) == 0;
}
/**
 * Check if branch id is used in the system
 * @param  mixed  $id branch id
 * @return boolean
 */
function is_branch_id_used($id)
{
    $total = 0;

    $checkCommonTables = [db_prefix() . 'subscriptions', db_prefix() . 'creditnotes', db_prefix() . 'projects', db_prefix() . 'invoices', db_prefix() . 'expenses', db_prefix() . 'estimates'];

    foreach ($checkCommonTables as $table) {
        $total += total_rows($table, [
            'branch' => $id,
        ]);
    }

    $total += total_rows(db_prefix() . 'contracts', [
        'branch' => $id,
    ]);

    $total += total_rows(db_prefix() . 'proposals', [
        'rel_id'   => $id,
        'rel_type' => 'branch',
    ]);

    $total += total_rows(db_prefix() . 'tickets', [
        'branchid' => $id,
    ]);

    $total += total_rows(db_prefix() . 'tasks', [
        'rel_id'   => $id,
        'rel_type' => 'branch',
    ]);

    return hooks()->apply_filters('is_branch_id_used', $total > 0 ? true : false, $id);
}
/**
 * Check if branch has subscriptions
 * @param  mixed $id branch id
 * @return boolean
 */
function branch_has_subscriptions($id)
{
    return hooks()->apply_filters('branch_has_subscriptions', total_rows(db_prefix() . 'subscriptions', ['branchid' => $id]) > 0);
}
/**
 * Get branch by ID or current queried branch
 * @param  mixed $id branch id
 * @return mixed
 */
function get_branch($id = null)
{
    $CI = &get_instance();
    // if (is_numeric($id)) {
    //     $this->db->where('branchid', $id);
    //     return $this->db->get(db_prefix() . 'branch')->row();
    // }
    return $CI->db->get(db_prefix() . 'branch')->result();
}
/**
 * Get predefined tabs array, used in branch profile
 * @return array
 */
function get_branch_profile_tabs()
{
    return get_instance()->app_tabs->get_branch_profile_tabs();
}

/**
 * Filter only visible tabs selected from the profile and add badge
 * @param  array $tabs available tabs
 * @param  int $id branch
 * @return array
 */
function filter_branch_visible_tabs($tabs, $id = '')
{
    $newTabs               = [];
    $branchProfileBadges = null;

    $visible = get_option('visible_branch_profile_tabs');
    if ($visible != 'all') {
        $visible = unserialize($visible);
    }

    if ($id !== '') {
        $branchProfileBadges = new CustomerProfileBadges($id);
    }

    $appliedSettings = is_array($visible);
    foreach ($tabs as $key => $tab) {

        // Check visibility from settings too
        // if ($key != 'profile' && $key != 'contacts' && $appliedSettings) {
        //     if (array_key_exists($key, $visible) && $visible[$key] == false) {
        //         continue;
        //     }
        // }

        if (!is_null($branchProfileBadges)) {
            $tab['badge'] = $branchProfileBadges->getBadge($tab['slug']);
        }

        $newTabs[$key] = $tab;
    }

    return hooks()->apply_filters('branch_filtered_visible_tabs', $newTabs);
}
/**
 * @todo
 * Find a way to get the branch_id inside this function or refactor the hook
 * @param  string $group the tabs groups
 * @return null
 */
function app_init_branch_profile_tabs()
{
    $branch_id = null;

    $remindersText = _l('branch_reminders_tab');

    if ($branch = get_branch()) {
        $branch_id = $branch->branchid;

        $total_reminders = total_rows(
            db_prefix() . 'reminders',
            [
                'isnotified' => 0,
                'staff'      => get_staff_branch_id(),
                'rel_type'   => 'branch',
                'rel_id'     => $branch_id,
            ]
        );

        if ($total_reminders > 0) {
            $remindersText .= ' <span class="badge">' . $total_reminders . '</span>';
        }
    }

    $CI = &get_instance();

    $CI->app_tabs->add_branch_profile_tab('profile', [
        'name'     => _l('branch_add_edit_profile'),
        'icon'     => 'fa fa-user-circle',
        'view'     => 'admin/branch/groups/profile',
        'position' => 5,
        'badge'    => [],
    ]);

    $CI->app_tabs->add_branch_profile_tab('contacts', [
        'name'     => !is_empty_branch($branch_id) || empty($branch_id) ? _l('branch_contacts') : _l('contact'),
        'icon'     => 'fa fa-user',
        'view'     => 'admin/branch/groups/contacts',
        'position' => 10,
        'badge'    => [],
    ]);

    $CI->app_tabs->add_branch_profile_tab('notes', [
        'name'     => _l('contracts_notes_tab'),
        'icon'     => 'fa-regular fa-note-sticky',
        'view'     => 'admin/branch/groups/notes',
        'position' => 15,
        'badge'    => [],
    ]);

    $CI->app_tabs->add_branch_profile_tab('statement', [
        'name'     => _l('branch_statement'),
        'icon'     => 'fa fa-area-chart',
        'view'     => 'admin/branch/groups/statement',
        'visible'  => staff_can('view',  'invoices'),
        'position' => 20,
        'badge'    => [],
    ]);

    $CI->app_tabs->add_branch_profile_tab('invoices', [
        'name'     => _l('branch_invoices_tab'),
        'icon'     => 'fa fa-file-text',
        'view'     => 'admin/branch/groups/invoices',
        'visible'  => (staff_can('view',  'invoices') || staff_can('view_own',  'invoices') || (get_option('allow_staff_view_invoices_assigned') == 1 && staff_has_assigned_invoices())),
        'position' => 25,
        'badge'    => [],
    ]);

    $CI->app_tabs->add_branch_profile_tab('payments', [
        'name'     => _l('branch_payments_tab'),
        'icon'     => 'fa fa-line-chart',
        'view'     => 'admin/branch/groups/payments',
        'visible'  => (staff_can('view',  'payments') || staff_can('view_own',  'invoices') || (get_option('allow_staff_view_invoices_assigned') == 1 && staff_has_assigned_invoices())),
        'position' => 30,
        'badge'    => [],
    ]);

    $CI->app_tabs->add_branch_profile_tab('proposals', [
        'name'     => _l('proposals'),
        'icon'     => 'fa-regular fa-file-powerpoint',
        'view'     => 'admin/branch/groups/proposals',
        'visible'  => (staff_can('view',  'proposals') || staff_can('view_own',  'proposals') || (get_option('allow_staff_view_proposals_assigned') == 1 && staff_has_assigned_proposals())),
        'position' => 35,
        'badge'    => [],
    ]);

    $CI->app_tabs->add_branch_profile_tab('credit_notes', [
        'name'     => _l('credit_notes'),
        'icon'     => 'fa-regular fa-file-lines',
        'view'     => 'admin/branch/groups/credit_notes',
        'visible'  => (staff_can('view',  'credit_notes') || staff_can('view_own',  'credit_notes')),
        'position' => 40,
        'badge'    => [],
    ]);

    $CI->app_tabs->add_branch_profile_tab('estimates', [
        'name'     => _l('estimates'),
        'icon'     => 'fa-regular fa-file',
        'view'     => 'admin/branch/groups/estimates',
        'visible'  => (staff_can('view',  'estimates') || staff_can('view_own',  'estimates') || (get_option('allow_staff_view_estimates_assigned') == 1 && staff_has_assigned_estimates())),
        'position' => 45,
        'badge'    => [],
    ]);

    $CI->app_tabs->add_branch_profile_tab('subscriptions', [
        'name'     => _l('subscriptions'),
        'icon'     => 'fa fa-repeat',
        'view'     => 'admin/branch/groups/subscriptions',
        'visible'  => (staff_can('view',  'subscriptions') || staff_can('view_own',  'subscriptions')),
        'position' => 50,
        'badge'    => [],
    ]);

    $CI->app_tabs->add_branch_profile_tab('expenses', [
        'name'     => _l('expenses'),
        'icon'     => 'fa-regular fa-file-lines',
        'view'     => 'admin/branch/groups/expenses',
        'visible'  => (staff_can('view',  'expenses') || staff_can('view_own',  'expenses')),
        'position' => 55,
        'badge'    => [],
    ]);

    $CI->app_tabs->add_branch_profile_tab('contracts', [
        'name'     => _l('contracts'),
        'icon'     => 'fa fa-file-contract',
        'view'     => 'admin/branch/groups/contracts',
        'visible'  => (staff_can('view',  'contracts') || staff_can('view_own',  'contracts')),
        'position' => 60,
        'badge'    => [],
    ]);

    $CI->app_tabs->add_branch_profile_tab('projects', [
        'name'     => _l('projects'),
        'icon'     => 'fa-solid fa-chart-gantt',
        'view'     => 'admin/branch/groups/projects',
        'position' => 65,
        'badge'    => [],
    ]);

    $CI->app_tabs->add_branch_profile_tab('tasks', [
        'name'     => _l('tasks'),
        'icon'     => 'fa fa-tasks',
        'view'     => 'admin/branch/groups/tasks',
        'position' => 70,
        'badge'    => [],
    ]);

    $CI->app_tabs->add_branch_profile_tab('tickets', [
        'name'     => _l('tickets'),
        'icon'     => 'fa-regular fa-life-ring',
        'view'     => 'admin/branch/groups/tickets',
        'visible'  => ((get_option('access_tickets_to_none_staff_members') == 1 && !is_staff_member()) || is_staff_member()),
        'position' => 75,
        'badge'    => [],
    ]);

    $CI->app_tabs->add_branch_profile_tab('attachments', [
        'name'     => _l('branch_attachments'),
        'icon'     => 'fa fa-paperclip',
        'view'     => 'admin/branch/groups/attachments',
        'position' => 80,
        'badge'    => [],
    ]);

    $CI->app_tabs->add_branch_profile_tab('vault', [
        'name'     => _l('vault'),
        'icon'     => 'fa fa-lock',
        'view'     => 'admin/branch/groups/vault',
        'position' => 85,
        'badge'    => [],
    ]);

    $CI->app_tabs->add_branch_profile_tab('reminders', [
        'name'     => $remindersText,
        'icon'     => 'fa-regular fa-clock',
        'view'     => 'admin/branch/groups/reminders',
        'position' => 90,
        'badge'    => [],
    ]);

    $CI->app_tabs->add_branch_profile_tab('map', [
        'name'     => _l('branch_map'),
        'icon'     => 'fa-solid fa-location-dot',
        'view'     => 'admin/branch/groups/map',
        'position' => 95,
        'badge'    => [],
    ]);
}

/**
 * Get branch id by lead id
 * @since  Version 1.0.1
 * @param  mixed $id lead id
 * @return mixed     branch id
 */
function get_branch_id_by_lead_id($id)
{
    $CI = &get_instance();
    $CI->db->select('branchid')->from(db_prefix() . 'branch')->where('leadid', $id);

    return $CI->db->get()->row()->branchid;
}



/**
 * Check if branch have invoices with multiple currencies
 * @return booelan
 */
function is_branch_using_multiple_currencies($branchid = '', $table = null)
{
    if (!$table) {
        $table = db_prefix() . 'invoices';
    }

    $CI = &get_instance();

    $branchid = $branchid == '' ? get_branch_branch_id() : $branchid;
    $CI->load->model('currencies_model');
    $currencies            = $CI->currencies_model->get();
    $total_currencies_used = 0;
    foreach ($currencies as $currency) {
        $CI->db->where('currency', $currency['id']);
        $CI->db->where('branchid', $branchid);
        $total = $CI->db->count_all_results($table);
        if ($total > 0) {
            $total_currencies_used++;
        }
    }

    $retVal = true;
    if ($total_currencies_used > 1) {
        $retVal = true;
    } elseif ($total_currencies_used == 0 || $total_currencies_used == 1) {
        $retVal = false;
    }

    return hooks()->apply_filters('is_branch_using_multiple_currencies', $retVal, [
        'branch_id' => $branchid,
        'table'     => $table,
    ]);
}


/**
 * Function used to check if is really empty branch branch
 * Can happen user to have selected that the branch field is not required and the primary contact name is auto added in the branch field
 * @param  mixed  $id
 * @return boolean
 */
function is_empty_branch($id)
{
    $CI = &get_instance();
    $CI->db->select('branch');
    $CI->db->from(db_prefix() . 'branch');
    $CI->db->where('branchid', $id);
    $row = $CI->db->get()->row();
    if ($row) {
        if ($row->branch == '') {
            return true;
        }

        return false;
    }

    return true;
}

/**
 * Get ids to check what files with contacts are shared
 * @param  array  $where
 * @return array
 */
function get_branch_profile_file_sharing($where = [])
{
    $CI = &get_instance();
    $CI->db->where($where);

    return $CI->db->get(db_prefix() . 'shared_branch_files')->result_array();
}

/**
 * Get branch id by passed contact id
 * @param  mixed $id
 * @return mixed
 */
function get_branch_id_by_contact1_id($id)
{
    $CI = &get_instance();

    $branchid = $CI->app_object_cache->get('user-id-by-contact-id-' . $id);
    if (!$branchid) {
        $CI->db->select('branchid')
            ->where('id', $id);
        $branch = get(db_prefix() . 'contacts')->row();

        if ($branch) {
            $branchid = $branch->branchid;
            $CI->app_object_cache->add('user-id-by-contact-id-' . $id, $branchid);
        }
    }

    return $branchid;
}

/**
 * Get primary contact user id for specific branch
 * @param  mixed $branchid
 * @return mixed
 */
function get_primary_contact1_branch_id($branchid)
{
    $CI = &get_instance();
    $CI->db->where('branchid', $branchid);
    $CI->db->where('is_primary', 1);
    $row = $CI->db->get(db_prefix() . 'contacts')->row();

    if ($row) {
        return $row->id;
    }

    return false;
}


/**
 * Return contact profile image url
 * @param  mixed $contact_id
 * @param  string $type
 * @return string
 */
function contact_profile_image_url1 ($contact_id, $type = 'small')
{
    $url  = base_url('assets/images/user-placeholder.jpg');
    $CI   = &get_instance();
    $path = $CI->app_object_cache->get('contact-profile-image-path-' . $contact_id);

    if (!$path) {
        $CI->app_object_cache->add('contact-profile-image-path-' . $contact_id, $url);

        $CI->db->select('profile_image');
        $CI->db->from(db_prefix() . 'contacts');
        $CI->db->where('id', $contact_id);
        $contact = $CI->db->get()->row();

        if ($contact && !empty($contact->profile_image)) {
            $path = 'uploads/branch_profile_images/' . $contact_id . '/' . $type . '_' . $contact->profile_image;
            $CI->app_object_cache->set('contact-profile-image-path-' . $contact_id, $path);
        }
    }

    if ($path && file_exists($path)) {
        $url = base_url($path);
    }

    return $url;
}
/**
 * Used in:
 * Search contact tickets
 * Project dropdown quick switch
 * Calendar tooltips
 * @param  [type] $branchid [description]
 * @return [type]         [description]
 */
function get_branch_name($branchid, $prevent_empty_branch = false)
{
    $_branchid = get_branch_branch_id();
    if ($branchid !== '') {
        $_branchid = $branchid;
    }
    $CI = &get_instance();

    $select = ($prevent_empty_branch == false ? get_sql_select_branch_branch() : 'branch');

    $branch = $CI->db->select($select)
        ->where('branchid', $_branchid)
        ->from(db_prefix() . 'branch')
        ->get()
        ->row();
    if ($branch) {
        return $branch->branch;
    }

    return '';
}


/**
 * Get branch default language
 * @param  mixed $branchid
 * @return mixed
 */
function get_branch_default_language($branchid = '')
{
    if (!is_numeric($branchid)) {
        $branchid = get_branch_branch_id();
    }

    $CI = &get_instance();
    $CI->db->select('default_language');
    $CI->db->from(db_prefix() . 'branch');
    $CI->db->where('branchid', $branchid);
    $branch = $CI->db->get()->row();
    if ($branch) {
        return $branch->default_language;
    }

    return '';
}

/**
 * Function is branch admin
 * @param  mixed  $id       branch id
 * @param  staff_id  $staff_id staff id to check
 * @return boolean
 */
function is_branch_admin($id, $staff_id = '')
{
    $staff_id = is_numeric($staff_id) ? $staff_id : get_staff_branch_id();
    $CI       = &get_instance();
    $cache    = $CI->app_object_cache->get($id . '-is-branch-admin-' . $staff_id);

    if ($cache) {
        return $cache['retval'];
    }

    $total = total_rows(db_prefix() . 'branch_admins', [
        'branch_id' => $id,
        'staff_id'    => $staff_id,
    ]);

    $retval = $total > 0 ? true : false;
    $CI->app_object_cache->add($id . '-is-branch-admin-' . $staff_id, ['retval' => $retval]);

    return $retval;
}
/**
 * Check if staff member have assigned branch
 * @param  mixed $staff_id staff id
 * @return boolean
 */
function have_assigned_branch($staff_id = '')
{
    $CI       = &get_instance();
    $staff_id = is_numeric($staff_id) ? $staff_id : get_staff_branch_id();
    $cache    = $CI->app_object_cache->get('staff-total-assigned-branch-' . $staff_id);

    if (is_numeric($cache)) {
        $result = $cache;
    } else {
        $result = total_rows(db_prefix() . 'branch_admins', [
            'staff_id' => $staff_id,
        ]);
        $CI->app_object_cache->add('staff-total-assigned-branch-' . $staff_id, $result);
    }

    return $result > 0 ? true : false;
}
/**
 * Check if contact has permission
 * @param  string  $permission permission name
 * @param  string  $contact_id     contact id
 * @return boolean
 */
function has_contact1_permission1($permission, $contact_id = '')
{
    $CI = &get_instance();

    if (!class_exists('app')) {
        $CI->load->library('app');
    }

    $permissions = get_contact1_permissions();

    if (empty($contact_id)) {
        $contact_id = get_contact1_branch_id();
    }

    foreach ($permissions as $_permission) {
        if ($_permission['short_name'] == $permission) {
            return total_rows(db_prefix() . 'contact_permissions', [
                'permission_id' => $_permission['id'],
                'branchid'        => $contact_id,
            ]) > 0;
        }
    }

    return false;
}
/**
 * Load branch area language
 * @param  string $branch_id
 * @return string return loaded language
 */
function load_branch_language($branch_id = '')
{
    $CI = &get_instance();
    if (!$CI->load->is_loaded('cookie')) {
        $CI->load->helper('cookie');
    }

    if (defined('branchS_AREA') && get_contact1_language() != '' && !is_language_disabled()) {
        $language = get_contact1_language();
    } else {
        $language = get_option('active_language');

        if ((is_branch_logged_in() || $branch_id != '') && !is_language_disabled()) {
            $branch_language = get_branch_default_language($branch_id);

            if (!empty($branch_language)
                && file_exists(APPPATH . 'language/' . $branch_language)) {
                $language = $branch_language;
            }
        }

        // set_contact1_language($language);
    }

    $CI->lang->is_loaded = [];
    $CI->lang->language  = [];

    $CI->lang->load($language . '_lang', $language);
    load_custom_lang_file($language);

    $GLOBALS['language'] = $language;

    $GLOBALS['locale'] = get_locale_key($language);

    $CI->lang->set_last_loaded_language($language);

    hooks()->do_action('after_load_branch_language', $language);

    return $language;
}
/**
 * Check if branch have transactions recorded
 * @param  mixed $id branchid
 * @return boolean
 */
function branch_have_transactions($id)
{
    $total = 0;

    foreach ([db_prefix() . 'invoices', db_prefix() . 'creditnotes', db_prefix() . 'estimates'] as $table) {
        $total += total_rows($table, [
            'branchid' => $id,
        ]);
    }

    $total += total_rows(db_prefix() . 'expenses', [
        'branchid' => $id,
        'billable' => 1,
    ]);

    $total += total_rows(db_prefix() . 'proposals', [
        'rel_id'   => $id,
        'rel_type' => 'branch',
    ]);

    return hooks()->apply_filters('branch_have_transactions', $total > 0, $id);
}


/**
 * Predefined contact permission
 * @return array
 */
function get_contact1_permissions()
{
    $permissions = [
        [
            'id'         => 1,
            'name'       => _l('branch_permission_invoice'),
            'short_name' => 'invoices',
        ],
        [
            'id'         => 2,
            'name'       => _l('branch_permission_estimate'),
            'short_name' => 'estimates',
        ],
        [
            'id'         => 3,
            'name'       => _l('branch_permission_contract'),
            'short_name' => 'contracts',
        ],
        [
            'id'         => 4,
            'name'       => _l('branch_permission_proposal'),
            'short_name' => 'proposals',
        ],
        [
            'id'         => 5,
            'name'       => _l('branch_permission_support'),
            'short_name' => 'support',
        ],
        [
            'id'         => 6,
            'name'       => _l('branch_permission_projects'),
            'short_name' => 'projects',
        ],
    ];

    return hooks()->apply_filters('get_contact1_permissions', $permissions);
}

function get_contact1_permission($name)
{
    $permissions = get_contact1_permissions();

    foreach ($permissions as $permission) {
        if ($permission['short_name'] == $name) {
            return $permission;
        }
    }

    return false;
}

/**
 * Additional checking for branch area, when contact edit his profile
 * This function will check if the checkboxes for email notifications should be shown
 * @return boolean
 */
function can_contact1_view_email_notifications_options()
{
    return has_contact1_permission('invoices')
        || has_contact1_permission('estimates')
        || has_contact1_permission('projects')
        || has_contact1_permission('contracts');
}

/**
 * With this function staff can login as branch in the branch area
 * @param  mixed $id branch id
 */
function login_as_branch($id)
{
    $CI = &get_instance();

    $CI->db->select(db_prefix() . 'contacts.id, active')
        ->where('branchid', $id)
        ->where('is_primary', 1);

    $primary = $CI->db->get(db_prefix() . 'contacts')->row();

    if (!$primary) {
        set_alert('danger', _l('no_primary_contact'));
        redirect(previous_url() ?: $_SERVER['HTTP_REFERER']);
    } elseif ($primary->active == '0') {
        set_alert('danger', 'Customer primary contact is not active, please set the primary contact as active in order to login as branch');
        redirect(previous_url() ?: $_SERVER['HTTP_REFERER']);
    }

    $CI->load->model('announcements_model');
    $CI->announcements_model->set_announcements_as_read_except_last_one($primary->id);

    $user_data = [
        'branch_branch_id'      => $id,
        'contact_branch_id'     => $primary->id,
        'branch_logged_in'    => true,
        'logged_in_as_branch' => true,
    ];

    $CI->session->set_userdata($user_data);
}

function send_branch_registered_email_to_administrators($branch_id)
{
    $CI = &get_instance();
    $CI->load->model('staff_model');
    $admins = $CI->staff_model->get('', ['active' => 1, 'admin' => 1]);

    foreach ($admins as $admin) {
        send_mail_template('branch_new_registration_to_admins', $admin['email'], $branch_id, $admin['staffid']);
    }
}

/**
 * Return and perform additional checkings for contact consent url
 * @param  mixed $contact_id contact id
 * @return string
 */
function contact_consent_url1($contact_id)
{
    $CI = &get_instance();

    $consent_key = get_contact1_meta($contact_id, 'consent_key');

    if (empty($consent_key)) {
        $consent_key = app_generate_hash() . '-' . app_generate_hash();
        $meta_id     = false;
        if (total_rows(db_prefix() . 'contacts', ['id' => $contact_id]) > 0) {
            $meta_id = add_contact1_meta($contact_id, 'consent_key', $consent_key);
        }
        if (!$meta_id) {
            return '';
        }
    }

    return site_url('consent/contact/' . $consent_key);
}

/**
 *  Get branch attachment
 * @param   mixed $id   branch id
 * @return  array
 */
function get_all_branch_attachments($id)
{
    $CI = &get_instance();

    $attachments                = [];
    $attachments['invoice']     = [];
    $attachments['estimate']    = [];
    $attachments['credit_note'] = [];
    $attachments['proposal']    = [];
    $attachments['contract']    = [];
    $attachments['lead']        = [];
    $attachments['task']        = [];
    $attachments['branch']    = [];
    $attachments['ticket']      = [];
    $attachments['expense']     = [];

    $has_permission_expenses_view = staff_can('view',  'expenses');
    $has_permission_expenses_own  = staff_can('view_own',  'expenses');
    if ($has_permission_expenses_view || $has_permission_expenses_own) {
        // Expenses
        $CI->db->select('branchid,id');
        $CI->db->where('branchid', $id);
        if (!$has_permission_expenses_view) {
            $CI->db->where('addedfrom', get_staff_branch_id());
        }

        $CI->db->from(db_prefix() . 'expenses');
        $expenses = $CI->db->get()->result_array();
        $ids      = array_column($expenses, 'id');
        if (count($ids) > 0) {
            $CI->db->where_in('rel_id', $ids);
            $CI->db->where('rel_type', 'expense');
            $_attachments = $CI->db->get(db_prefix() . 'files')->result_array();
            foreach ($_attachments as $_att) {
                array_push($attachments['expense'], $_att);
            }
        }
    }


    $has_permission_invoices_view = staff_can('view',  'invoices');
    $has_permission_invoices_own  = staff_can('view_own',  'invoices');
    if ($has_permission_invoices_view || $has_permission_invoices_own || get_option('allow_staff_view_invoices_assigned') == 1) {
        $noPermissionQuery = get_invoices_where_sql_for_staff(get_staff_branch_id());
        // Invoices
        $CI->db->select('branchid,id');
        $CI->db->where('branchid', $id);

        if (!$has_permission_invoices_view) {
            $CI->db->where($noPermissionQuery);
        }

        $CI->db->from(db_prefix() . 'invoices');
        $invoices = $CI->db->get()->result_array();

        $ids = array_column($invoices, 'id');
        if (count($ids) > 0) {
            $CI->db->where_in('rel_id', $ids);
            $CI->db->where('rel_type', 'invoice');
            $_attachments = $CI->db->get(db_prefix() . 'files')->result_array();
            foreach ($_attachments as $_att) {
                array_push($attachments['invoice'], $_att);
            }
        }
    }

    $has_permission_credit_notes_view = staff_can('view',  'credit_notes');
    $has_permission_credit_notes_own  = staff_can('view_own',  'credit_notes');

    if ($has_permission_credit_notes_view || $has_permission_credit_notes_own) {
        // credit_notes
        $CI->db->select('branchid,id');
        $CI->db->where('branchid', $id);

        if (!$has_permission_credit_notes_view) {
            $CI->db->where('addedfrom', get_staff_branch_id());
        }

        $CI->db->from(db_prefix() . 'creditnotes');
        $credit_notes = $CI->db->get()->result_array();

        $ids = array_column($credit_notes, 'id');
        if (count($ids) > 0) {
            $CI->db->where_in('rel_id', $ids);
            $CI->db->where('rel_type', 'credit_note');
            $_attachments = $CI->db->get(db_prefix() . 'files')->result_array();
            foreach ($_attachments as $_att) {
                array_push($attachments['credit_note'], $_att);
            }
        }
    }

    $permission_estimates_view = staff_can('view',  'estimates');
    $permission_estimates_own  = staff_can('view_own',  'estimates');

    if ($permission_estimates_view || $permission_estimates_own || get_option('allow_staff_view_proposals_assigned') == 1) {
        $noPermissionQuery = get_estimates_where_sql_for_staff(get_staff_branch_id());
        // Estimates
        $CI->db->select('branchid,id');
        $CI->db->where('branchid', $id);
        if (!$permission_estimates_view) {
            $CI->db->where($noPermissionQuery);
        }
        $CI->db->from(db_prefix() . 'estimates');
        $estimates = $CI->db->get()->result_array();

        $ids = array_column($estimates, 'id');
        if (count($ids) > 0) {
            $CI->db->where_in('rel_id', $ids);
            $CI->db->where('rel_type', 'estimate');
            $_attachments = $CI->db->get(db_prefix() . 'files')->result_array();

            foreach ($_attachments as $_att) {
                array_push($attachments['estimate'], $_att);
            }
        }
    }

    $has_permission_proposals_view = staff_can('view',  'proposals');
    $has_permission_proposals_own  = staff_can('view_own',  'proposals');

    if ($has_permission_proposals_view || $has_permission_proposals_own || get_option('allow_staff_view_proposals_assigned') == 1) {
        $noPermissionQuery = get_proposals_sql_where_staff(get_staff_branch_id());
        // Proposals
        $CI->db->select('rel_id,id');
        $CI->db->where('rel_id', $id);
        $CI->db->where('rel_type', 'branch');
        if (!$has_permission_proposals_view) {
            $CI->db->where($noPermissionQuery);
        }
        $CI->db->from(db_prefix() . 'proposals');
        $proposals = $CI->db->get()->result_array();

        $ids = array_column($proposals, 'id');

        if (count($ids) > 0) {
            $CI->db->where_in('rel_id', $ids);
            $CI->db->where('rel_type', 'proposal');
            $_attachments = $CI->db->get(db_prefix() . 'files')->result_array();

            foreach ($_attachments as $_att) {
                array_push($attachments['proposal'], $_att);
            }
        }
    }

    $permission_contracts_view = staff_can('view',  'contracts');
    $permission_contracts_own  = staff_can('view_own',  'contracts');
    if ($permission_contracts_view || $permission_contracts_own) {
        // Contracts
        $CI->db->select('branch,id');
        $CI->db->where('branch', $id);
        if (!$permission_contracts_view) {
            $CI->db->where('addedfrom', get_staff_branch_id());
        }
        $CI->db->from(db_prefix() . 'contracts');
        $contracts = $CI->db->get()->result_array();

        $ids = array_column($contracts, 'id');

        if (count($ids) > 0) {
            $CI->db->where_in('rel_id', $ids);
            $CI->db->where('rel_type', 'contract');
            $_attachments = $CI->db->get(db_prefix() . 'files')->result_array();

            foreach ($_attachments as $_att) {
                array_push($attachments['contract'], $_att);
            }
        }
    }

    $CI->db->select('leadid')
        ->where('branchid', $id);
    $branch = $CI->db->get(db_prefix() . 'branch')->row();

    if ($branch->leadid != null) {
        $CI->db->where('rel_id', $branch->leadid);
        $CI->db->where('rel_type', 'lead');
        $_attachments = $CI->db->get(db_prefix() . 'files')->result_array();
        foreach ($_attachments as $_att) {
            array_push($attachments['lead'], $_att);
        }
    }

    $CI->db->select('ticketid,branchid');
    $CI->db->where('branchid', $id);
    $CI->db->from(db_prefix() . 'tickets');
    $tickets = $CI->db->get()->result_array();

    $ids = array_column($tickets, 'ticketid');

    if (count($ids) > 0) {
        $CI->db->where_in('ticketid', $ids);
        $_attachments = $CI->db->get(db_prefix() . 'ticket_attachments')->result_array();

        foreach ($_attachments as $_att) {
            array_push($attachments['ticket'], $_att);
        }
    }

    $has_permission_tasks_view = staff_can('view',  'tasks');
    $noPermissionQuery         = get_tasks_where_string(false);
    $CI->db->select('rel_id, id');
    $CI->db->where('rel_id', $id);
    $CI->db->where('rel_type', 'branch');

    if (!$has_permission_tasks_view) {
        $CI->db->where($noPermissionQuery);
    }

    $CI->db->from(db_prefix() . 'tasks');
    $tasks = $CI->db->get()->result_array();

    $ids = array_column($tasks, 'ticketid');
    if (count($ids) > 0) {
        $CI->db->where_in('rel_id', $ids);
        $CI->db->where('rel_type', 'task');

        $_attachments = $CI->db->get(db_prefix() . 'files')->result_array();

        foreach ($_attachments as $_att) {
            array_push($attachments['task'], $_att);
        }
    }

    $CI->db->where('rel_id', $id);
    $CI->db->where('rel_type', 'branch');
    $branch_main_attachments = $CI->db->get(db_prefix() . 'files')->result_array();

    $attachments['branch'] = $branch_main_attachments;

    return hooks()->apply_filters('all_branch_attachments', $attachments, $id);
}

/**
 * Used in branch profile vaults feature to determine if the vault should be shown for staff
 * @param  array $entries vault entries from database
 * @return array
 */
function _check_vault_entries_visibility1($entries)
{
    $new = [];
    foreach ($entries as $entry) {
        if ($entry['visibility'] != 1) {
            if ($entry['visibility'] == 2 && !is_admin() && $entry['creator'] != get_staff_branch_id()) {
                continue;
            } elseif ($entry['visibility'] == 3 && $entry['creator'] != get_staff_branch_id() && !is_admin()) {
                continue;
            }
        }
        $new[] = $entry;
    }

    if (count($new) == 0) {
        $new = -1;
    }

    return $new;
}
/**
 * Default SQL select for selecting the branch
 *
 * @param string $as
 *
 * @return string
 */
function get_sql_select_branch_branch($as = 'branch')
{
    return 'CASE ' . db_prefix() . 'branch.branch WHEN \' \' THEN (SELECT CONCAT(firstname, \' \', lastname) FROM ' . db_prefix() . 'contacts WHERE branchid = ' . db_prefix() . 'branch.branchid and is_primary = 1) ELSE ' . db_prefix() . 'branch.branch END as ' . $as;
}

/**
 * @since  2.7.0
 * check if logged in user can manage contacts
 * @return boolean
 */
function can_loggged_in_user_manage_contacts1()
{
    $id      = get_contact1_branch_id();
    $contact = get_instance()->branch_model->get_contact($id);

    if (($contact->is_primary != 1) || (get_option('allow_primary_contact1_to_manage_other_contacts') != 1)) {
        return false;
    }

    if (is_individual_branch()) {
        return false;
    }

    return true;
}

/**
 * @since  2.7.0
 * check if logged in branch is an individual
 * @return boolean
 */
function is_individual_branch()
{
    return is_empty_branch(get_branch_branch_id())
        && total_rows(db_prefix() . 'contacts', ['branchid' => get_branch_branch_id()]) == 1;
}

/**
 * @since  2.7.0
 * Set logged in contact language
 * @return void
 */
function set_contact1_language($lang, $duration = 60 * 60 * 24 * 31 * 3)
{
    set_cookie('contact_language', $lang, $duration);
}

/**
 * @since  2.7.0
 * get logged in contact language
 * @return string
 */
function get_contact1_language()
{
    if (!is_null(get_cookie('contact_language'))) {
        return get_cookie('contact_language');
    }

    return '';
}

/**
 * @since  2.9.0
 *
 * Indicates whether the contact automatically
 * appended calling codes feature is enabled based on the
 * branch selected country
 *
 * @return boolean
 */
function is_automatic_calling_codes_enabled1()
{
    return hooks()->apply_filters('automatic_calling_codes_enabled', true);
}

/**
 * @since 3.1.2
 * 
 * Get the required fields for registration.
 * 
 * @return array 
 */
function get_required_fields_for_registration1() {
    $option = get_option('required_register_fields');

    $required = $option ? json_decode($option) : [];

    return [
        'contact'=> [
            'contact_firstname'=>['label' => _l('branch_firstname'), 'is_required' => true, 'disabled' => true],
            'contact_lastname'=>['label' => _l('branch_lastname'), 'is_required' => true, 'disabled' => true],
            'contact_email'=>['label' => _l('branch_email'), 'is_required' => true, 'disabled' => true],
            'contact_contact1_phonenumber'=>['label' => _l('branch_phone'), 'is_required' => in_array('contact_contact1_phonenumber', $required), 'disabled' => false],
            'contact_website'=>['label' => _l('branch_website'), 'is_required' => in_array('contact_website', $required), 'disabled' => false],
            'contact_title'=>['label' => _l('contact_position'), 'is_required' => in_array('contact_title', $required), 'disabled' => false],
        ],
        'branch'=> [
            'branch_branch'=>['label' => _l('branch_branch'), 'is_required' => (bool) get_option('branch_is_required'), 'disabled' => true],
            'branch_vat'=>['label' => _l('branch_vat'), 'is_required' => in_array('branch_vat', $required), 'disabled' => false],
            'branch_phonenumber'=>['label' => _l('branch_phone'), 'is_required' => in_array('branch_phonenumber', $required), 'disabled' => false],
            'branch_country'=>['label' => _l('branch_country'), 'is_required' => in_array('branch_country', $required), 'disabled' => false],
            'branch_city'=>['label' => _l('branch_city'), 'is_required' => in_array('branch_city', $required), 'disabled' => false],
            'branch_address'=>['label' => _l('branch_address'), 'is_required' => in_array('branch_address', $required), 'disabled' => false],
            'branch_zip'=>['label' => _l('branch_zip'), 'is_required' => in_array('branch_zip', $required), 'disabled' => false],
            'branch_state'=>['label' => _l('branch_state'), 'is_required' => in_array('branch_state', $required), 'disabled' => false],
        ],
    ];
}