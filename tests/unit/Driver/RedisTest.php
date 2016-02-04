<?php

namespace Molovo\Amnesia\Tests\Unit\Driver;

use Molovo\Amnesia\Cache;
use Molovo\Amnesia\Cache\Instance;
use Molovo\Amnesia\Driver\Redis;

class RedisTest extends \Codeception\TestCase\Test
{
    /**
     * The cache instance we are using for testing.
     *
     * @var Instance|null
     */
    private static $instance = null;

    /**
     * Test the cache can be bootstrapped when using the redis driver.
     *
     * @covers Molovo\Amnesia\Cache::bootstrap
     * @covers Molovo\Amnesia\Cache\Instance::__construct
     * @covers Molovo\Amnesia\Driver\Redis::__construct
     */
    public function testBootstrap()
    {
        $config = [
            'redis_driver_bootstrap_test' => [
                'driver' => 'redis',
            ],
        ];

        Cache::bootstrap($config);

        static::$instance = Cache::instance('redis_driver_bootstrap_test');

        verify(static::$instance)->isInstanceOf(Instance::class);

        $property = new \ReflectionProperty(Instance::class, 'driver');
        $property->setAccessible(true);
        $driver = $property->getValue(static::$instance);
        verify($driver)->isInstanceOf(Redis::class);
    }

    /**
     * Test that values can be set correctly.
     *
     * @depends testBootstrap
     *
     * @covers Molovo\Amnesia\Cache\Instance::set
     * @covers Molovo\Amnesia\Driver\Redis::set
     *
     * @uses Molovo\Amnesia\Driver\Redis::get
     */
    public function testSet()
    {
        static::$instance->set('key', 'value');

        $value = static::$instance->get('key');
        verify($value)->equals('value');
    }

    /**
     * Test that nested values can be set correctly.
     *
     * @depends testBootstrap
     *
     * @covers Molovo\Amnesia\Cache\Instance::set
     * @covers Molovo\Amnesia\Driver\Redis::set
     *
     * @uses Molovo\Amnesia\Driver\Redis::get
     */
    public function testSetNested()
    {
        static::$instance->set('a.nested.key', 'value');

        $value = static::$instance->get('a.nested.key');
        verify($value)->equals('value');
    }

    /**
     * Test that null values clear the key correctly.
     *
     * @depends testBootstrap
     *
     * @covers Molovo\Amnesia\Cache\Instance::set
     * @covers Molovo\Amnesia\Driver\Redis::set
     *
     * @uses Molovo\Amnesia\Driver\Redis::get
     */
    public function testSetWithNullValue()
    {
        static::$instance->set('null.key', null);

        $value = static::$instance->get('null.key');
        verify($value)->equals(false);
    }

    /**
     * Test that nested values can be set correctly.
     *
     * @depends testBootstrap
     * @depends testSet
     *
     * @covers Molovo\Amnesia\Cache\Instance::get
     * @covers Molovo\Amnesia\Driver\Redis::get
     */
    public function testGet()
    {
        $value = static::$instance->get('key', 'value');

        verify($value)->equals('value');
    }

    /**
     * Test that nested values can be set correctly.
     *
     * @depends testBootstrap
     * @depends testSetNested
     *
     * @covers Molovo\Amnesia\Cache\Instance::get
     * @covers Molovo\Amnesia\Driver\Redis::get
     */
    public function testGetNested()
    {
        $value = static::$instance->get('a.nested.key');

        verify($value)->equals('value');
    }
}
