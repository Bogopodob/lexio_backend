<?php

namespace App\Modules\Auth\Domain\Enums;

enum AuthPurposeEnum: string
{
    case Login = 'login';
    case Registration = 'registration';
}
