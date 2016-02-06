<?php

namespace Molovo\Amnesia\Tests\Unit\Cache;

use Molovo\Amnesia\Cache;
use Molovo\Amnesia\Cache\Instance;
use Molovo\Amnesia\Config;
use Molovo\Amnesia\Exceptions\InvalidDriverException;

class InstanceTest extends \Codeception\TestCase\Test
{
    /**
     * The instance which is used for these tests.
     *
     * @var Instance|null
     */
    private static $instance = null;

    /**
     * Create the instance which we will use during testing.
     */
    public static function setUpBeforeClass()
    {
        Cache::bootstrap([
            'default' => [
                'driver' => 'redis',
            ],
        ]);
        static::$instance = Cache::instance();
    }

    /**
     * Tests that an instance can be created through the constructor.
     *
     * @covers Molovo\Amnesia\Cache\Instance::__construct
     */
    public function testConstruct()
    {
        $config = new Config([
            'driver' => 'redis',
        ]);

        $instance = new Instance('constructor_test', $config);
        verify($instance)->isInstanceOf(Instance::class);
    }

    /**
     * Tests that an instance can be created through the constructor,
     * and that the config is pulled from the bootstrapped Cache class.
     *
     * @covers Molovo\Amnesia\Cache\Instance::__construct
     */
    public function testConstructWithoutConfig()
    {
        $instance = new Instance('default');
        verify($instance)->isInstanceOf(Instance::class);
    }

    /**
     * Tests that the constructor throws an error when the config
     * for an instance cannot be found.
     *
     * @covers Molovo\Amnesia\Cache\Instance::__construct
     *
     * @expectedException Molovo\Amnesia\Exceptions\ConfigNotFoundException
     * @expectedExceptionMessage No config could be found for instance nonexistent.
     */
    public function testConstructWithInvalidInstance()
    {
        $instance = new Instance('nonexistent');
        verify($instance)->isInstanceOf(Instance::class);
    }

    /**
     * Tests that creating an instance with an invalid or nonexistent
     * driver throws InvalidDriverException.
     *
     * @covers Molovo\Amnesia\Cache\Instance::__construct
     *
     * @expectedException Molovo\Amnesia\Exceptions\InvalidDriverException
     * @expectedExceptionMessage nonexistent is not a valid driver.
     */
    public function testConstructWithInvalidDriver()
    {
        $config = new Config([
            'driver' => 'nonexistent',
        ]);

        $instance = new Instance('nonexistent_driver_test', $config);
    }

    /**
     * Tests that the hash of the instance name is added to keys.
     *
     * @covers Molovo\Amnesia\Cache\Instance::key
     */
    public function testKey()
    {
        $str      = 'a.test.key';
        $key      = static::$instance->key($str);
        $expected = '0b4e02e6.a.test.key';

        verify($key)->equals($expected);
    }

    /**
     * Tests that the instance key is removed from keys.
     *
     * @covers Molovo\Amnesia\Cache\Instance::unkey
     */
    public function testUnkey()
    {
        $str      = '0b4e02e6.a.test.key';
        $key      = static::$instance->unkey($str);
        $expected = 'a.test.key';

        verify($key)->equals($expected);
    }

    /**
     * Test that integers are passed through encode untouched.
     *
     * @covers Molovo\Amnesia\Cache\Instance::encode
     */
    public function testEncodeWithInteger()
    {
        $value = 8234;

        $ref = new \ReflectionMethod(Instance::class, 'encode');
        $ref->setAccessible(true);
        $encoded = $ref->invoke(static::$instance, $value);

        verify($encoded)->equals($value);
    }

    /**
     * Test that strings are passed through encode untouched.
     *
     * @covers Molovo\Amnesia\Cache\Instance::encode
     */
    public function testEncodeWithString()
    {
        $value = 'a_test_value';

        $ref = new \ReflectionMethod(Instance::class, 'encode');
        $ref->setAccessible(true);
        $encoded = $ref->invoke(static::$instance, $value);

        verify($encoded)->equals($value);
    }

    /**
     * Test that arrays are correctly encoded as JSON.
     *
     * @covers Molovo\Amnesia\Cache\Instance::encode
     */
    public function testEncodeWithArray()
    {
        // Test that associative arrays are converted to JSON objects
        $array = [
            'test'         => 'value',
            'another_test' => 'another_value',
        ];

        $ref = new \ReflectionMethod(Instance::class, 'encode');
        $ref->setAccessible(true);
        $encoded = $ref->invoke(static::$instance, $array);

        verify($encoded)->equals('{"test":"value","another_test":"another_value"}');

        // Test that numeric arrays are converted to JSON arrays
        $array = ['first', 'second', 'third'];

        $ref = new \ReflectionMethod(Instance::class, 'encode');
        $ref->setAccessible(true);
        $encoded = $ref->invoke(static::$instance, $array);

        verify($encoded)->equals('["first","second","third"]');
    }

    /**
     * Test that objects are correctly encoded as JSON.
     *
     * @covers Molovo\Amnesia\Cache\Instance::encode
     */
    public function testEncodeWithObject()
    {
        $object               = new \stdClass;
        $object->test         = 'value';
        $object->another_test = 'another_value';

        $ref = new \ReflectionMethod(Instance::class, 'encode');
        $ref->setAccessible(true);
        $encoded = $ref->invoke(static::$instance, $object);

        verify($encoded)->equals('{"test":"value","another_test":"another_value"}');
    }
}
