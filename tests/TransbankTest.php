<?php

namespace Tests;

use LogicException;
use ReflectionClass;
use RuntimeException;
use DarkGhostHunter\Transbank\Transbank;
use PHPUnit\Framework\TestCase;

class TransbankTest extends TestCase
{
    public function test_integration_environment_as_default(): void
    {
        $instance = Transbank::make();

        static::assertFalse($instance->isProduction());
    }

    public function test_can_switch_to_production_with_correct_credentials(): void
    {
        $transbank = Transbank::make()->toProduction([
            'webpay' => [
                'key' => 'test_key',
                'secret' => 'test_secret',
            ]
        ]);

        static::assertTrue($transbank->isProduction());
    }

    public function test_exception_to_production_with_empty_credentials(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot set empty credentials for production environment.');

        Transbank::make()->toProduction([]);
    }

    public function test_exception_to_production_with_wrong_service_credentials(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The Transbank service [invalid_service] doesn\'t exist for these credentials.');

        Transbank::make()->toProduction([
            'invalid_service' => [
                'key' => 'commerceCode',
                'secret' => 'secret',
            ]
        ]);
    }

    public function test_exception_to_production_with_no_key(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Credentials for [webpay] must have a [key] and [secret].');

        Transbank::make()->toProduction([
            'webpay' => [
                'no_key' => 'test_key',
                'secret' => 'test_secret',
            ]
        ]);
    }

    public function test_exception_to_production_with_no_secret(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Credentials for [webpay] must have a [key] and [secret].');

        Transbank::make()->toProduction([
            'webpay' => [
                'key' => 'test_key',
                'no_secret' => 'test_secret',
            ]
        ]);
    }

    public function test_can_singleton(): void
    {
        static::assertSame(Transbank::getInstance(), Transbank::getInstance());
    }

    public function test_removes_instance_to_rebuild(): void
    {
        $singleton = Transbank::getInstance();

        Transbank::setInstance();

        static::assertNotSame($singleton, Transbank::getInstance());
    }

    public function test_switches_back_to_integration(): void
    {
        $transbank = Transbank::make()->toProduction([
            'webpay' => [
                'key' => 'test_key',
                'secret' => 'test_secret',
            ]
        ]);

        static::assertTrue($transbank->isProduction());

        $transbank->toIntegration();

        static::assertFalse($transbank->isProduction());
    }

    protected function tearDown(): void
    {
        $class = new ReflectionClass(Transbank::class);

        $property = $class->getProperty("instance");
        $property->setAccessible(true);
        $property->setValue(null);
    }
}
