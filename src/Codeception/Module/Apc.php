<?php

declare(strict_types=1);

namespace Codeception\Module;

use Codeception\Module;
use Codeception\TestInterface;
use Codeception\Exception\ModuleException;

/**
 * This module interacts with the [Alternative PHP Cache (APC)](https://php.net/manual/en/intro.apcu.php)
 * using _APCu_ extension.
 *
 * Performs a cleanup by flushing all values after each test run.
 *
 * ## Status
 *
 * * Maintainer: **Serghei Iakovlev**
 * * Stability: **stable**
 * * Contact: serghei@phalcon.io
 *
 * ### Example (`unit.suite.yml`)
 *
 * ```yaml
 *    modules:
 *        - Apc
 * ```
 *
 * Be sure you don't use the production server to connect.
 *
 */
class Apc extends Module
{
    /**
     * Code to run before each test.
     *
     * @throws ModuleException
     */
    public function _before(TestInterface $test)
    {
        if (!extension_loaded('apcu')) {
            throw new ModuleException(
                __CLASS__,
                'The APCu extension not loaded.'
            );
        }

        if (!ini_get('apc.enabled') || (PHP_SAPI === 'cli' && !ini_get('apc.enable_cli'))) {
            throw new ModuleException(
                __CLASS__,
                'The "apc.enable_cli" parameter must be set to "On".'
            );
        }
    }

    /**
     * Code to run after each test.
     */
    public function _after(TestInterface $test)
    {
        $this->clear();
    }

    /**
     * Grabs value from APCu by key.
     *
     * Example:
     *
     * ``` php
     * <?php
     * $users_count = $I->grabValueFromApc('users_count');
     * ```
     */
    public function grabValueFromApc(string $key): mixed
    {
        $value = $this->fetch($key);
        $this->debugSection('Value', $value);

        return $value;
    }

    /**
     * Checks item in APCu exists and the same as expected.
     *
     * Examples:
     *
     * ``` php
     * <?php
     * // With only one argument, only checks the key exists
     * $I->seeInApc('users_count');
     *
     * // Checks a 'users_count' exists and has the value 200
     * $I->seeInApc('users_count', 200);
     * ```
     *
     */
    public function seeInApc(string $key, mixed $value = null): void
    {
        if (null === $value) {
            $this->assertTrue($this->exists($key), "Cannot find key '$key' in APCu.");
            return;
        }

        $actual = $this->grabValueFromApc($key);
        $this->assertEquals($value, $actual, "Cannot find key '$key' in APCu with the provided value.");
    }

    /**
     * Checks item in APCu doesn't exist or is the same as expected.
     *
     * Examples:
     *
     * ``` php
     * <?php
     * // With only one argument, only checks the key does not exist
     * $I->dontSeeInApc('users_count');
     *
     * // Checks a 'users_count' exists does not exist or its value is not the one provided
     * $I->dontSeeInApc('users_count', 200);
     * ```
     */
    public function dontSeeInApc(string $key, mixed $value = null): void
    {
        if (null === $value) {
            $this->assertFalse($this->exists($key), "The key '$key' exists in APCu.");
            return;
        }

        $actual = $this->grabValueFromApc($key);
        $this->assertFalse($actual, "The key '$key' exists in APCu with the provided value.");
    }

    /**
     * Stores an item `$value` with `$key` on the APCu.
     *
     * Examples:
     *
     * ```php
     * <?php
     * // Array
     * $I->haveInApc('users', ['name' => 'miles', 'email' => 'miles@davis.com']);
     *
     * // Object
     * $I->haveInApc('user', UserRepository::findFirst());
     *
     * // Key as array of 'key => value'
     * $entries = [];
     * $entries['key1'] = 'value1';
     * $entries['key2'] = 'value2';
     * $entries['key3'] = ['value3a','value3b'];
     * $entries['key4'] = 4;
     * $I->haveInApc($entries, null);
     * ```
     */
    public function haveInApc(string $key, mixed $value, int $expiration = 0): string
    {
        $this->store($key, $value, $expiration);

        return $key;
    }

    /**
     * Clears the APCu cache
     */
    public function flushApc(): void
    {
        // Returns TRUE always
        $this->clear();
    }

    /**
     * Clears the APCu cache.
     */
    protected function clear(): bool
    {
        return apcu_clear_cache();
    }

    /**
     * Checks if entry exists
     */
    protected function exists(string $key): bool
    {
        return apcu_exists($key);
    }

    /**
     * Fetch a stored variable from the cache
     */
    protected function fetch(string $key): mixed
    {
        $success = false;

        $data = apcu_fetch($key, $success);

        $this->debugSection('Fetching a stored variable', $success ? 'OK' : 'FAILED');

        return $data;
    }

    /**
     * Cache a variable in the data store.
     */
    protected function store(string $key, mixed $var, int $ttl = 0): bool
    {
        return apcu_store($key, $var, $ttl);
    }
}
