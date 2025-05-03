<?php

namespace App\Enums;

enum UserRole: string
{
    case USER = 'user';
    case COMPANY = 'company';
    case PROVIDER = 'provider';
    case ADMIN = 'admin';
    
    public function label(): string
    {
        return match($this) {
            self::USER => 'Usuario normal',
            self::COMPANY => 'Empresa',
            self::PROVIDER => 'Proveedor',
            self::ADMIN => 'Admin'
        };
    }
}