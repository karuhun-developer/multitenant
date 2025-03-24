<?php

namespace App\Livewire\Cms\Tenant;

use App\Livewire\Forms\Cms\Tenant\FormUser;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User as UserModel;
use BaseComponent;

class User extends BaseComponent
{
    public FormUser $form;
    public $title;

    public $searchBy = [
            [
                'name' => 'Name',
                'field' => 'users.name',
            ],
            [
                'name' => 'Email',
                'field' => 'users.email',
            ],
            [
                'name' => 'Is Owner',
                'field' => 'tenant_user.is_owner',
            ],
            [
                'name' => 'Created At',
                'field' => 'users.created_at',
            ],
        ],
        $search = '',
        $paginate = 10,
        $orderBy = 'tenant_user.is_owner',
        $order = 'desc';

    public $tenant;
    public $roles = [];

    public function mount(Tenant $tenant) {
        // Add modal for update password
        $this->addModal('updatePasswordModal');

        // Get tenant
        $this->tenant = $tenant;
        // Get roles except owner
        $this->roles = Role::where('tenant_id', $this->tenant->id)->where('name', 'NOT LIKE', '%owner')->get();

        // Title
        $this->title = $this->tenant->name . ' User';
    }

    public function render()
    {
        $model = UserModel::query()
            ->join('tenant_user', 'tenant_user.user_id', '=', 'users.id')
            ->select('users.*', 'tenant_user.is_owner')
            ->where('tenant_user.tenant_id', $this->tenant->id);

        $get = $this->getDataWithFilter(
            model: $model,
            searchBy: $this->searchBy,
            orderBy: $this->orderBy,
            order: $this->order,
            paginate: $this->paginate,
            s: $this->search
        );

        if ($this->search != null) {
            $this->resetPage();
        }

        return view('livewire.cms.tenant.user', compact('get'))->title($this->title);
    }

    public function customSave() {
        $this->form->tenant = $this->tenant;
        $this->save();
    }

    public function editPassword($id) {
        $this->form->getDetail($id);
        $this->openModal('updatePasswordModal');
    }

    public function changePassword() {
        $this->form->changePassword();
        $this->closeModalUpdatePassword();

        $this->dispatch('alert', type: 'success', message: 'Password has been changed');
    }
}
