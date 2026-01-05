<?php

declare(strict_types=1);

namespace Janfish\UnderwriteAgent;

use Janfish\UnderwriteAgent\Exception\Exception;
use Janfish\UnderwriteAgent\Exception\RuntimeException;
use Janfish\UnderwriteAgent\Exception\ValidationException;
use Janfish\UnderwriteAgent\Prompt\Prompt;
use Janfish\UnderwriteAgent\Config\ConfigManager;
use Janfish\UnderwriteAgent\Cache\CacheInterface;
use Janfish\UnderwriteAgent\Cache\MemoryCache;
use Janfish\UnderwriteAgent\Cache\FileCache;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * 保险承保分析业务层
 * 负责业务逻辑和提示词定义，调用QwenClient进行AI分析
 */
class UnderwriteAgent
{
    /** @var QwenClient SDK客户端，负责与通义千问API交互 */
    private QwenClient $qwenClient;
    
    /** @var ConfigManager 配置管理器，统一管理所有配置项 */
    private ConfigManager $configManager;
    
    /** @var LoggerInterface PSR-3兼容的日志记录器 */
    private LoggerInterface $logger;
    
    /** @var CacheInterface 缓存接口，支持内存缓存和文件缓存 */
    private CacheInterface $cache;
    
    /** @var float AI模型的温度参数，控制输出的随机性（0-1之间） */
    private float $temperature;
    
    /** @var int AI模型生成的最大token数量 */
    private int $maxToken;
    
    /** @var string 使用的AI模型名称 */
    private string $model;


    /**
     * @param array $config 配置数组
     *  - apiKey: string API密钥（必需）
     *  - baseUrl: string API地址（可选）
     *  - timeout: int 请求超时时间（可选，默认30秒）
     *  - connectTimeout: int 连接超时时间（可选，默认10秒）
     * @throws Exception
     */
    public function __construct(array $config)
    {
        $this->configManager = new ConfigManager($config);
        $this->logger = new NullLogger();

        // 根据配置初始化缓存
        $this->initializeCache();

        // 使用配置管理器获取配置
        $this->temperature = $this->configManager->getTemperature();
        $this->maxToken = $this->configManager->getMaxToken();
        $this->model = $this->configManager->getModel();

        // 内部实例化QwenClient
        $this->qwenClient = new QwenClient(
            $this->configManager->getApiKey(),
            $this->configManager->getBaseUrl(),
            $this->configManager->getTimeout(),
            $this->configManager->getConnectTimeout()
        );
    }

    /**
     * 设置日志器
     *
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * 设置缓存
     *
     * @param CacheInterface $cache
     */
    public function setCache(CacheInterface $cache): void
    {
        $this->cache = $cache;
    }

    /**
     * 根据配置初始化缓存
     * 
     * 缓存初始化逻辑说明：
     * - memory: 使用内存缓存，数据存储在内存中，速度快但重启后数据丢失
     * - file: 使用文件缓存，数据持久化到文件系统，适合生产环境
     * - none: 使用空缓存实现，所有缓存操作都返回默认值，相当于禁用缓存
     * 
     * 默认行为：
     * - 如果用户没有指定cacheDir，文件缓存会使用系统临时目录下的underwrite_cache文件夹
     * - 空缓存实现确保代码一致性，无需在业务逻辑中判断缓存是否启用
     */
    private function initializeCache(): void
    {
        $cacheType = $this->configManager->getCacheType();

        switch ($cacheType) {
            case 'memory':
                // 内存缓存：适合开发环境，读写速度快，但数据不持久化
                $this->cache = new MemoryCache();
                break;
            case 'file':
                // 文件缓存：适合生产环境，数据持久化存储
                // 如果用户没有指定缓存目录，使用系统临时目录下的默认目录
                $cacheDir = $this->configManager->getCacheDir() ?? sys_get_temp_dir() . '/underwrite_cache';
                $this->cache = new FileCache($cacheDir);
                break;
            case 'none':
            default:
                // 空缓存实现：禁用缓存功能，所有操作返回默认值
                // 这种设计保持接口一致性，业务代码无需判断缓存是否启用
                $this->cache = new class implements CacheInterface {
                    public function get(string $key, $default = null)
                    {
                        return $default;
                    }

                    public function set(string $key, $value, int $ttl = 3600): bool
                    {
                        return true;
                    }

                    public function delete(string $key): bool
                    {
                        return true;
                    }

                    public function clear(): bool
                    {
                        return true;
                    }

                    public function has(string $key): bool
                    {
                        return false;
                    }

                    public function getMultiple(array $keys, $default = null): array
                    {
                        return array_fill_keys($keys, $default);
                    }

                    public function setMultiple(array $values, int $ttl = 3600): bool
                    {
                        return true;
                    }

                    public function deleteMultiple(array $keys): bool
                    {
                        return true;
                    }
                };
                break;
        }
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return $this->configManager->all();
    }


