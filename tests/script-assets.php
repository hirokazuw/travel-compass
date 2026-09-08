<?php

$t->test('Script asset version changes for module-only edits even with unchanged timestamps', function () use ($t): void {
    $directory = sys_get_temp_dir() . '/travel-compass-assets-' . bin2hex(random_bytes(8));
    mkdir($directory);
    mkdir($directory . '/js');
    try {
        file_put_contents($directory . '/app.js', 'entry');
        file_put_contents($directory . '/js/feature.js', 'before');
        $before = App\ViewModels\ScriptAssetVersion::create($directory);
        $mtime = filemtime($directory . '/js/feature.js');
        file_put_contents($directory . '/js/feature.js', 'after');
        touch($directory . '/js/feature.js', $mtime);
        $after = App\ViewModels\ScriptAssetVersion::create($directory);
        $t->true($before !== $after, 'Module edit must invalidate entry and imported assets');
        $t->same($after, App\ViewModels\ScriptAssetVersion::create($directory));
    } finally {
        unlink($directory . '/js/feature.js');
        unlink($directory . '/app.js');
        rmdir($directory . '/js');
        rmdir($directory);
    }
});
