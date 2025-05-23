<?php

// Aquí estamos diciendo que este archivo forma parte de la carpeta App\Enums
namespace App\Enums;

// Esto es una "enum", que básicamente es una lista de opciones posibles para el tipo de usuario.
enum UserRole: string
{
    // Aquí decimos los tipos de usuario que puede haber en la web:
    case USER = 'user';         // Usuario normal, o sea, una persona cualquiera que entra a la web.
    case COMPANY = 'company';   // Empresa, que puede poner viajes a la venta.
    case PROVIDER = 'provider'; // Proveedor, parecido a la empresa, pero puede tener otras funciones.
    case ADMIN = 'admin';       // Admin, que es el que manda y puede controlar todo.

    // Esta función sirve para mostrar el nombre bonito de cada tipo de usuario.
    public function label(): string
    {
        // Dependiendo del tipo de usuario, devuelve un nombre más claro para mostrar en la web.
        return match($this) {
            self::USER => 'Usuario normal',    // Si es usuario normal, muestra esto.
            self::COMPANY => 'Empresa',        // Si es empresa, muestra esto.
            self::PROVIDER => 'Proveedor',     // Si es proveedor, muestra esto.
            self::ADMIN => 'Admin'             // Si es admin, muestra esto.
        };
    }
}