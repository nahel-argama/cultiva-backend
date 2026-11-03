<?php

declare(strict_types=1);

namespace Cultiva\Integrations\GoLog\Actions;

use Cultiva\Integrations\GoLog\Clients\GoLogClient;
use Cultiva\Integrations\GoLog\DTO\GoLogTransportTypeResponseDTO;
use Cultiva\Integrations\GoLog\Enums\GoLogTransportType;

final class PopulateTransportTypesAction
{
    public function __construct(
        private readonly GoLogClient $client,
    ) {}

    /**
     * @return array{created: array<int, string>, existing: array<int, string>}
     */
    public function execute(): array
    {
        $existing = $this->client->getTransportTypes();
        $existingNames = array_map(
            static fn(GoLogTransportTypeResponseDTO $item): string => $item->name,
            $existing
        );

        $created = [];
        $alreadyPresent = [];

        foreach (GoLogTransportType::cases() as $transportType) {
            $name = $transportType->value;
            if (in_array($name, $existingNames, true)) {
                $alreadyPresent[] = $name;
                continue;
            }

            $this->client->createTransportType(
                name: $name,
                description: $transportType->description(),
                care: $transportType->care()
            );

            $created[] = $name;
        }

        return [
            'created' => $created,
            'existing' => $alreadyPresent,
        ];
    }
}
