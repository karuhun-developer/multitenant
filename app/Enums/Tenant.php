<?php

namespace App\Enums;

enum Tenant: int {
    case OWNER = 1;
    case MEMBER = -1;

    public function label(): string {
        return match($this) {
            self::OWNER => 'Owner',
            self::MEMBER => 'Member',
        };
    }

    public function color(): string {
        return match($this) {
            self::OWNER => 'badge bg-success text-white',
            self::MEMBER => 'badge bg-info text-white',
        };
    }
}
