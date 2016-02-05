<?php

namespace Molovo\Amnesia\Driver;

use Molovo\Amnesia\Cache\Instance;
use Molovo\Amnesia\Config;
use Molovo\Amnesia\Interfaces\Driver;

class Memcached implements Driver
{
    /**
     * The client to which we are connected.
     *
     * @var \Memcached|null
     */
    private $client = null;

    /**
     * The instance which is using this driver.
     *
     * @var Instance|null
     */
    private $instance = null;

    /**
     * Construct the driver instance.
     *
     * @param Config $config The instance config
     */
    public function __construct(Config $config, Instance $instance)
    {
        $this->instance = $instance;
        $this->client   = new \Memcached($config->name);

        if (count($this->client->getServerList()) === 0) {
            $this->client->addServers($config->servers->toArray());
        }
    }

    /**
     * Get a value from the cache.
     *
     * @param string $key The key to fetch
     *
     * @return string The json_encoded value
     */
    public function get($key)
    {
        $value = $this->client->get($key);

        // The Memcached extension returns false not null for nonexistent
        // values. For consistency's sake, we spoof that here
        if ($value === false) {
            $value = null;
        }

        return $value;
    }

    /**
     * Set a value against a key in the cache.
     *
     * @param string $key     The key to store against
     * @param string $value   A json_encoded value
     * @param int    $expires Optional expiry time in seconds from now
     */
    public function set($key, $value = null, $expires = 0)
    {
        if ($value === null) {
            return $this->clear($key);
        }

        return $this->client->set($key, $value, $expires);
    }

    /**
     * Get multiple values from the cache.
     *
     * @param array $keys An array of keys to get
     *
     * @return array An array of JSON objects
     */
    public function mget(array $keys = array())
    {
        $values = $this->client->getMulti($keys);

        $rtn = [];

        // The Memcached extension returns false not null for nonexistent
        // values. For consistency's sake, we spoof that here
        foreach ($keys as $key) {
            if (!isset($values[$key]) || $values[$key] === false) {
                $values[$key] = null;
            }

            $rtn[$this->instance->unkey($key)] = $values[$key];
        }

        return $rtn;
    }

    /**
     * Set multiple values in the cache.
     *
     * @param array    $dictionary An array of keys and values to set
     * @param int|null $expires    Optional expiry time in seconds from now
     */
    public function mset(array $dictionary = array(), $expires = 0)
    {
        return $this->client->setMulti($dictionary, $expires);
    }

    /**
     * Clear a single value from the cache.
     *
     * @param string $key The key to clear
     */
    public function clear($key)
    {
        return $this->client->delete($key);
    }

    /**
     * Clear an array of values from the cache.
     *
     * @param array $key An array of keys to clear
     */
    public function mclear(array $keys = array())
    {
        return $this->client->deleteMulti($keys);
    }

    /**
     * Get all keys within the namespace.
     *
     * @return array
     *
     * @codeCoverageIgnore
     */
    public function keys($namespace)
    {
        return [];

        // Memcached::getAllKeys does not work with newer versions of memcached,
        // as the internal commands required for it have been deprecated.
        // The functionality below does work with archaic versions of
        // memcached. Keeping it here in case Memcached re-enables this
        // functionality in the future so we can make use of it
        //
        // $allKeys = $this->client->getAllKeys();
        //
        // $keys = [];
        //
        // if ($allKeys !== false) {
        //     foreach ($allKeys as $key) {
        //         if (strpos($key, $namespace) === 0) {
        //             $keys[] = $key;
        //         }
        //     }
        // }
        //
        // return $keys;
    }

    /**
     * Flush all keys within a namespace from the cache.
     *
     * @param string $namespace The namespace to clear
     *
     * @codeCoverageIgnore
     */
    public function flush($namespace)
    {
        // Cannot get keys here.
        // See comments in self::getAllKeys
        // $keys = $this->keys($namespace);

        return $this->client->flush();
    }
}
