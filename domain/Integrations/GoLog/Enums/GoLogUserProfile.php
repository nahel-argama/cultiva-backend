<?php

declare(strict_types=1);

namespace Cultiva\Integrations\GoLog\Enums;

enum GoLogUserProfile: string
{
    case ADMIN = 'ADMIN';
    case DRIVER = 'DRIVER';
    case OPERATOR = 'OPERATOR';
    case POWERBI = 'POWERBI';
}
