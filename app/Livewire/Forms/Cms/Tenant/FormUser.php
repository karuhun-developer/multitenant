<?php

namespace App\Livewire\Forms\Cms\Tenant;

use App\Enums\Tenant;
use App\Livewire\Contracts\FormCrudInterface;
use App\Models\Role;
use App\Models\User;
use Livewire\Form;

class FormUser extends Form implements FormCrudInterface
{
    public $id;
    public $role;
    public $tenant;
    public $name;
    public $email;
    public $password;

    // Get the data
    public function getDetail($id) {
        $data = User::find($id);

        $this->id = $id;
        $this->name = $data->name;
        $this->email = $data->email;
        $this->role = $data->getRoleNames()[0];
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
            'id' => 'nullable',
            'role' => [
                'required',
                'exists:roles,name',
                function ($attribute, $value, $fail) {
                    $role = Role::where('name', $value)->first();
                    if (!$role || $role->tenant_id != $this->tenant->id) {
                        $fail('The selected role is invalid for the current tenant.');
                    }
                },
            ],
            'name' => 'required',
            'email' => 'required|unique:users,email',
            'password' => 'required',
        ]);

        // C

        $user = User::create($this->only([
            'name',
            'email',
            'password',
        ]));

        // Assign new role
        $user->assignRole($this->role);

        // Attach to current tenant
        $this->tenant->users()->attach($user->id, [
            'is_owner' => Tenant::MEMBER,
        ]);
    }

    // Update data
    public function update() {
        $this->validate([
            'id' => 'required',
            'name' => 'required',
            'email' => 'required|unique:users,email,' . $this->id,
        ]);

        $user = User::find($this->id);

        $user->update($this->only([
            'name',
            'email',
        ]));
    }

    // Delete data
    public function delete($id) {
        $user = User::find($id);

        // Detach from current tenant
        $user->tenants()->detach();

        // Delete user
        $user->delete();
    }

    // Change password
    public function changePassword() {
        User::find($this->id)->update([
            'password' => $this->password,
        ]);
    }
}
