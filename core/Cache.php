<?php

namespace Core;

class Cache
{
    protected static array $memory = [];
    protected static ?\Redis $redis = null;

    public static function set(string $key, $value, int $ttl = 0): void
    {
        $driver = App::config('cache')['driver'];
        if ($driver === 'redis') {
            self::getRedis()->set($key, serialize($value), $ttl);
        } else {
            self::$memory[$key] = $value;
        }
    }

    public static function get(string $key)
    {
        $driver = App::config('cache')['driver'];
        if ($driver === 'redis') {
            $val = self::getRedis()->get($key);
            return $val ? unserialize($val) : null;
        }

        return self::$memory[$key] ?? null;
    }

    protected static function getRedis(): \Redis
    {
        if (!self::$redis) {
            $config = App::config('cache')['redis'];
            self::$redis = new \Redis();
            self::$redis->connect($config['host'], $config['port']);
        }
        return self::$redis;
    }
}
