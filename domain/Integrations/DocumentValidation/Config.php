<?php

namespace Cultiva\Integrations\DocumentValidation;

use Illuminate\Contracts\Config\Repository as ConfigRepository;

final class Config
{
    public function __construct(
        private readonly ConfigRepository $config,
    ) {}

    public function getModel(): string
    {
        return (string) $this->config->get('gemini.model', 'gemini-2.5-flash');
    }
}
