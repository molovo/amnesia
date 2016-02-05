<?php

namespace Molovo\Amnesia\Tests\Unit\Driver;

use Molovo\Amnesia\Cache;
use Molovo\Amnesia\Cache\Instance;
use Molovo\Amnesia\Driver\File;

class FileTest extends \Codeception\TestCase\Test
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
     * @covers Molovo\Amnesia\Driver\File::__construct
     * @covers Molovo\Amnesia\Cache::instance
     */
    public function testBootstrap()
    {
        $name   = 'file_driver_test';
        $config = [
            $name => [
                'driver'     => 'file',
                'store_path' => dirname(dirname(__DIR__)).'/_data/cache/store',
            ],
        ];

        if (!is_dir($config[$name]['store_path'])) {
            // This is a test cache, so just let anyone write to it
            // to avoid having to deal with permissions issues
            mkdir($config[$name]['store_path'], 0777, true);
        }

        Cache::bootstrap($config);

        $instance = Cache::instance($name);
        verify($instance)->isInstanceOf(Instance::class);

        // Test that the driver has been instantiated correctly
        $property = new \ReflectionProperty(Instance::class, 'driver');
        $property->setAccessible(true);
        $driver = $property->getValue($instance);
        verify($driver)->isInstanceOf(File::class);

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
     * Test that filenames are built correctly.
     *
     * @depends testBootstrap
     *
     * @covers Molovo\Amnesia\Driver\File::filename
     */
    public function testFilename()
    {
        $key = 'key';

        $ref = new \ReflectionProperty(Instance::class, 'driver');
        $ref->setAccessible(true);
        $driver = $ref->getValue(static::$instance);

        $filename = $driver->filename($key);
        verify($filename)->equals(dirname(dirname(__DIR__)).'/_data/cache/store/'.$key);
    }

    /**
     * Test that values can be set correctly.
     *
     * @depends testBootstrap
     *
     * @covers Molovo\Amnesia\Cache\Instance::set
     * @covers Molovo\Amnesia\Cache\Instance::encode
     * @covers Molovo\Amnesia\Cache\Instance::decode
     * @covers Molovo\Amnesia\Driver\File::set
     * @covers Molovo\Amnesia\Driver\File::get
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
     * @covers Molovo\Amnesia\Cache\Instance::encode
     * @covers Molovo\Amnesia\Cache\Instance::decode
     * @covers Molovo\Amnesia\Driver\File::set
     * @covers Molovo\Amnesia\Driver\File::get
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
     * @covers Molovo\Amnesia\Cache\Instance::encode
     * @covers Molovo\Amnesia\Cache\Instance::decode
     * @covers Molovo\Amnesia\Driver\File::set
     * @covers Molovo\Amnesia\Driver\File::get
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
     * @covers Molovo\Amnesia\Cache\Instance::encode
     * @covers Molovo\Amnesia\Cache\Instance::decode
     * @covers Molovo\Amnesia\Driver\File::set
     * @covers Molovo\Amnesia\Driver\File::get
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
     * @covers Molovo\Amnesia\Driver\File::get
     */
    public function testGet()
    {
        $value = static::$instance->get('key', true);

        verify($value)->equals('value');
    }

    /**
     * Test that nested values can be retrieved correctly.
     *
     * @depends testBootstrap
     * @depends testSetNested
     *
     * @covers Molovo\Amnesia\Cache\Instance::get
     * @covers Molovo\Amnesia\Driver\File::get
     */
    public function testGetNested()
    {
        $value = static::$instance->get('a.nested.key', true);

        verify($value)->equals('value');
    }

    /**
     * Test that nonexistent keys return null.
     *
     * @depends testBootstrap
     *
     * @covers Molovo\Amnesia\Cache\Instance::get
     * @covers Molovo\Amnesia\Cache\Instance::decode
     * @covers Molovo\Amnesia\Driver\File::get
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
     * @covers Molovo\Amnesia\Cache\Instance::encode
     * @covers Molovo\Amnesia\Cache\Instance::decode
     * @covers Molovo\Amnesia\Driver\File::set
     * @covers Molovo\Amnesia\Driver\File::get
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
     * @covers Molovo\Amnesia\Cache\Instance::encode
     * @covers Molovo\Amnesia\Cache\Instance::decode
     * @covers Molovo\Amnesia\Driver\File::set
     * @covers Molovo\Amnesia\Driver\File::get
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
     * @covers Molovo\Amnesia\Cache\Instance::encode
     * @covers Molovo\Amnesia\Cache\Instance::decode
     * @covers Molovo\Amnesia\Driver\File::mset
     * @covers Molovo\Amnesia\Driver\File::mget
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
     * @covers Molovo\Amnesia\Cache\Instance::encode
     * @covers Molovo\Amnesia\Cache\Instance::decode
     * @covers Molovo\Amnesia\Driver\File::mset
     * @covers Molovo\Amnesia\Driver\File::mget
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
     * @covers Molovo\Amnesia\Cache\Instance::encode
     * @covers Molovo\Amnesia\Cache\Instance::decode
     * @covers Molovo\Amnesia\Driver\File::mset
     * @covers Molovo\Amnesia\Driver\File::mget
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
     * Test that multiple values expire correctly.
     *
     * @depends testBootstrap
     *
     * @covers Molovo\Amnesia\Cache\Instance::mset
     * @covers Molovo\Amnesia\Cache\Instance::encode
     * @covers Molovo\Amnesia\Cache\Instance::decode
     * @covers Molovo\Amnesia\Driver\File::mset
     * @covers Molovo\Amnesia\Driver\File::mget
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
     * @covers Molovo\Amnesia\Driver\File::clear
     *
     * @uses Molovo\Amnesia\Cache\Instance::mset
     * @uses Molovo\Amnesia\Cache\Instance::encode
     * @uses Molovo\Amnesia\Cache\Instance::decode
     * @uses Molovo\Amnesia\Driver\File::mset
     * @uses Molovo\Amnesia\Driver\File::mget
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
     * @covers Molovo\Amnesia\Driver\File::mclear
     *
     * @uses Molovo\Amnesia\Cache\Instance::mset
     * @uses Molovo\Amnesia\Cache\Instance::encode
     * @uses Molovo\Amnesia\Cache\Instance::decode
     * @uses Molovo\Amnesia\Driver\File::mset
     * @uses Molovo\Amnesia\Driver\File::mget
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
     * @covers Molovo\Amnesia\Driver\File::keys
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
     * @covers Molovo\Amnesia\Driver\File::flush
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
