<?php

namespace App\Livewire\Forms\Cms;

use App\Livewire\Contracts\FormCrudInterface;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Livewire\Form;

class FormTenant extends Form implements FormCrudInterface
{
    public $id;
    public $tenant_name;
    public $tenant_domain;

    public $user_id;
    public $user_name;
    public $user_email;
    public $user_password;

    // Get the data
    public function getDetail($id) {
        $data = Tenant::find($id);

        // Get tenant owner
        $user = $data->users()->wherePivot('is_owner', 1)->first();

        $this->id = $id;
        $this->tenant_name = $data->name;
        $this->tenant_domain = $data->domain;
        $this->user_id = $user->id;
        $this->user_name = $user->name;
        $this->user_email = $user->email;
    }

    // Save the data
    public function save() {
        if ($this->id) {
            $this->update();
        } else {
            $this->store();
        }

        $this->reset();
    }

    // Store data
    public function store() {
        $this->validate([
            'tenant_name' => 'required',
            'tenant_domain' => 'required|regex:/^[a-z0-9]+(-[a-z0-9]+)*$/|unique:tenants,domain',
            'user_name' => 'required',
            'user_email' => 'required|email|unique:users,email',
            'user_password' => 'required',
        ]);

        // Create tenant
        $tenant = Tenant::create([
            'name' => $this->tenant_name,
            'domain' => $this->tenant_domain,
        ]);

        // Create owner role for tenant
        $role = Role::create([
            'name' => $this->tenant_domain . '_owner',
            'base_name' => 'owner',
            'guard_name' => 'web',
            'tenant_id' => $tenant->id,
        ]);

        // Create user role for tenant
        Role::create([
            'name' => $this->tenant_domain . '_user',
            'base_name' => 'user',
            'guard_name' => 'web',
            'tenant_id' => $tenant->id,
        ]);

        // Create user
        $user = User::create([
            'name' => $this->user_name,
            'email' => $this->user_email,
            'password' => $this->user_password,
        ]);
        $user->assignRole($role->name);

        // Attach user to tenant
        $tenant->users()->attach($user->id, ['is_owner' => 1]);
    }

    // Update data
    public function update() {
        $this->validate([
            'tenant_name' => 'required',
            'tenant_domain' => 'required|regex:/^[a-z0-9]+(-[a-z0-9]+)*$/|unique:tenants,domain,' . $this->id,
            'user_name' => 'required',
            'user_email' => 'required|email|unique:users,email,' . $this->user_id,
        ]);

        // Update tenant
        Tenant::find($this->id)->update([
            'name' => $this->tenant_name,
            'domain' => $this->tenant_domain,
        ]);

        // Update user
        User::find($this->user_id)->update([
            'name' => $this->user_name,
            'email' => $this->user_email,
        ]);

        // Update role name
        Role::where('tenant_id', $this->id)->get()->each(function($role) {
            $role->update([
                'name' => $this->tenant_domain . '_' . $role->base_name,
            ]);
        });
    }

    // Delete data
    public function delete($id) {
        $tenant = Tenant::find($id);

        // Get all user id from tenant
        $userIds = $tenant->users->pluck('id')->toArray();
        // Detach user from tenant
        $tenant->users()->detach();

        // Delete user
        User::whereIn('id', $userIds)->delete();

        // Delete role
        Role::where('tenant_id', $id)->delete();

        // Delete tenant
        $tenant->delete();
    }

    // Change password
    public function changePassword() {
        User::find($this->user_id)->update([
            'password' => $this->password,
        ]);
    }
}
