<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Tenant extends Model
{
    use LogsActivity;

    protected $fillable = [
        'name',
        'domain',
    ];

    public function getActivitylogOptions(): LogOptions {
        return LogOptions::defaults()
            ->logOnly(['*']);
    }

    public function users() {
        return $this->belongsToMany(User::class)->withPivot('is_owner');
    }

    public function setting() {
        return $this->hasOne(Setting::class);
    }
}
