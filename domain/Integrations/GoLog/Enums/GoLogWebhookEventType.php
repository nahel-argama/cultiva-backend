<?php

declare(strict_types=1);

namespace Cultiva\Integrations\GoLog\Enums;

enum GoLogWebhookEventType: string
{
    case TEST_PING = 'TEST_PING';
    case SHIPMENT_CREATED = 'SHIPMENT_CREATED';
    case SHIPMENT_PLANNED = 'SHIPMENT_PLANNED';
    case SHIPMENT_UNPLANNED = 'SHIPMENT_UNPLANNED';
    case SHIPMENT_STARTED = 'SHIPMENT_STARTED';
    case SHIPMENT_DELIVERED = 'SHIPMENT_DELIVERED';
    case SHIPMENT_OCCURRENCE = 'SHIPMENT_OCCURRENCE';
}
