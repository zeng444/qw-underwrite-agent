<?php

declare(strict_types=1);

namespace Janfish\UnderwriteAgent\Cache;

/**
 * 文件缓存实现
 * 使用文件系统存储缓存数据，支持持久化缓存
 */
class FileCache implements CacheInterface
{
    private string $cacheDir;
    private int $defaultTtl;
    private bool $useCompression;

    public function __construct(string $cacheDir = null, int $defaultTtl = 3600, bool $useCompression = true)
    {
        $this->cacheDir = $cacheDir ?? sys_get_temp_dir() . '/underwrite_cache';
        $this->defaultTtl = $defaultTtl;
        $this->useCompression = $useCompression;
        
        // 确保缓存目录存在
        if (!is_dir($this->cacheDir)) {
            if (!mkdir($this->cacheDir, 0755, true) && !is_dir($this->cacheDir)) {
                throw new \RuntimeException("无法创建缓存目录: {$this->cacheDir}");
            }
        }
    }

    /**
     * 获取缓存值
     */
    public function get(string $key, $default = null)
    {
        $filename = $this->getFilename($key);
        
        if (!file_exists($filename)) {
            return $default;
        }
        
        $data = file_get_contents($filename);
        if ($data === false) {
            return $default;
        }
        
        // 解压缩数据
        if ($this->useCompression) {
            $data = gzuncompress($data);
            if ($data === false) {
                return $default;
            }
        }
        
        $cached = unserialize($data);
        if ($cached === false) {
            return $default;
        }
        
        // 检查是否过期
        if (isset($cached['expiration']) && time() > $cached['expiration']) {
            $this->delete($key);
            return $default;
        }
        
        return $cached['data'] ?? $default;
    }

    /**
     * 设置缓存值
     */
    public function set(string $key, $value, int $ttl = 3600): bool
    {
        $filename = $this->getFilename($key);
        
        $ttl = $ttl > 0 ? $ttl : $this->defaultTtl;
        $expiration = time() + $ttl;
        
        $cached = [
            'data' => $value,
            'expiration' => $expiration,
            'created_at' => time(),
        ];
        
        $data = serialize($cached);
        
        // 压缩数据
        if ($this->useCompression) {
            $compressed = gzcompress($data);
            if ($compressed !== false) {
                $data = $compressed;
            }
        }
        
        return file_put_contents($filename, $data, LOCK_EX) !== false;
    }

    /**
     * 删除缓存
     */
    public function delete(string $key): bool
    {
        $filename = $this->getFilename($key);
        
        if (file_exists($filename)) {
            return unlink($filename);
        }
        
        return true;
    }

    /**
     * 清空所有缓存
     */
    public function clear(): bool
    {
        $files = glob($this->cacheDir . '/*.cache');
        
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        
        return true;
    }

    /**
     * 检查缓存是否存在
     */
    public function has(string $key): bool
    {
        $filename = $this->getFilename($key);
        
        if (!file_exists($filename)) {
            return false;
        }
        
        // 检查是否过期
        $data = file_get_contents($filename);
        if ($data === false) {
            return false;
        }
        
        // 解压缩数据
        if ($this->useCompression) {
            $data = gzuncompress($data);
            if ($data === false) {
                return false;
            }
        }
        
        $cached = unserialize($data);
        if ($cached === false) {
            return false;
        }
        
        if (isset($cached['expiration']) && time() > $cached['expiration']) {
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
        $success = true;
        foreach ($values as $key => $value) {
            if (!$this->set($key, $value, $ttl)) {
                $success = false;
            }
        }
        return $success;
    }

    /**
     * 删除多个缓存
     */
    public function deleteMultiple(array $keys): bool
    {
        $success = true;
        foreach ($keys as $key) {
            if (!$this->delete($key)) {
                $success = false;
            }
        }
        return $success;
    }

    /**
     * 获取缓存文件路径
     */
    private function getFilename(string $key): string
    {
        // 使用MD5哈希确保文件名安全
        $hash = md5($key);
        return $this->cacheDir . '/' . $hash . '.cache';
    }

    /**
     * 获取缓存统计信息
     */
    public function getStats(): array
    {
        $files = glob($this->cacheDir . '/*.cache');
        $totalFiles = count($files);
        $totalSize = 0;
        $expiredFiles = 0;
        
        foreach ($files as $file) {
            $totalSize += filesize($file);
            
            // 检查是否过期
            $data = file_get_contents($file);
            if ($data !== false) {
                if ($this->useCompression) {
                    $data = gzuncompress($data);
                }
                
                if ($data !== false) {
                    $cached = unserialize($data);
                    if ($cached !== false && isset($cached['expiration']) && time() > $cached['expiration']) {
                        $expiredFiles++;
                    }
                }
            }
        }
        
        return [
            'total_files' => $totalFiles,
            'total_size_bytes' => $totalSize,
            'total_size_mb' => round($totalSize / 1048576, 2), // 转换为MB
            'expired_files' => $expiredFiles,
            'valid_files' => $totalFiles - $expiredFiles,
            'cache_dir' => $this->cacheDir,
        ];
    }

    /**
     * 清理过期缓存
     */
    public function gc(): void
    {
        $files = glob($this->cacheDir . '/*.cache');
        
        foreach ($files as $file) {
            $data = file_get_contents($file);
            if ($data !== false) {
                if ($this->useCompression) {
                    $data = gzuncompress($data);
                }
                
                if ($data !== false) {
                    $cached = unserialize($data);
                    if ($cached !== false && isset($cached['expiration']) && time() > $cached['expiration']) {
                        unlink($file);
                    }
                }
            }
        }
    }
}