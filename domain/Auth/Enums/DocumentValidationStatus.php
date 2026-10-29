<?php

namespace Cultiva\Auth\Enums;

enum DocumentValidationStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case FAILED = 'failed';
}
