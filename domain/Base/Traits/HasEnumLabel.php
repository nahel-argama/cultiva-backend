<?php

declare(strict_types=1);

namespace Cultiva\Base\Traits;

use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;

trait HasEnumLabel
{
    public function getLabel(): string
    {
        $enumKey = Str::snake(class_basename(self::class));

        return Lang::get("enums.{$enumKey}.{$this->value}");
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->getLabel();
        }

        return $options;
    }
}
