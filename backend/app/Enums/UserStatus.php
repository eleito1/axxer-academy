<?php

namespace App\Enums;

enum UserStatus: string
{
    case Pending = 'pendente';
    case Approved = 'aprovado';
    case Blocked = 'bloqueado';
}
