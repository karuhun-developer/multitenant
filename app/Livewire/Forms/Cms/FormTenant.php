<?php

namespace App\Livewire\Forms\Cms;

use App\Livewire\Contracts\FormCrudInterface;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Livewire\Form;
use Spatie\Permission\Models\Permission;

class FormTenant extends Form implements FormCrudInterface
{
    public $id;
    public $tenant_name;
    public $tenant_subdomain;
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
        $this->tenant_subdomain = $data->subdomain;
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
            'tenant_subdomain' => 'required|regex:/^[a-z0-9]+(-[a-z0-9]+)*$/|unique:tenants,subdomain',
            'tenant_domain' => 'nullable|regex:/^[a-z0-9]+(-[a-z0-9]+)*$/|unique:tenants,domain',
            'user_name' => 'required',
            'user_email' => 'required|email|unique:users,email',
            'user_password' => 'required',
        ]);

        DB::beginTransaction();

        try {
            // Create tenant
            $tenant = Tenant::create([
                'name' => $this->tenant_name,
                'subdomain' => $this->tenant_subdomain,
                'domain' => $this->tenant_domain,
            ]);

            // Create owner role for tenant
            $role = Role::create([
                'name' => $this->tenant_subdomain . '_owner',
                'base_name' => 'owner',
                'guard_name' => 'web',
                'tenant_id' => $tenant->id,
            ]);

            // Create user role for tenant
            $role2 = Role::create([
                'name' => $this->tenant_subdomain . '_user',
                'base_name' => 'user',
                'guard_name' => 'web',
                'tenant_id' => $tenant->id,
            ]);

            $this->addPermission($role);
            $this->addPermission($role2);

            // Create user
            $user = User::create([
                'name' => $this->user_name,
                'email' => $this->user_email,
                'password' => $this->user_password,
            ]);
            $user->assignRole($role->name);

            // Attach user to tenant
            $tenant->users()->attach($user->id, ['is_owner' => 1]);

            // Create setting for tenant
            $tenant->setting()->create([
                'name' => ucfirst($this->tenant_name),
                'email' => $this->tenant_name . '@example.com',
                'phone' => '081234567890',
                'address' => 'Jl. Jalan No. 1',
                'about' => 'Lorem ipsum dolor sit amet consectetur adipisicing elit. Quisquam, voluptatum.',
                'google_analytics' => 'UA-123456789-1',
                'mail_email_show' => 'info@example.com',
                'mail_driver' => 'smtp',
                'mail_host' => 'smtp.gmail.com',
                'mail_port' => '587',
                'mail_encryption' => 'tls',
                'mail_username' => 'info@example.com',
                'mail_password' => 'password',
                'mail_from_address' => 'info@example.com',
                'mail_from_name' => ucfirst($this->tenant_name),
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e->getMessage());
        }
    }

    // Update data
    public function update() {
        $this->validate([
            'tenant_name' => 'required',
            'tenant_subdomain' => 'required|regex:/^[a-z0-9]+(-[a-z0-9]+)*$/|unique:tenants,subdomain,' . $this->id,
            'tenant_domain' => 'nullable|regex:/^[a-z0-9]+(-[a-z0-9]+)*$/|unique:tenants,domain,' . $this->id,
            'user_name' => 'required',
            'user_email' => 'required|email|unique:users,email,' . $this->user_id,
        ]);

        // Update tenant
        Tenant::find($this->id)->update([
            'name' => $this->tenant_name,
            'subdomain' => $this->tenant_subdomain,
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

        // Delete setting
        $tenant->setting()->delete();

        // Delete tenant
        $tenant->delete();
    }

    // Change password
    public function changePassword() {
        User::find($this->user_id)->update([
            'password' => $this->password,
        ]);
    }

    // Add permission to role
    private function addPermission(Role $role) {
        $permissionType = [
            'view',
            'create',
            'update',
            'delete',
        ];
        $routeExcept = [
            'sanctum.csrf-cookie',
            'livewire.update',
            'livewire.upload-file',
            'livewire.preview-file',
            'ignition.healthCheck',
            'ignition.executeSolution',
            'ignition.updateConfig',
            'profile.edit',
            'profile.update',
            'profile.destroy',
            'storage.local',
            'debugbar.queries.explain',
            'debugbar.cache.delete',
            'debugbar.assets.css',
            'debugbar.assets.js',
            'debugbar.clockwork',
            'debugbar.openhandler',
            'password.confirm',
            'password.update',
            'login',
            'logout',
            'cms.management.menu',
            'cms.management.menu.child',
            'cms.tenant',
            'cms.tenant.user',
        ];
        $routeUser = [
            'cms.dashboard',
        ];

        $routes = Route::getRoutes();

        foreach ($routes as $value) {
            $route = $value->getName();
            // Except route
            if(!in_array($route, $routeExcept) && !is_null($route)) {
                foreach($permissionType as $type) {
                    $permission = $type . '.' . $route;
                    $permission = Permission::findOrCreate($permission, 'web');

                    // Give owner permission
                    if($role->base_name == 'owner') {
                        $role->givePermissionTo($permission);
                    }

                    // Give user permission
                    if($role->base_name == 'user' && in_array($route, $routeUser)) {
                        $role->givePermissionTo($permission);
                    }
                }
            }
        }
    }
}
