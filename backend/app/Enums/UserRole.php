<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Creator = 'creator';
    case Student = 'aluno';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Creator => 'Criador',
            self::Student => 'Aluno',
        };
    }
}