    /**
     * 执行单次承保条件分析
     * 定义车险承保分析的系统提示词和用户提示词
     *
     * @param array $params 分析参数数组
     * @param string $user 用户标识
     * @return array 解析后的业务数据
     * @throws Exception|RuntimeException
     */
    public function analyze(array $params, string $user = 'default'): array
    {
        $this->logger->info("开始承保分析", ['user' => $user, 'params' => $this->sanitizeParams($params)]);

        // 生成缓存键
        $cacheKey = $this->generateCacheKey($params, $user);

        // 检查缓存
        if ($this->configManager->isCacheEnabled() && $this->cache->has($cacheKey)) {
            $cachedResult = $this->cache->get($cacheKey);
            $this->logger->info("命中缓存", ['user' => $user, 'cache_key' => $cacheKey]);
            return $cachedResult;
        }

        try {
            $this->validateParams($params);

            // 定义车险承保分析的系统提示词
            $systemPrompt = $this->systemPrompt();

            // 定义用户提示词模板
            $userPrompt = $this->buildUserPrompt($params);
            $userPrompt .= "\n注意：**以上任一条件【导致佣金变化】，必须生成独立规则**\n";

            $this->logger->debug("构建请求消息", ['systemPrompt' => substr($systemPrompt, 0, 100) . '...', 'userPrompt' => substr($userPrompt, 0, 200) . '...']);

            // 使用QwenClient发送请求
            $messages = QwenClient::buildMessages($systemPrompt, $userPrompt);
            $response = $this->qwenClient->chat($messages, $this->model, $this->temperature, $this->maxToken);

            $result = $this->parseResponse($response);

            // 缓存结果
            if ($this->configManager->isCacheEnabled()) {
                $this->cache->set($cacheKey, $result, $this->configManager->getCacheTtl());
                $this->logger->info("缓存结果", ['user' => $user, 'cache_key' => $cacheKey, 'ttl' => $this->configManager->getCacheTtl()]);
            }

            $this->logger->info("承保分析完成", ['user' => $user, 'result' => $this->sanitizeResult($result)]);

            return $result;

        } catch (\Exception $e) {
            $this->logger->error("承保分析失败", ['user' => $user, 'error' => $e->getMessage(), 'params' => $this->sanitizeParams($params)]);
            throw $e;
        }
    }

