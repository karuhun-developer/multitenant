<?php

namespace App\Livewire\Cms\Management\Role;


use BaseComponent;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission as PermissionModel;
use Spatie\Permission\Models\Role;

class Permission extends BaseComponent
{
    public $title = '';
    public $role = null;
    public $permissions = [];
    public $permissionType = [
        'view',
        'create',
        'update',
        'delete',
    ];
    public $routeExcept = [
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
    ];

    public function mount($role = null) {
        $this->role = Role::findByName($role);
        $this->title = 'Role Permissions - ' . ucfirst($this->role->name);

        // Check user role is not super admin
        if(!auth()->user()->hasRole('superadmin')) {
            $this->routeExcept = array_merge($this->routeExcept, [
                'pulse',
                'cms.logs',
                'cms.management.menu',
                'cms.management.menu.child',
                'cms.management.user',
                'cms.management.access-control',
                'cms.tenant',
                'cms.tenant.user',
                // 'cms.management.role',
                // 'cms.management.role-permission',
                // 'cms.management.general-setting',
                // 'cms.management.misc-setting',
                // 'cms.management.mail-setting',
                // 'cms.management.privacy-policy-setting',
                // 'cms.management.term-of-service-setting',
            ]);
        }

        $this->getPermission();
        // dd($this->permissions);
    }

    public function render()
    {
        return view('livewire.cms.management.role.permission')->title($this->title);
    }

    // Get role permission
    public function getPermission() {
        // Get all route names
        $routes = Route::getRoutes();

        foreach ($routes as $value) {
            $route = $value->getName();
            // Except route
            if(!in_array($route, $this->routeExcept) && !is_null($route)) {
                $this->permissions[$route] = [];
                foreach($this->permissionType as $type) {
                    $this->permissions[$route][$type . '.' . $route] = false;
                }
            }
        }


        // Get all permissions
        foreach ($this->role->permissions->pluck('name') as $permission) {
            $route = explode('.', $permission);
            /**
             *
             * Ignore type permission name, e.g `view.cms.management.*` to `cms.management.*`
             *
             **/
            unset($route[0]);
            $route = implode('.', $route);
            if(in_array($route, $this->routeExcept)) continue;
            $this->permissions[$route][$permission] = true;
        }
    }

    // Check all
    public function checkAll() {
        foreach($this->permissions as $key => $value) {
            foreach($value as $k => $v) {
                $this->check($k, $key);
                $this->permissions[$key][$k] = true;
            }
        }
    }

    // Uncheck all
    public function uncheckAll() {
        foreach($this->permissions as $key => $value) {
            foreach($value as $k => $v) {
                $this->uncheck($k, $key);
                $this->permissions[$key][$k] = false;
            }
        }
    }

    // Check
    public function check($permission, $route) {
        $this->isPermissionExist($permission);
        $this->role->givePermissionTo($permission);
        $this->permissions[$route][$permission] = true;

        activity()
            ->causedBy(auth()->user())
            ->performedOn($this->role)
            ->withProperties([
                'route' => $route,
                'permission' => $permission,
            ])
            ->event('check-permission')
            ->log('Add permission');
    }

    // Uncheck
    public function uncheck($permission, $route) {
        $this->isPermissionExist($permission);
        $this->role->revokePermissionTo($permission);
        $this->permissions[$route][$permission] = false;

        activity()
            ->causedBy(auth()->user())
            ->performedOn($this->role)
            ->withProperties([
                'route' => $route,
                'permission' => $permission,
            ])
            ->event('uncheck-permission')
            ->log('Remove permission');
    }

    // Is Permission Exist
    public function isPermissionExist($permission) {
        $isPermissionExist = PermissionModel::where('name', $permission)->first();
        if(is_null($isPermissionExist)) {
            PermissionModel::create([
                'name' => $permission,
            ]);
        }
    }
}
