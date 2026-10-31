<?php

namespace Cultiva\Integrations\ProductImage\DTO;

final readonly class DownloadedImageDTO
{
    public function __construct(
        public string $content,
        public string $extension,
    ) {}
}
