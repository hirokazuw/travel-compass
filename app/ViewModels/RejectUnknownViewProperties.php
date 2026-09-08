<?php

declare(strict_types=1);

namespace App\ViewModels;

trait RejectUnknownViewProperties
{
    public function __get(string $name): never
    {
        throw new \Error('Unknown view property: ' . static::class . '::$' . $name);
    }

    public function __set(string $name, mixed $value): never
    {
        throw new \Error('Cannot add view property: ' . static::class . '::$' . $name);
    }
}
