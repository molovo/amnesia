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
     * @covers Molovo\Amnesia\Cache::instance
     */
    public function testBootstrap()
    {
        $name   = 'redis_driver_test';
        $config = [
            $name => [
                'driver' => 'redis',
            ],
        ];

        Cache::bootstrap($config);

        $instance = Cache::instance($name);
        verify($instance)->isInstanceOf(Instance::class);

        // Test that the driver has been instantiated correctly
        $property = new \ReflectionProperty(Instance::class, 'driver');
        $property->setAccessible(true);
        $driver = $property->getValue($instance);
        verify($driver)->isInstanceOf(Redis::class);

        // Call a second time to test retrieval from cache
        $cached_instance = Cache::instance($name);

        // Compare hashes of the two instances to ensure they are
        // the same object
        $hash1 = spl_object_hash($instance);
        $hash2 = spl_object_hash($cached_instance);
        verify($hash1)->equals($hash2);

        // Store the instance so we can use it in other tests
        static::$instance = $instance;
    }

    /**
     * Test the cache can be bootstrapped when using the redis driver.
     *
     * @covers Molovo\Amnesia\Cache::bootstrap
     * @covers Molovo\Amnesia\Cache\Instance::__construct
     * @covers Molovo\Amnesia\Driver\Redis::__construct
     * @covers Molovo\Amnesia\Cache::instance
     */
    public function testBootstrapTcp()
    {
        $name   = 'redis_driver_tcp_test';
        $config = [
            $name => [
                'driver' => 'redis',
                'host'   => '127.0.0.1',
                'port'   => 6739,
            ],
        ];

        Cache::bootstrap($config);

        $instance = Cache::instance($name);
        verify($instance)->isInstanceOf(Instance::class);

        // Test that the driver has been instantiated correctly
        $property = new \ReflectionProperty(Instance::class, 'driver');
        $property->setAccessible(true);
        $driver = $property->getValue($instance);
        verify($driver)->isInstanceOf(Redis::class);

        // Call a second time to test retrieval from cache
        $cached_instance = Cache::instance($name);

        // Compare hashes of the two instances to ensure they are
        // the same object
        $hash1 = spl_object_hash($instance);
        $hash2 = spl_object_hash($cached_instance);
        verify($hash1)->equals($hash2);
    }

    /**
     * Test the cache can be bootstrapped when using the redis driver.
     *
     * @covers Molovo\Amnesia\Cache::bootstrap
     * @covers Molovo\Amnesia\Cache\Instance::__construct
     * @covers Molovo\Amnesia\Driver\Redis::__construct
     * @covers Molovo\Amnesia\Cache::instance
     */
    public function testBootstrapSocket()
    {
        $name   = 'redis_driver_socket_test';
        $config = [
            $name => [
                'driver' => 'redis',
                'socket' => '/usr/local/var/run/redis/redis.sock',
            ],
        ];

        Cache::bootstrap($config);

        $instance = Cache::instance($name);
        verify($instance)->isInstanceOf(Instance::class);

        // Test that the driver has been instantiated correctly
        $property = new \ReflectionProperty(Instance::class, 'driver');
        $property->setAccessible(true);
        $driver = $property->getValue($instance);
        verify($driver)->isInstanceOf(Redis::class);

        // Call a second time to test retrieval from cache
        $cached_instance = Cache::instance($name);

        // Compare hashes of the two instances to ensure they are
        // the same object
        $hash1 = spl_object_hash($instance);
        $hash2 = spl_object_hash($cached_instance);
        verify($hash1)->equals($hash2);
    }

    /**
     * Test that values can be set correctly.
     *
     * @depends testBootstrap
     *
     * @covers Molovo\Amnesia\Cache\Instance::set
     * @covers Molovo\Amnesia\Cache\Instance::get
     * @covers Molovo\Amnesia\Cache\Instance::encode
     * @covers Molovo\Amnesia\Cache\Instance::decode
     * @covers Molovo\Amnesia\Driver\Redis::set
     * @covers Molovo\Amnesia\Driver\Redis::get
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
     * @covers Molovo\Amnesia\Cache\Instance::get
     * @covers Molovo\Amnesia\Cache\Instance::encode
     * @covers Molovo\Amnesia\Cache\Instance::decode
     * @covers Molovo\Amnesia\Driver\Redis::set
     * @covers Molovo\Amnesia\Driver\Redis::get
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
     * @covers Molovo\Amnesia\Cache\Instance::get
     * @covers Molovo\Amnesia\Cache\Instance::encode
     * @covers Molovo\Amnesia\Cache\Instance::decode
     * @covers Molovo\Amnesia\Driver\Redis::set
     * @covers Molovo\Amnesia\Driver\Redis::get
     */
    public function testSetWithNullValue()
    {
        static::$instance->set('null.key', null);

        $value = static::$instance->get('null.key');
        verify($value)->null();
    }

    /**
     * Test that values expire correctly.
     *
     * @depends testBootstrap
     *
     * @covers Molovo\Amnesia\Cache\Instance::set
     * @covers Molovo\Amnesia\Cache\Instance::get
     * @covers Molovo\Amnesia\Cache\Instance::encode
     * @covers Molovo\Amnesia\Cache\Instance::decode
     * @covers Molovo\Amnesia\Driver\Redis::set
     * @covers Molovo\Amnesia\Driver\Redis::get
     */
    public function testSetWithExpiry()
    {
        static::$instance->set('another.key', 'value', 3);

        $value = static::$instance->get('another.key');
        verify($value)->equals('value');

        sleep(4);
        $value = static::$instance->get('another.key');
        verify($value)->null();
    }

    /**
     * Test that values can be retrieved correctly.
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
     * Test that nested values can be retrieved correctly.
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

    /**
     * Test that nonexistent keys return null.
     *
     * @depends testBootstrap
     *
     * @covers Molovo\Amnesia\Cache\Instance::get
     * @covers Molovo\Amnesia\Cache\Instance::decode
     * @covers Molovo\Amnesia\Driver\Redis::get
     */
    public function testGetNonexistent()
    {
        $value = static::$instance->get('nonexistent');

        verify($value)->null();
    }

    /**
     * Test that arrays can be set correctly.
     *
     * @depends testBootstrap
     *
     * @covers Molovo\Amnesia\Cache\Instance::set
     * @covers Molovo\Amnesia\Cache\Instance::get
     * @covers Molovo\Amnesia\Cache\Instance::encode
     * @covers Molovo\Amnesia\Cache\Instance::decode
     * @covers Molovo\Amnesia\Driver\Redis::set
     * @covers Molovo\Amnesia\Driver\Redis::get
     */
    public function testSetWithArray()
    {
        $set = [
            'an' => 'array',
        ];
        static::$instance->set('key', $set);

        $value = static::$instance->get('key');
        verify($value)->equals((object) $set);

        $value = static::$instance->get('key', true, true);
        verify($value)->equals($set);

        $value = static::$instance->get('key', false);
        verify($value)->equals('{"an":"array"}');
    }

    /**
     * Test that objects can be set correctly.
     *
     * @depends testBootstrap
     *
     * @covers Molovo\Amnesia\Cache\Instance::set
     * @covers Molovo\Amnesia\Cache\Instance::get
     * @covers Molovo\Amnesia\Cache\Instance::encode
     * @covers Molovo\Amnesia\Cache\Instance::decode
     * @covers Molovo\Amnesia\Driver\Redis::set
     * @covers Molovo\Amnesia\Driver\Redis::get
     */
    public function testSetWithObject()
    {
        $set = (object) [
            'an' => 'array',
        ];
        static::$instance->set('key', $set);

        $value = static::$instance->get('key');
        verify($value)->equals($set);

        $value = static::$instance->get('key', true, true);
        verify($value)->equals((array) $set);

        $value = static::$instance->get('key', false);
        verify($value)->equals('{"an":"array"}');
    }

    /**
     * Test that multiple values can be set correctly.
     *
     * @depends testBootstrap
     *
     * @covers Molovo\Amnesia\Cache\Instance::mset
     * @covers Molovo\Amnesia\Cache\Instance::mget
     * @covers Molovo\Amnesia\Cache\Instance::encode
     * @covers Molovo\Amnesia\Cache\Instance::decode
     * @covers Molovo\Amnesia\Driver\Redis::mset
     * @covers Molovo\Amnesia\Driver\Redis::mget
     */
    public function testSetMultiple()
    {
        $set = [
            'first'  => 'value',
            'second' => 'value',
            'third'  => 'value',
        ];
        static::$instance->mset($set);

        $value = static::$instance->mget(array_keys($set));
        verify($value)->equals($set);
    }

    /**
     * Test that multiple nested values can be set correctly.
     *
     * @depends testBootstrap
     *
     * @covers Molovo\Amnesia\Cache\Instance::mset
     * @covers Molovo\Amnesia\Cache\Instance::mget
     * @covers Molovo\Amnesia\Cache\Instance::encode
     * @covers Molovo\Amnesia\Cache\Instance::decode
     * @covers Molovo\Amnesia\Driver\Redis::mset
     * @covers Molovo\Amnesia\Driver\Redis::mget
     */
    public function testSetMultipleNested()
    {
        $set = [
            'first.nested.key'  => 'value',
            'second.nested.key' => 'value',
            'third.nested.key'  => 'value',
        ];
        static::$instance->mset($set);

        $value = static::$instance->mget(array_keys($set));
        verify($value)->equals($set);
    }

    /**
     * Test that multiple null values clear the key correctly.
     *
     * @depends testBootstrap
     *
     * @covers Molovo\Amnesia\Cache\Instance::mset
     * @covers Molovo\Amnesia\Cache\Instance::mget
     * @covers Molovo\Amnesia\Cache\Instance::encode
     * @covers Molovo\Amnesia\Cache\Instance::decode
     * @covers Molovo\Amnesia\Driver\Redis::mset
     * @covers Molovo\Amnesia\Driver\Redis::mget
     */
    public function testSetMultipleWithNullValue()
    {
        $set = [
            'first'  => null,
            'second' => null,
            'third'  => null,
        ];
        static::$instance->mset($set);

        $value = static::$instance->mget(array_keys($set));
        verify($value)->equals($set);
    }

    /**
     * Test that multiple array values are encoded and stored correctly.
     *
     * @depends testBootstrap
     *
     * @covers Molovo\Amnesia\Cache\Instance::mset
     * @covers Molovo\Amnesia\Cache\Instance::mget
     * @covers Molovo\Amnesia\Cache\Instance::encode
     * @covers Molovo\Amnesia\Cache\Instance::decode
     * @covers Molovo\Amnesia\Driver\Redis::mset
     * @covers Molovo\Amnesia\Driver\Redis::mget
     */
    public function testSetMultipleWithArrays()
    {
        $set = [
            'first' => [
                'test' => 'value',
            ],
            'second' => [
                'test' => 'value',
            ],
        ];
        static::$instance->mset($set);

        $values = static::$instance->mget(['first', 'second']);
        foreach ($set as $key => $value) {
            verify($values[$key])->equals((object) $value);
        }

        $values = static::$instance->mget(['first', 'second'], true, true);
        foreach ($set as $key => $value) {
            verify($values[$key])->equals($value);
        }

        $values = static::$instance->mget(['first', 'second'], false);
        foreach ($set as $key => $value) {
            verify($values[$key])->equals('{"test":"value"}');
        }
    }

    /**
     * Test that multiple object values are encoded and stored correctly.
     *
     * @depends testBootstrap
     *
     * @covers Molovo\Amnesia\Cache\Instance::mset
     * @covers Molovo\Amnesia\Cache\Instance::mget
     * @covers Molovo\Amnesia\Cache\Instance::encode
     * @covers Molovo\Amnesia\Cache\Instance::decode
     * @covers Molovo\Amnesia\Driver\Redis::mset
     * @covers Molovo\Amnesia\Driver\Redis::mget
     */
    public function testSetMultipleWithObjects()
    {
        $set = [
            'first' => (object) [
                'test' => 'value',
            ],
            'second' => (object) [
                'test' => 'value',
            ],
        ];
        static::$instance->mset($set);

        $values = static::$instance->mget(['first', 'second']);
        foreach ($set as $key => $value) {
            verify($values[$key])->equals($value);
        }

        $values = static::$instance->mget(['first', 'second'], true, true);
        foreach ($set as $key => $value) {
            verify($values[$key])->equals((array) $value);
        }

        $values = static::$instance->mget(['first', 'second'], false);
        foreach ($set as $key => $value) {
            verify($values[$key])->equals('{"test":"value"}');
        }
    }

    /**
     * Test that multiple values expire correctly.
     *
     * @depends testBootstrap
     *
     * @covers Molovo\Amnesia\Cache\Instance::mset
     * @covers Molovo\Amnesia\Cache\Instance::mget
     * @covers Molovo\Amnesia\Cache\Instance::encode
     * @covers Molovo\Amnesia\Cache\Instance::decode
     * @covers Molovo\Amnesia\Driver\Redis::mset
     * @covers Molovo\Amnesia\Driver\Redis::mget
     */
    public function testSetMultipleWithExpiry()
    {
        $set = [
            'first'  => 'value',
            'second' => 'value',
            'third'  => 'value',
        ];
        static::$instance->mset($set, 3);

        $value = static::$instance->mget(array_keys($set));
        verify($value)->equals($set);

        sleep(4);
        $value = static::$instance->mget(array_keys($set));
        verify($value)->equals([
            'first'  => null,
            'second' => null,
            'third'  => null,
        ]);
    }

    /**
     * Test that values can be cleared correctly.
     *
     * @depends testBootstrap
     *
     * @covers Molovo\Amnesia\Cache\Instance::clear
     * @covers Molovo\Amnesia\Driver\Redis::clear
     *
     * @uses Molovo\Amnesia\Cache\Instance::mset
     * @uses Molovo\Amnesia\Cache\Instance::mget
     * @uses Molovo\Amnesia\Cache\Instance::encode
     * @uses Molovo\Amnesia\Cache\Instance::decode
     * @uses Molovo\Amnesia\Driver\Redis::mset
     * @uses Molovo\Amnesia\Driver\Redis::mget
     */
    public function testClear()
    {
        static::$instance->set('key', 'value');

        $value = static::$instance->get('key');
        verify($value)->equals('value');

        static::$instance->clear('key');
        $value = static::$instance->get('key');
        verify($value)->null();
    }

    /**
     * Test that multiple values can be cleared correctly.
     *
     * @depends testBootstrap
     *
     * @covers Molovo\Amnesia\Cache\Instance::mclear
     * @covers Molovo\Amnesia\Driver\Redis::mclear
     *
     * @uses Molovo\Amnesia\Cache\Instance::mset
     * @uses Molovo\Amnesia\Cache\Instance::mget
     * @uses Molovo\Amnesia\Cache\Instance::encode
     * @uses Molovo\Amnesia\Cache\Instance::decode
     * @uses Molovo\Amnesia\Driver\Redis::mset
     * @uses Molovo\Amnesia\Driver\Redis::mget
     */
    public function testClearMultiple()
    {
        $set = [
            'first'  => 'value',
            'second' => 'value',
            'third'  => 'value',
        ];
        static::$instance->mset($set);

        $value = static::$instance->mget(array_keys($set));
        verify($value)->equals($set);

        static::$instance->mclear(array_keys($set));
        $value = static::$instance->mget(array_keys($set));
        verify($value)->equals([
            'first'  => null,
            'second' => null,
            'third'  => null,
        ]);
    }

    /**
     * Tests that the keys can be retrieved correctly.
     *
     * @depends testBootstrap
     *
     * @covers Molovo\Amnesia\Cache\Instance::keys
     * @covers Molovo\Amnesia\Driver\Redis::keys
     */
    public function testKeys()
    {
        // We know keys should be filled, because we've been using
        // the cache extensively during the tests above
        $keys = static::$instance->keys();
        verify($keys)->notEmpty();
    }

    /**
     * Tests that the database can be flushed correctly.
     *
     * @depends testBootstrap
     *
     * @covers Molovo\Amnesia\Cache\Instance::flush
     * @covers Molovo\Amnesia\Driver\Redis::flush
     */
    public function testFlush()
    {
        $keys = static::$instance->keys();
        verify($keys)->notEquals([]);

        static::$instance->flush();

        $keys = static::$instance->keys();
        verify($keys)->equals([]);
    }
}
