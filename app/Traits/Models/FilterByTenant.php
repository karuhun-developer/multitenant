<?php

namespace App\Traits\Models;

trait FilterByTenant
{
    public static function boot() {
        parent::boot();

        // Check user authenticated
        if(!auth()->check()) return;

        $currentTenant = auth()->user()->tenants()->first()?->id;

        // If user has no tenant, then it is a global role
        if (!$currentTenant) return;
        // Check user role not super admin
        // if (auth()->user()->hasRole('superadmin')) return;

        self::creating(function ($model) use ($currentTenant) {
            $model->tenant_id = $currentTenant;
        });

        self::addGlobalScope('tenant', function ($builder) use ($currentTenant) {
            // Get builder table name
            $table = $builder->getModel()->getTable();
            $builder->where($table . '.tenant_id', $currentTenant);
        });
    }
}
