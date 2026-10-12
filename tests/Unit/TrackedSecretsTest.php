<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class TrackedSecretsTest extends TestCase
{
    public function test_local_env_and_runtime_log_are_ignored_and_not_tracked(): void
    {
        $root = dirname(__DIR__, 2);
        $gitignore = file_get_contents($root.'/.gitignore');

        $this->assertIsString($gitignore);
        $this->assertStringContainsString('.env.loc', $gitignore);
        $this->assertStringContainsString('/laravel.log', $gitignore);

        $tracked = shell_exec('git -C '.escapeshellarg($root).' ls-files');
        $this->assertIsString($tracked);

        $paths = array_flip(preg_split('/\R/', trim($tracked)) ?: []);
        $this->assertArrayNotHasKey('.env.loc', $paths);
        $this->assertArrayNotHasKey('laravel.log', $paths);
    }
}
