<?php

declare(strict_types=1);

namespace App\ViewModels;

final class ScriptAssetVersion
{
    /** Hash entry and modules together so a module-only edit also invalidates caches. */
    public static function create(string $assets): string
    {
        $modules = glob($assets . '/js/*.js');
        sort($modules, SORT_STRING);
        $context = hash_init('sha256');
        foreach (array_merge([$assets . '/app.js'], $modules) as $file) {
            hash_update($context, basename($file) . "\0");
            hash_update_file($context, $file);
        }
        return substr(hash_final($context), 0, 16);
    }
}
