<?php

declare(strict_types=1);

namespace Janfish\UnderwriteAgent\Cache;

/**
 * 缓存接口
 * 定义缓存的基本操作
 */
interface CacheInterface
{
    /**
     * 获取缓存值
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null);

    /**
     * 设置缓存值
     *
     * @param string $key
     * @param mixed $value
     * @param int $ttl 生存时间（秒）
     * @return bool
     */
    public function set(string $key, $value, int $ttl = 3600): bool;

    /**
     * 删除缓存
     *
     * @param string $key
     * @return bool
     */
    public function delete(string $key): bool;

    /**
     * 清空所有缓存
     *
     * @return bool
     */
    public function clear(): bool;

    /**
     * 检查缓存是否存在
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * 获取多个缓存值
     *
     * @param array $keys
     * @param mixed $default
     * @return array
     */
    public function getMultiple(array $keys, $default = null): array;

    /**
     * 设置多个缓存值
     *
     * @param array $values
     * @param int $ttl 生存时间（秒）
     * @return bool
     */
    public function setMultiple(array $values, int $ttl = 3600): bool;

    /**
     * 删除多个缓存
     *
     * @param array $keys
     * @return bool
     */
    public function deleteMultiple(array $keys): bool;
}