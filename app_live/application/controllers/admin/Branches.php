<?php
defined('BASEPATH') or exit('No direct script access allowed');
class Branches extends AdminController
{
    function __construct(){
        parent::__construct();
        $this->load->model('branch_model');
        $this->load->model('branch_model');
        $this->load->helper('branch');
    }
    public function index()
    {
        if (staff_cant('view', 'branch')) {
            if (!have_assigned_customers() && staff_cant('create', 'branch')) {
                access_denied('branch');
            }
        }
        $data['groups']         = $this->branch_model->get_groups();
        $data['table'] = App_table::find('branches');
        // printrx($data['table']->id());
        $this->load->view('admin/branch/manage', $data);
    }

    /* Ajax call for load data table */
    public function table()
    {
        // if (staff_cant('view', 'branch')) {
        //     if (!have_assigned_customers() && staff_cant('create', 'branch')) {
        //         ajax_access_denied();
        //     }
        // }
        App_table::find('clients')->output();
    }

    /* save data and load form for insert record*/
    public function branch($id ='')
    {
        if ($this->input->post() && !$this->input->is_ajax_request()) {
            if ($id == '') {
                if (staff_cant('create', 'customers')) {
                    access_denied('customers');
                }

                $data = $this->input->post();
                $save_and_add_contact = false;
                if (isset($data['save_and_add_contact'])) {
                    unset($data['save_and_add_contact']);
                    $save_and_add_contact = true;
                }
                $id = $this->branch_model->add($data);
                if (staff_cant('view', 'customers')) {
                    $assign['customer_admins']   = [];
                    $assign['customer_admins'][] = get_staff_user_id();
                    $this->branch_model->assign_admins($assign, $id);
                }
                if ($id) {
                    set_alert('success', _l('added_successfully', _l('branch')));
                    if ($save_and_add_contact == false) {
                        redirect(admin_url('branches/branch/' . $id));
                    } else {
                        redirect(admin_url('branches/branch/' . $id . '?group=branch&new_branch=true'));
                    }
                }
            } else {
                    if (staff_cant('edit', 'customers')) {
                        if (!is_customer_admin($id)) {
                            access_denied('customers');
                        }
                    }
                    $success = $this->branch_model->update($this->input->post(), $id);
                    if ($success == true) {
                        set_alert('success', _l('updated_successfully', _l('branch')));
                    }
                    redirect(admin_url('branches/branch/' . $id));
            }
        }

        $group         = !$this->input->get('group') ? 'profile' : $this->input->get('group');
        $data['group'] = $group;

        // if ($group != 'contacts' && $contact_id = $this->input->get('contactid')) {
        //     redirect(admin_url('branches/branch/' . $id . '?group=contacts&contactid=' . $contact_id));
        // }

        // Customer groups
        $data['groups'] = $this->branch_model->get_groups();

        if ($id == '') {
            $title = _l('add_new', _l('branch_lowercase'));
        } else {
            $branch                = $this->branch_model->get($id);
            $data['branch_tabs'] = get_customer_profile_tabs($id);
            // printrx($this->db->last_query());
            // printrx($data);
            if (!$branch) {
                show_404();
            }

            $data['tab']      = isset($data['branch_tabs'][$group]) ? $data['branch_tabs'][$group] : null;

            if (!$data['tab']) {
                show_404();
            }

            // Fetch data based on groups
            if ($group == 'profile') {
                $data['customer_groups'] = $this->branch_model->get_customer_groups($id);
                $data['customer_admins'] = $this->branch_model->get_admins($id);
            } elseif ($group == 'attachments') {
                $data['attachments'] = get_all_customer_attachments($id);
            } elseif ($group == 'vault') {
                $data['vault_entries'] = hooks()->apply_filters('check_vault_entries_visibility', $this->branch_model->get_vault_entries($id));

                if ($data['vault_entries'] === -1) {
                    $data['vault_entries'] = [];
                }
            } elseif ($group == 'estimates') {
                $this->load->model('estimates_model');
                $data['estimate_statuses'] = $this->estimates_model->get_statuses();
            } elseif ($group == 'invoices') {
                $this->load->model('invoices_model');
                $data['invoice_statuses'] = $this->invoices_model->get_statuses();
            } elseif ($group == 'credit_notes') {
                $this->load->model('credit_notes_model');
                $data['credit_notes_statuses'] = $this->credit_notes_model->get_statuses();
                $data['credits_available']     = $this->credit_notes_model->total_remaining_credits_by_customer($id);
            } elseif ($group == 'payments') {
                $this->load->model('payment_modes_model');
                $data['payment_modes'] = $this->payment_modes_model->get();
            } elseif ($group == 'notes') {
                $data['user_notes'] = $this->misc_model->get_notes($id, 'customer');
            } elseif ($group == 'projects') {
                $this->load->model('projects_model');
                $data['project_statuses'] = $this->projects_model->get_project_statuses();
            } elseif ($group == 'statement') {
                if (staff_cant('view', 'invoices') && staff_cant('view', 'payments')) {
                    set_alert('danger', _l('access_denied'));
                    redirect(admin_url('branches/branch/' . $id));
                }

                $data = array_merge($data, prepare_mail_preview_data('customer_statement', $id));
            } elseif ($group == 'map') {
                if (get_option('google_api_key') != '' && !empty($branch->latitude) && !empty($branch->longitude)) {
                    $this->app_scripts->add('map-js', base_url($this->app_scripts->core_file('assets/js', 'map.js')) . '?v=' . $this->app_css->core_version());

                    $this->app_scripts->add('google-maps-api-js', [
                        'path'       => 'https://maps.googleapis.com/maps/api/js?key=' . get_option('google_api_key') . '&callback=initMap',
                        'attributes' => [
                            'async',
                            'defer',
                            'latitude'       => "$branch->latitude",
                            'longitude'      => "$branch->longitude",
                            'mapMarkerTitle' => "$branch->branch",
                        ],
                        ]);
                }
            }

            $data['staff'] = $this->staff_model->get('', ['active' => 1]);

            $data['branch'] = $branch;
            $title          = $branch->branch;

            // Get all active staff members (used to add reminder)
            $data['members'] = $data['staff'];

            if (!empty($data['branch']->branch)) {
                // Check if is realy empty client branch so we can set this field to empty
                // The query where fetch the client auto populate firstname and lastname if branch is empty
                if (is_empty_branch($data['branch']->branchid)) {
                    $data['branch']->branch = '';
                }
            }
        }
        $this->load->model('currencies_model');
        $data['currencies'] = $this->currencies_model->get();

        if ($id != '') {
            $customer_currency = $data['branch']->default_currency;
            foreach ($data['currencies'] as $currency) {
                if ($customer_currency != 0) {
                    if ($currency['id'] == $customer_currency) {
                        $customer_currency = $currency;
                        break;
                    }
                } else {
                    if ($currency['isdefault'] == 1) {
                        $customer_currency = $currency;
                        break;
                    }
                }
            }

            if (is_array($customer_currency)) {
                $customer_currency = (object) $customer_currency;
            }

            $data['customer_currency'] = $customer_currency;

            $slug_zip_folder = (
                $branch->branch != ''
                ? $branch->branch
                : get_contact_full_name(get_primary_contact_user_id($branch->branchid))
            );

            $data['zip_in_folder'] = slug_it($slug_zip_folder);
            
        }

        $data['bodyclass'] = 'branch-profile dynamic-create-groups';
        $data['title']     = $title;

        $this->load->view('admin/branch/branch', $data);

    }
}

