<?php if (!defined('BASEPATH')) exit('No direct access allowed');

class Permissions extends Admin_Controller
{

    public $filter = [
        'page'          => '',
        'filter_search' => '',
        'filter_status' => '',
    ];

    public $default_sort = ['permission_id', 'DESC'];

    public $sort = ['name', 'status', 'permission_id'];

    public function __construct()
    {
        parent::__construct(); //  calls the constructor

        $this->user->restrict('Admin.Permissions');

        $this->load->model('Permissions_model');

        $this->lang->load('permissions');
    }

    public function index()
    {
        if ($this->input->post('delete') AND $this->_deletePermission() === TRUE) {
            $this->redirect();
        }

        $this->template->setTitle($this->lang->line('text_title'));
        $this->template->setHeading($this->lang->line('text_heading'));
        $this->template->setButton($this->lang->line('button_new'), ['class' => 'btn btn-primary', 'href' => page_url().'/edit']);
        $this->template->setButton($this->lang->line('button_delete'), ['class' => 'btn btn-danger', 'onclick' => 'confirmDelete();']);;
        $this->template->setButton($this->lang->line('button_icon_filter'), ['class' => 'btn btn-default btn-filter pull-right', 'data-toggle' => 'button']);

        $data = $this->getList();

        $this->template->render('permissions', $data);
    }

    public function edit()
    {
        if ($this->input->post() AND $permission_id = $this->_savePermission()) {
            $this->redirect($permission_id);
        }

        $permission_info = $this->Permissions_model->getPermission((int)$this->input->get('id'));

        $title = (isset($permission_info['name'])) ? $permission_info['name'] : $this->lang->line('text_new');
        $this->template->setTitle(sprintf($this->lang->line('text_edit_heading'), $title));
        $this->template->setHeading(sprintf($this->lang->line('text_edit_heading'), $title));

        $this->template->setButton($this->lang->line('button_save'), ['class' => 'btn btn-primary', 'onclick' => '$(\'#edit-form\').submit();']);
        $this->template->setButton($this->lang->line('button_save_close'), ['class' => 'btn btn-default', 'onclick' => 'saveClose();']);
        $this->template->setButton($this->lang->line('button_icon_back'), ['class' => 'btn btn-default', 'href' => site_url('permissions')]);

        $data = $this->getForm($permission_info);

        $this->template->render('permissions_edit', $data);
    }

    public function getList()
    {
        $data = array_merge($this->getFilter(), $this->getSort());

        $data['permissions'] = [];
        $results = $this->Permissions_model->paginateWithFilter($this->getFilter());
        foreach ($results->list as $result) {
            $data['permissions'][] = array_merge($result, [
                'action' => (!empty($result['action'])) ? ucwords(implode(' | ', $result['action'])) : '',
                'edit'   => $this->pageUrl($this->edit_url, ['id' => $result['permission_id']]),
            ]);
        }

        $data['pagination'] = $results->pagination;

        return $data;
    }

    public function getForm($permission_info = [])
    {
        $data = $permission_info;

        $permission_id = 0;
        $data['_action'] = $this->pageUrl($this->create_url);
        if (!empty($permission_info['permission_id'])) {
            $permission_id = $permission_info['permission_id'];
            $data['_action'] = $this->pageUrl($this->edit_url, ['id' => $permission_id]);
        }

        $data['permission_id'] = $permission_info['permission_id'];
        $data['name'] = $permission_info['name'];
        $data['description'] = $permission_info['description'];
        $data['status'] = $permission_info['status'];

        $data['action'] = [];
        if ($this->input->post('action')) {
            $data['action'] = $this->input->post('action');
        } else if (!empty($permission_info['action'])) {
            $data['action'] = $permission_info['action'];
        }

        $data['permission_actions'] = ['access' => $this->lang->line('text_access'), 'manage' => $this->lang->line('text_manage'), 'add' => $this->lang->line('text_add'), 'delete' => $this->lang->line('text_delete')];

        return $data;
    }

    protected function _savePermission()
    {
        if ($this->validateForm() === TRUE) {
            $save_type = (!is_numeric($this->input->get('id'))) ? $this->lang->line('text_added') : $this->lang->line('text_updated');
            if ($permission_id = $this->Permissions_model->savePermission($this->input->get('id'), $this->input->post())) {
                log_activity($this->user->getStaffId(), $save_type, 'permissions', get_activity_message('activity_custom_no_link',
                    ['{staff}', '{action}', '{context}', '{item}'],
                    [$this->user->getStaffName(), $save_type, 'permission', $this->input->post('name')]
                ));

                $this->alert->set('success', sprintf($this->lang->line('alert_success'), 'Permission '.$save_type));
            } else {
                $this->alert->set('warning', sprintf($this->lang->line('alert_error_nothing'), $save_type));
            }

            return $permission_id;
        }
    }

    protected function _deletePermission()
    {
        if ($this->input->post('delete')) {
            $deleted_rows = $this->Permissions_model->deletePermission($this->input->post('delete'));
            if ($deleted_rows > 0) {
                $prefix = ($deleted_rows > 1) ? '['.$deleted_rows.'] Permissions' : 'Permission';
                $this->alert->set('success', sprintf($this->lang->line('alert_success'), $prefix.' '.$this->lang->line('text_deleted')));
            } else {
                $this->alert->set('warning', sprintf($this->lang->line('alert_error_nothing'), $this->lang->line('text_deleted')));
            }

            return TRUE;
        }
    }

    protected function validateForm()
    {
        $rules[] = ['name', 'lang:label_name', 'xss_clean|trim|required|min_length[2]|max_length[128]|callback__validate_name'];
        $rules[] = ['description', 'lang:label_description', 'xss_clean|trim|required|max_length[255]'];
        $rules[] = ['status', 'lang:label_status', 'xss_clean|trim|required|integer'];

        return $this->form_validation->set_rules($rules)->run();
    }

    public function _validate_name($str)
    {
        $name = explode('.', $str);
        if (strpos($str, '.') === FALSE OR !isset($name[0]) OR !in_array(strtolower($name[0]), ['admin', 'site', 'module', 'payment'])) {
            $this->form_validation->set_message('_validate_name', $this->lang->line('error_invalid_name'));

            return FALSE;
        }

        return TRUE;
    }
}

/* End of file Permissions.php */
/* Location: ./admin/controllers/Permissions.php */