    /**
     * 执行批量承保条件分析（并发请求）
     * 定义批量分析的系统提示词
     *
     * @param array $requests 请求数组
     * @param int $concurrency 并发数限制
     * @return array 包含每个请求结果的数组
     * @throws RuntimeException|Exception
     */
    public function batchAnalyze(array $requests, int $concurrency = 5): array
    {
        $this->logger->info("开始批量承保分析", ['request_count' => count($requests), 'concurrency' => $concurrency]);

        if (empty($requests)) {
            $this->logger->error("批量分析失败：请求数组为空");
            throw new Exception("Requests cannot be empty");
        }

        try {
            // 定义批量分析的系统提示词
            $systemPrompt = $this->systemPrompt();

            // 构建批量请求
            $batchRequests = [];
            foreach ($requests as $index => $request) {
                $params = $request['params'] ?? [];
                $this->logger->debug("处理批量请求", ['index' => $index, 'params' => $this->sanitizeParams($params)]);

                try {
                    $this->validateParams($params);
                    $userPrompt = $this->buildUserPrompt($params);
                    $batchRequests[$index] = [
                        'messages' => QwenClient::buildMessages($systemPrompt, $userPrompt),
                        'model' => $this->model,
                        'temperature' => $this->temperature,
                        'max_tokens' => $this->maxToken,
                    ];
                } catch (\Exception $e) {
                    $this->logger->warning("批量请求参数验证失败", ['index' => $index, 'error' => $e->getMessage()]);
                    // 记录错误但继续处理其他请求
                    continue;
                }
            }

            if (empty($batchRequests)) {
                $this->logger->error("批量分析失败：没有有效的请求");
                throw new Exception("No valid requests to process");
            }

            $this->logger->info("发送批量请求", ['valid_request_count' => count($batchRequests)]);

            // 使用QwenClient批量发送请求
            $responses = $this->qwenClient->batchChat($batchRequests, $concurrency);

            // 处理响应结果
            $results = [];
            $successCount = 0;
            $errorCount = 0;

            foreach ($responses as $index => $response) {
                if (isset($response['error'])) {
                    $results[$index] = [
                        'index' => $index,
                        'success' => false,
                        'error' => $response['error'],
                    ];
                    $errorCount++;
                    $this->logger->warning("批量请求处理失败", ['index' => $index, 'error' => $response['error']]);
                } else {
                    try {
                        $parsedData = $this->parseResponse($response);
                        $results[$index] = [
                            'index' => $index,
                            'success' => true,
                            'data' => $parsedData,
                        ];
                        $successCount++;
                        $this->logger->debug("批量请求处理成功", ['index' => $index]);
                    } catch (\Exception $e) {
                        $results[$index] = [
                            'index' => $index,
                            'success' => false,
                            'error' => 'Response processing failed: ' . $e->getMessage(),
                        ];
                        $errorCount++;
                        $this->logger->warning("批量响应处理失败", ['index' => $index, 'error' => $e->getMessage()]);
                    }
                }
            }

            $this->logger->info("批量承保分析完成", [
                'total_requests' => count($requests),
                'valid_requests' => count($batchRequests),
                'success_count' => $successCount,
                'error_count' => $errorCount
            ]);

            return $results;

        } catch (\Exception $e) {
            $this->logger->error("批量分析异常", ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 执行综合承保条件分析
     * 定义综合分析的系统提示词
     *
     * @param array $requests 请求数组
     * @return array 解析后的业务数据
     * @throws Exception|RuntimeException
     * @throws \Exception
     */
    public function compositeAnalyze(array $requests): array
    {
        $this->logger->info("开始综合分析", ['request_count' => count($requests)]);

        if (empty($requests)) {
            $this->logger->error("综合分析失败：请求数组为空");
            throw new Exception('Requests cannot be empty');
        }

        try {
            // 定义综合分析的系统提示词
            $systemPrompt = $this->systemPrompt();

            // 构建综合分析的用户提示词
            $userPrompt = $this->buildCompositePrompt($requests);
            $userPrompt .= "\n注意：**以上每组承保信息互不影响，但组内任一条件【导致佣金变化】，必须生成独立规则**";

            $this->logger->debug("构建综合分析请求", ['userPrompt' => substr($userPrompt, 0, 300) . '...']);

            // 使用QwenClient发送请求
            $messages = QwenClient::buildMessages($systemPrompt, $userPrompt);
            $response = $this->qwenClient->chat($messages, $this->model, $this->temperature, $this->maxToken);

            $result = $this->parseResponse($response);

            $this->logger->info("综合分析完成", ['request_count' => count($requests)]);

            return $result;

        } catch (\Exception $e) {
            $this->logger->error("综合分析失败", ['error' => $e->getMessage(), 'request_count' => count($requests)]);
            throw $e;
        }
    }

    /**
     * 构建车险承保分析提示词
     *
     * @param array $params 承保参数
     * @return string 用户提示词
     */
    private function buildUserPrompt(array $params): string
    {
        $prompt = "承保保险公司：{$params['company']}\n";
        if (isset($params['type'])) {
            $prompt .= "投保信息：{$params['type']}\n";
        }
        if (isset($params['car'])) {
            $prompt .= "车况信息：{$params['car']}\n";
        }
        if (isset($params['region'])) {
            $prompt .= "承保区域：{$params['region']}\n";
        }
        if (isset($params['VCIAgentRate'])) {
            $prompt .= "代理人商业险佣金比：{$params['VCIAgentRate']}\n";
        }
        if (isset($params['TCIAgentRate'])) {
            $prompt .= "代理人交强险佣金比：{$params['TCIAgentRate']}\n";
        }
        if (isset($params['NCAgentRate'])) {
            $prompt .= "代理人非车险佣金比：{$params['NCAgentRate']}\n";
        }
        if (isset($params['TCIRate'])) {
            $prompt .= "非代理交强险佣金比：{$params['TCIRate']}\n";
        }
        if (isset($params['VCIRate'])) {
            $prompt .= "非代理商业险佣金比：{$params['VCIRate']}\n";
        }
        if (isset($params['NCRate'])) {
            $prompt .= "非代理人非车险佣金比：{$params['NCRate']}\n";
        }
        if (isset($params['policy'])) {
            $prompt .= "非代理人承保条件：{$params['policy']}\n";
        }
        if (isset($params['agentPolicy'])) {
            $prompt .= "代理承保条件：{$params['agentPolicy']}\n";
        }
        return $prompt;
    }

    /**
     * 构建综合分析提示词
     *
     * @param array $requests 多组承保请求
     * @return string 综合分析用户提示词
     */
    private function buildCompositePrompt(array $requests): string
    {
        // 定义批量分析的系统提示词
        $composite = [];
        foreach ($requests as $index => $request) {
            if (!isset($request['params'])) {
                throw new RuntimeException("请求参数[$index]缺少params字段");
            }
            $composite[] = $this->buildUserPrompt($request['params']);
        }
        return implode("\n", $composite);
    }

    /**
     * 验证配置参数
     *
     * @param array $config 配置数组
     * @throws Exception
     */
    private function validateConfig(array $config): void
    {
        if (empty($config['apiKey'])) {
            throw new Exception("API密钥不能为空");
        }
    }

    /**
     * 验证参数
     *
     * @param array $params 参数数组
     * @throws ValidationException
     */
    private function validateParams(array $params): void
    {
        $requiredFields = ['company', 'type', 'car', 'region', 'policy', 'agentPolicy'];

        foreach ($requiredFields as $field) {
            if (!isset($params[$field]) || !is_string($params[$field]) || empty(trim($params[$field]))) {
                throw new ValidationException("必需参数缺失或格式错误: {$field}");
            }
        }

        // 验证字符串长度
        $maxLengths = [
            'company' => 50,
            'type' => 50,
            'car' => 100,
            'region' => 200,
            'policy' => 1000,
            'agentPolicy' => 1000
        ];

        foreach ($maxLengths as $field => $maxLength) {
            if (isset($params[$field]) && strlen($params[$field]) > $maxLength) {
                throw new ValidationException("参数 {$field} 超出最大长度限制 ({$maxLength}字符)");
            }
        }

        // 验证费率参数（如果存在）
        $rateFields = ['VCIAgentRate', 'TCIAgentRate', 'NCAgentRate', 'TCIRate', 'VCIRate', 'NCRate'];
        foreach ($rateFields as $field) {
            if (isset($params[$field])) {
                if (!is_numeric($params[$field])) {
                    throw new ValidationException("费率参数 {$field} 必须是数字");
                }
                $rate = (float)$params[$field];
                if ($rate < 0 || $rate > 1) {
                    throw new ValidationException("费率参数 {$field} 必须在0-1之间");
                }
            }
        }
    }

    /**
     * 解析API响应
     *
     * @param array $response API响应
     * @return array 解析后的业务数据
     * @throws RuntimeException
     */
    private function parseResponse(array $response): array
    {
        $this->logger->debug("开始解析API响应", ['response_keys' => array_keys($response)]);

        if (isset($response['choices'][0]['message']['content'])) {
            $content = $response['choices'][0]['message']['content'];
        } // 格式2: output['choices'][0]['message']['content'] (某些API版本)
        elseif (isset($response['output']['choices'][0]['message']['content'])) {
            $content = $response['output']['choices'][0]['message']['content'];
        } // 格式3: output['text'] (简化格式)
        elseif (isset($response['output']['text'])) {
            $content = $response['output']['text'];
        } // 格式4: choices[0]['text'] (另一种格式)
        elseif (isset($response['choices'][0]['text'])) {
            $content = $response['choices'][0]['text'];
        } // 格式5: text (直接文本格式)
        elseif (isset($response['text'])) {
            $content = $response['text'];
        } else {
            $errorMsg = 'Invalid API response format. Available keys: ' . implode(', ', array_keys($response));
            $this->logger->error("API响应格式无效", ['available_keys' => array_keys($response)]);
            throw new RuntimeException($errorMsg);
        }

        $this->logger->debug("提取到响应内容", ['content_length' => strlen($content)]);

        // 尝试解析JSON内容
        $result = json_decode($content, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            $this->logger->debug("JSON解析成功", ['result_keys' => array_keys($result)]);
            return $result;
        }

        // 如果JSON解析失败，返回包含原始内容的数组
        $this->logger->warning("JSON解析失败", ['error' => json_last_error_msg(), 'content_preview' => substr($content, 0, 100)]);

        return [
            'content' => $content,
            'parsed' => false,
            'error' => 'Content is not valid JSON: ' . json_last_error_msg()
        ];
    }


    /**
     * @return string
     */
    private function systemPrompt(): string
    {
        return Prompt::SYSTEM_MSG;
    }

    /**
     * 消毒参数（移除敏感信息）
     *
     * @param array $params
     * @return array
     */
    private function sanitizeParams(array $params): array
    {
        $sanitized = $params;
        // 移除可能包含敏感信息的字段
        unset($sanitized['apiKey'], $sanitized['password'], $sanitized['secret']);
        return $sanitized;
    }

    /**
     * 消毒结果（移除敏感信息）
     *
     * @param array $result
     * @return array
     */
    private function sanitizeResult(array $result): array
    {
        // 如果结果太大，只记录关键信息
        if (count($result) > 10) {
            return [
                'status' => 'success',
                'fields_count' => count($result),
                'sample_keys' => array_slice(array_keys($result), 0, 5)
            ];
        }
        return $result;
    }

    /**
     * 生成缓存键
     *
     * @param array $params
     * @param string $user
     * @return string
     */
    private function generateCacheKey(array $params, string $user): string
    {
        // 移除可能影响缓存命中的动态字段
        $cacheParams = $params;
        unset($cacheParams['timestamp'], $cacheParams['requestId']);

        $keyData = [
            'user' => $user,
            'params' => $cacheParams,
            'model' => $this->model,
            'temperature' => $this->temperature,
            'version' => '1.0'
        ];

        // 手动排序键以确保一致的缓存键生成
        ksort($keyData);
        if (isset($keyData['params']) && is_array($keyData['params'])) {
            ksort($keyData['params']);
        }

        return 'underwrite:' . md5(json_encode($keyData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}