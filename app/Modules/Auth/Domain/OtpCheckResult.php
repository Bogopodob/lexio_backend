<?php

namespace App\Modules\Auth\Domain;

enum OtpCheckResult
{
    case Valid;
    case Invalid;
    case Burned;
    case Missing;
}
