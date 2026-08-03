<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Creator = 'creator';
    case Student = 'aluno';
}
