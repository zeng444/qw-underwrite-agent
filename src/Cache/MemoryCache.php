<?php

declare(strict_types=1);

namespace Janfish\UnderwriteAgent\Cache;

/**
 * 内存缓存实现
 * 使用PHP数组存储缓存数据，适用于单次请求周期内的缓存
 */
class MemoryCache implements CacheInterface
{
    private array $cache = [];
    private array $expirations = [];

    /**
     * 获取缓存值
     */
    public function get(string $key, $default = null)
    {
        if (!$this->has($key)) {
            return $default;
        }

        return $this->cache[$key];
    }

    /**
     * 设置缓存值
     */
    public function set(string $key, $value, int $ttl = 3600): bool
    {
        $this->cache[$key] = $value;
        
        if ($ttl > 0) {
            $this->expirations[$key] = time() + $ttl;
        } else {
            unset($this->expirations[$key]);
        }
        
        return true;
    }

    /**
     * 删除缓存
     */
    public function delete(string $key): bool
    {
        unset($this->cache[$key], $this->expirations[$key]);
        return true;
    }

    /**
     * 清空所有缓存
     */
    public function clear(): bool
    {
        $this->cache = [];
        $this->expirations = [];
        return true;
    }

    /**
     * 检查缓存是否存在
     */
    public function has(string $key): bool
    {
        if (!isset($this->cache[$key])) {
            return false;
        }
        
        // 检查是否过期
        if (isset($this->expirations[$key]) && time() > $this->expirations[$key]) {
            $this->delete($key);
            return false;
        }
        
        return true;
    }

    /**
     * 获取多个缓存值
     */
    public function getMultiple(array $keys, $default = null): array
    {
        $values = [];
        foreach ($keys as $key) {
            $values[$key] = $this->get($key, $default);
        }
        return $values;
    }

    /**
     * 设置多个缓存值
     */
    public function setMultiple(array $values, int $ttl = 3600): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }
        return true;
    }

    /**
     * 删除多个缓存
     */
    public function deleteMultiple(array $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }
        return true;
    }

    /**
     * 获取缓存统计信息
     */
    public function getStats(): array
    {
        $total = count($this->cache);
        $expired = 0;
        
        foreach ($this->expirations as $key => $expiration) {
            if (time() > $expiration) {
                $expired++;
            }
        }
        
        return [
            'total_items' => $total,
            'expired_items' => $expired,
            'valid_items' => $total - $expired,
            'memory_usage' => strlen(serialize($this->cache)),
        ];
    }

    /**
     * 清理过期缓存
     */
    public function gc(): void
    {
        foreach ($this->expirations as $key => $expiration) {
            if (time() > $expiration) {
                $this->delete($key);
            }
        }
    }
}