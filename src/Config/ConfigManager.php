<?php

declare(strict_types=1);

namespace Janfish\UnderwriteAgent\Config;

use Janfish\UnderwriteAgent\Exception\Exception;

/**
 * 配置管理类
 * 统一管理所有配置项，提供类型安全和默认值处理
 */
class ConfigManager
{
    private array $config;
    private array $defaultConfig = [
        'baseUrl' => 'https://dashscope.aliyuncs.com/api/v1/services/aigc/text-generation/generation',
        'timeout' => 30,
        'connectTimeout' => 10,
        'maxToken' => 8192,
        'temperature' => 0.3,
        'model' => 'qwen3-max',
        'retryAttempts' => 3,
        'retryDelay' => 1000, // 毫秒
        'cache' => 'none',                    // 缓存类型: none/memory/file
        'cacheDir' => null,                   // 缓存目录
        'cacheTtl' => 3600,                   // 缓存时间（秒）
        'cacheEnabled' => false,              // 向后兼容，由cache参数决定
        'logEnabled' => true,
        'logLevel' => 'info',
        'logFile' => null,                    // 日志文件路径
    ];

    /**
     * @param array $config 用户配置
     * @throws Exception
     */
    public function __construct(array $config)
    {
        $this->validateRequiredConfig($config);
        $this->config = array_merge($this->defaultConfig, $config);
        
        /**
         * 处理缓存配置兼容性
         * 
         * 逻辑说明：
         * - 如果用户设置了cache参数且没有手动设置cacheEnabled，则根据cache值自动决定缓存启用状态
         * - 当cache为'none'时，cacheEnabled为false（禁用缓存）
         * - 当cache为'memory'或'file'时，cacheEnabled为true（启用缓存）
         * - 如果用户手动设置了cacheEnabled，则优先使用用户的设置（向后兼容）
         */
        if (isset($config['cache']) && !isset($config['cacheEnabled'])) {
            $this->config['cacheEnabled'] = ($config['cache'] !== 'none');
        }
        
        /**
         * 处理日志文件配置
         * 
         * 逻辑说明：
         * - 如果用户设置了logFile参数且没有手动设置logEnabled，则根据logFile值自动决定日志启用状态
         * - 当logFile不为空时，logEnabled为true（启用日志）
         * - 当logFile为空时，logEnabled为false（禁用日志）
         * - 如果用户手动设置了logEnabled，则优先使用用户的设置
         */
        if (isset($config['logFile']) && !isset($config['logEnabled'])) {
            $this->config['logEnabled'] = !empty($config['logFile']);
        }
        
        $this->validateConfigTypes();
    }

    /**
     * 获取配置值
     *
     * @param string $key 配置键
     * @param mixed $default 默认值
     * @return mixed 配置值
     */
    public function get(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * 设置配置值
     *
     * @param string $key 配置键
     * @param mixed $value 配置值
     */
    public function set(string $key, $value): void
    {
        $this->config[$key] = $value;
    }

    /**
     * 获取API密钥
     *
     * @return string
     * @throws Exception
     */
    public function getApiKey(): string
    {
        $apiKey = $this->get('apiKey');
        if (empty($apiKey)) {
            throw new Exception("API密钥不能为空");
        }
        return $apiKey;
    }

    /**
     * 获取基础URL
     *
     * @return string
     */
    public function getBaseUrl(): string
    {
        return $this->get('baseUrl');
    }

    /**
     * 获取超时时间
     *
     * @return int
     */
    public function getTimeout(): int
    {
        return (int)$this->get('timeout');
    }

    /**
     * 获取连接超时时间
     *
     * @return int
     */
    public function getConnectTimeout(): int
    {
        return (int)$this->get('connectTimeout');
    }

    /**
     * 获取模型名称
     *
     * @return string
     */
    public function getModel(): string
    {
        return $this->get('model');
    }

    /**
     * 获取温度参数
     *
     * @return float
     */
    public function getTemperature(): float
    {
        return (float)$this->get('temperature');
    }

    /**
     * 获取最大Token数
     *
     * @return int
     */
    public function getMaxToken(): int
    {
        return (int)$this->get('maxToken');
    }

    /**
     * 是否启用缓存
     *
     * @return bool
     */
    public function isCacheEnabled(): bool
    {
        return (bool)$this->get('cacheEnabled');
    }

    /**
     * 获取缓存TTL
     *
     * @return int
     */
    public function getCacheTtl(): int
    {
        return (int)$this->get('cacheTtl');
    }

    /**
     * 是否启用日志
     *
     * @return bool
     */
    public function isLogEnabled(): bool
    {
        return (bool)$this->get('logEnabled');
    }

    /**
     * 获取日志级别
     *
     * @return string
     */
    public function getLogLevel(): string
    {
        return $this->get('logLevel');
    }

    /**
     * 获取重试次数
     *
     * @return int
     */
    public function getRetryAttempts(): int
    {
        return (int)$this->get('retryAttempts');
    }

    /**
     * 获取重试延迟（毫秒）
     *
     * @return int
     */
    public function getRetryDelay(): int
    {
        return (int)$this->get('retryDelay');
    }

    /**
     * 获取缓存类型
     *
     * @return string
     */
    public function getCacheType(): string
    {
        return $this->get('cache');
    }

    /**
     * 获取缓存目录
     *
     * @return string|null
     */
    public function getCacheDir(): ?string
    {
        return $this->get('cacheDir');
    }

    /**
     * 获取日志文件路径
     *
     * @return string|null
     */
    public function getLogFile(): ?string
    {
        return $this->get('logFile');
    }

    /**
     * 获取所有配置
     *
     * @return array
     */
    public function all(): array
    {
        return $this->config;
    }

    /**
     * 验证必需配置
     *
     * @param array $config
     * @throws Exception
     */
    private function validateRequiredConfig(array $config): void
    {
        if (empty($config['apiKey'])) {
            throw new Exception("API密钥不能为空");
        }
    }

    /**
     * 验证配置类型
     *
     * @throws Exception
     */
    private function validateConfigTypes(): void
    {
        $numericFields = ['timeout', 'connectTimeout', 'maxToken', 'retryAttempts', 'retryDelay', 'cacheTtl'];
        foreach ($numericFields as $field) {
            if (!is_numeric($this->config[$field])) {
                throw new Exception("配置项 {$field} 必须是数字");
            }
        }

        $floatFields = ['temperature'];
        foreach ($floatFields as $field) {
            if (!is_numeric($this->config[$field])) {
                throw new Exception("配置项 {$field} 必须是数字");
            }
        }

        $booleanFields = ['cacheEnabled', 'logEnabled'];
        foreach ($booleanFields as $field) {
            if (!is_bool($this->config[$field])) {
                throw new Exception("配置项 {$field} 必须是布尔值");
            }
        }

        $stringFields = ['baseUrl', 'model', 'logLevel'];
        foreach ($stringFields as $field) {
            if (!is_string($this->config[$field])) {
                throw new Exception("配置项 {$field} 必须是字符串");
            }
        }
    }
}