<?php

namespace App\Livewire\Cms;

use App\Enums\Tenant as EnumsTenant;
use App\Livewire\Forms\Cms\FormTenant;
use App\Models\Tenant as TenantModel;
use BaseComponent;

class Tenant extends BaseComponent {
    public FormTenant $form;
    public $title = 'Tenant';

    public $searchBy = [
            [
                'name' => 'Name',
                'field' => 'tenants.name',
            ],
            [
                'name' => 'Domain',
                'field' => 'tenants.domain',
            ],
            [
                'name' => 'Owner',
                'field' => 'users.name',
            ],
            [
                'name' => 'Owner Email',
                'field' => 'users.email',
            ],
            [
                'name' => 'Created At',
                'field' => 'tenants.created_at',
            ]
        ],
        $search = '',
        $paginate = 10,
        $orderBy = 'tenants.created_at',
        $order = 'desc';

    public function mount() {
        // Add modal for update password
        $this->addModal('updatePasswordModal');
    }

    public function render()
    {
        $model = TenantModel::query()
            ->join('tenant_user', function($join) {
                $join->on('tenants.id', '=', 'tenant_user.tenant_id')
                    ->where('tenant_user.is_owner', EnumsTenant::OWNER);
            })
            ->join('users', 'tenant_user.user_id', '=', 'users.id')
            ->select('tenants.*', 'users.name as owner_name', 'users.email as owner_email');

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

        return view('livewire.cms.tenant', compact('get'))->title($this->title);
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
