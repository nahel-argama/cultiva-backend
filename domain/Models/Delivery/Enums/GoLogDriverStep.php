<?php

declare(strict_types=1);

namespace Cultiva\Models\Delivery\Enums;

enum GoLogDriverStep: string
{
    case USER = 'user';
    case DRIVER = 'driver';
    case TRACTOR = 'tractor';
    case EQUIPAMENT_GROUP = 'equipament_group';
    case WORK_SCHEDULE = 'work_schedule';
    case COMPLETED = 'completed';
}
