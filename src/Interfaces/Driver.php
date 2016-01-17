<?php

namespace Molovo\Amnesia\Interfaces;

interface Driver
{
    /**
     * Get a value from the cache.
     *
     * @param string $key The key to fetch
     *
     * @return string The json_encoded value
     */
    public function get($key);

    /**
     * Set a value against a key in the cache.
     *
     * @param string   $key     The key to store against
     * @param string   $value   A json_encoded value
     * @param int|null $expires Optional expiry time in seconds from now
     */
    public function set($key, $value = null, $expires = null);

    /**
     * Get multiple values from the cache.
     *
     * @param array $keys An array of keys to get
     *
     * @return array An array of JSON objects
     */
    public function mget(array $keys = array());

    /**
     * Set multiple values in the cache.
     *
     * @param array    $dictionary An array of keys and values to set
     * @param int|null $expires    Optional expiry time in seconds from now
     */
    public function mset(array $dictionary = array(), $expires = null);

    /**
     * Clear a single value from the cache.
     *
     * @param string $key The key to clear
     */
    public function clear($key);

    /**
     * Clear an array of values from the cache.
     *
     * @param array $key An array of keys to clear
     */
    public function mclear(array $keys = array());

    /**
     * Flush all keys within a namespace from the cache.
     *
     * @param string $namespace The namespace to clear
     */
    public function flush($namespace);
}
