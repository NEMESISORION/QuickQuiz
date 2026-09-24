<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:ensure-application-key')]
#[Description('Generate an application key only when none is configured')]
class EnsureApplicationKey extends Command
{
    public function handle(): int
    {
        $environmentFile = app()->environmentFilePath();
        $contents = is_file($environmentFile) ? file_get_contents($environmentFile) : false;

        if ($contents === false || preg_match('/^APP_KEY=(.*)$/m', $contents, $matches) !== 1) {
            $this->components->error('The environment file must contain an APP_KEY entry.');

            return self::FAILURE;
        }

        if (filled(config('app.key')) || trim($matches[1], " \t\r\n'\"") !== '') {
            $this->components->info('Existing application key preserved.');

            return self::SUCCESS;
        }

        $result = $this->call('key:generate', ['--no-interaction' => true]);
        $updatedContents = file_get_contents($environmentFile);

        return $result === self::SUCCESS
            && $updatedContents !== false
            && preg_match('/^APP_KEY=(.*)$/m', $updatedContents, $updatedMatches) === 1
            && trim($updatedMatches[1], " \t\r\n'\"") !== ''
            ? self::SUCCESS
            : self::FAILURE;
    }
}
