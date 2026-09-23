<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class EnsureApplicationKeyTest extends TestCase
{
    public function test_existing_application_key_is_preserved(): void
    {
        $originalPath = app()->environmentPath();
        $originalKey = config('app.key');
        $temporaryPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'quickquiz-key-'.bin2hex(random_bytes(6));
        mkdir($temporaryPath);
        $environmentFile = $temporaryPath.DIRECTORY_SEPARATOR.'.env';
        file_put_contents($environmentFile, "APP_KEY=base64:existing-key\n");

        try {
            app()->useEnvironmentPath($temporaryPath);
            config()->set('app.key', '');

            $exitCode = Artisan::call('app:ensure-application-key', ['--no-interaction' => true]);

            $this->assertSame(0, $exitCode);
            $this->assertSame("APP_KEY=base64:existing-key\n", file_get_contents($environmentFile));
        } finally {
            app()->useEnvironmentPath($originalPath);
            config()->set('app.key', $originalKey);
            unlink($environmentFile);
            rmdir($temporaryPath);
        }
    }

    public function test_missing_application_key_is_generated_once(): void
    {
        $originalPath = app()->environmentPath();
        $originalKey = config('app.key');
        $temporaryPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'quickquiz-key-'.bin2hex(random_bytes(6));
        mkdir($temporaryPath);
        $environmentFile = $temporaryPath.DIRECTORY_SEPARATOR.'.env';
        file_put_contents($environmentFile, "APP_KEY=\n");

        try {
            app()->useEnvironmentPath($temporaryPath);
            config()->set('app.key', '');

            $exitCode = Artisan::call('app:ensure-application-key', ['--no-interaction' => true]);

            $this->assertSame(0, $exitCode);
            $this->assertSame(1, preg_match('/^APP_KEY=base64:[A-Za-z0-9+\/=]+$/m', file_get_contents($environmentFile)));
            $generatedKey = file_get_contents($environmentFile);

            Artisan::call('app:ensure-application-key', ['--no-interaction' => true]);

            $this->assertSame($generatedKey, file_get_contents($environmentFile));
        } finally {
            app()->useEnvironmentPath($originalPath);
            config()->set('app.key', $originalKey);
            unlink($environmentFile);
            rmdir($temporaryPath);
        }
    }
}
