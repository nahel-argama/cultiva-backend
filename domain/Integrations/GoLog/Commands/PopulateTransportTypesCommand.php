<?php

declare(strict_types=1);

namespace Cultiva\Integrations\GoLog\Commands;

use Cultiva\Integrations\GoLog\Actions\PopulateTransportTypesAction;
use Illuminate\Console\Command;

class PopulateTransportTypesCommand extends Command
{
    protected $signature = 'golog:populate-transport-types';

    protected $description = 'Populate GoLog TMS with Cultiva transport types if they are not already present';

    public function handle(PopulateTransportTypesAction $action): int
    {
        $this->info('Connecting to GoLog TMS and checking transport types...');

        $result = $action->execute();

        if (! empty($result['existing'])) {
            $this->line('Already present in GoLog: ' . implode(', ', $result['existing']));
        }

        if (! empty($result['created'])) {
            $this->info('Successfully created in GoLog: ' . implode(', ', $result['created']));
        } else {
            $this->info('All required transport types are already present. No new types created.');
        }

        return self::SUCCESS;
    }
}
