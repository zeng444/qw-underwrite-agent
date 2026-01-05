<?php

declare(strict_types=1);

namespace Janfish\UnderwriteAgent;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Janfish\UnderwriteAgent\Exception\RuntimeException;

/**
 * Qwen SDK 封装层
 * 负责与千问API的直接交互，提供基础的API调用功能
 */
class QwenClient
{
    private Client $httpClient;
    private string $apiKey;
    private string $baseUrl;
    private int $timeout;
    private int $connectTimeout;

    /**
     * @param string $apiKey API密钥
     * @param string $baseUrl 基础URL
     * @param int $timeout 请求超时时间（秒）
     * @param int $connectTimeout 连接超时时间（秒）
     */
    public function __construct(
        string $apiKey,
        string $baseUrl = 'https://dashscope.aliyuncs.com/api/v1/services/aigc/text-generation/generation',
        int    $timeout = 30,
        int    $connectTimeout = 10
    )
    {
        $this->apiKey = $apiKey;
        $this->baseUrl = $baseUrl;
        $this->timeout = $timeout;
        $this->connectTimeout = $connectTimeout;

        $this->httpClient = new Client([
            'timeout' => $this->timeout,
            'connect_timeout' => $this->connectTimeout,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ]);
    }

    /**
     * 发送单个聊天请求
     *
     * @param array $messages 消息数组
     * @param string $model 模型名称
     * @param float $temperature 温度参数
     * @param int $maxTokens 最大token数
     * @param int $retryAttempts 重试次数
     * @param int $retryDelay 重试延迟（毫秒）
     * @return array 响应结果
     * @throws RuntimeException
     */
    public function chat(array $messages, string $model = 'qwen-turbo', float $temperature = 0.7, int $maxTokens = 2000, int $retryAttempts = 3, int $retryDelay = 1000): array
    {
        // 参数验证
        if (empty($messages)) {
            throw new RuntimeException('消息数组不能为空');
        }
        if (empty($model)) {
            throw new RuntimeException('模型名称不能为空');
        }
        if ($temperature < 0 || $temperature > 2) {
            throw new RuntimeException('temperature参数必须在0-2之间');
        }
        if ($maxTokens < 1 || $maxTokens > 32768) {
            throw new RuntimeException('maxTokens参数必须在1-32768之间');
        }

        $lastException = null;
        
        for ($attempt = 1; $attempt <= $retryAttempts; $attempt++) {
            try {
                $response = $this->httpClient->post($this->baseUrl, [
                    'json' => [
                        'model' => $model,
                        'input' => [
                            'messages' => $messages,
                        ],
                        'parameters' => [
                            'temperature' => $temperature,
                            'max_tokens' => $maxTokens,
                            'result_format' => 'message',
                        ],
                    ],
                ]);
                
                $responseBody = $response->getBody()->getContents();
                $result = json_decode($responseBody, true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new RuntimeException('API返回无效的JSON格式: ' . json_last_error_msg());
                }
                
                // 验证响应结构
                if (!isset($result['output']['choices'][0]['message']['content'])) {
                    throw new RuntimeException('API响应格式异常：缺少必要字段');
                }
                
                return $result;
                
            } catch (GuzzleException $e) {
                $lastException = new RuntimeException(
                    "API请求失败 (尝试 {$attempt}/{$retryAttempts}): " . $e->getMessage(), 
                    $e->getCode(), 
                    $e
                );
                
                // 如果是最后一次尝试，不再重试
                if ($attempt === $retryAttempts) {
                    break;
                }
                
                // 等待重试延迟
                usleep($retryDelay * 1000);
                
            } catch (RuntimeException $e) {
                // 业务逻辑错误不重试
                throw $e;
            }
        }
        
        throw $lastException;
    }

    /**
     * 批量发送聊天请求
     *
     * @param array $requests 请求数组，每个元素包含messages和其他参数
     * @param int $concurrency 并发数
     * @param int $retryAttempts 重试次数
     * @param int $retryDelay 重试延迟（毫秒）
     * @return array 响应结果数组
     * @throws RuntimeException
     */
    public function batchChat(array $requests, int $concurrency = 5, int $retryAttempts = 3, int $retryDelay = 1000): array
    {
        if (empty($requests)) {
            throw new RuntimeException('请求数组不能为空');
        }
        
        if ($concurrency < 1 || $concurrency > 20) {
            throw new RuntimeException('并发数必须在1-20之间');
        }

        $promises = [];
        $results = [];
        $requestCount = count($requests);
        
        // 使用分块处理，避免内存溢出
        $chunks = array_chunk($requests, $concurrency, true);
        
        foreach ($chunks as $chunkIndex => $chunk) {
            $chunkPromises = [];
            
            foreach ($chunk as $index => $request) {
                // 验证请求参数
                if (!isset($request['messages']) || !is_array($request['messages'])) {
                    $results[$index] = ['error' => '缺少messages参数或格式错误'];
                    continue;
                }
                
                if (empty($request['model'])) {
                    $results[$index] = ['error' => '缺少model参数'];
                    continue;
                }
                
                $messages = $request['messages'];
                $model = $request['model'];
                $temperature = $request['temperature'] ?? 0.7;
                $maxTokens = $request['max_tokens'] ?? 2000;
                
                // 参数验证
                if ($temperature < 0 || $temperature > 2) {
                    $results[$index] = ['error' => 'temperature参数必须在0-2之间'];
                    continue;
                }
                
                if ($maxTokens < 1 || $maxTokens > 32768) {
                    $results[$index] = ['error' => 'maxTokens参数必须在1-32768之间'];
                    continue;
                }

                $chunkPromises[$index] = $this->httpClient->postAsync($this->baseUrl, [
                    'json' => [
                        'model' => $model,
                        'input' => [
                            'messages' => $messages,
                        ],
                        'parameters' => [
                            'temperature' => $temperature,
                            'max_tokens' => $maxTokens,
                            'result_format' => 'message',
                        ],
                    ],
                ]);
            }
            
            if (!empty($chunkPromises)) {
                $chunkResponses = \GuzzleHttp\Promise\Utils::settle($chunkPromises)->wait();
                
                foreach ($chunkResponses as $index => $response) {
                    if ($response['state'] === 'fulfilled') {
                        $responseBody = $response['value']->getBody()->getContents();
                        $result = json_decode($responseBody, true);
                        
                        if (json_last_error() === JSON_ERROR_NONE) {
                            // 验证响应结构
                            if (isset($result['output']['choices'][0]['message']['content'])) {
                                $results[$index] = $result;
                            } else {
                                $results[$index] = ['error' => 'API响应格式异常：缺少必要字段'];
                            }
                        } else {
                            $results[$index] = ['error' => 'API返回无效的JSON格式: ' . json_last_error_msg()];
                        }
                    } else {
                        $errorMessage = $response['reason']->getMessage();
                        
                        // 重试机制
                        if ($retryAttempts > 1) {
                            $retryResult = $this->retrySingleRequest(
                                $requests[$index], 
                                $retryAttempts - 1, 
                                $retryDelay
                            );
                            
                            if (isset($retryResult['error'])) {
                                $results[$index] = ['error' => "请求失败: {$errorMessage}, 重试后: {$retryResult['error']}"];
                            } else {
                                $results[$index] = $retryResult;
                            }
                        } else {
                            $results[$index] = ['error' => "请求失败: {$errorMessage}"];
                        }
                    }
                }
            }
            
            // 可选：在块之间添加短暂延迟，避免API限流
            if ($chunkIndex < count($chunks) - 1) {
                usleep(20000); // 20ms延迟
            }
        }
        
        return $results;
    }

    /**
     * 重试单个请求
     *
     * @param array $request 请求数据
     * @param int $retryAttempts 剩余重试次数
     * @param int $retryDelay 重试延迟
     * @return array 响应结果
     * @throws GuzzleException
     */
    private function retrySingleRequest(array $request, int $retryAttempts, int $retryDelay): array
    {
        for ($attempt = 1; $attempt <= $retryAttempts; $attempt++) {
            try {
                $messages = $request['messages'] ?? [];
                $model = $request['model'];
                $temperature = $request['temperature'] ?? 0.7;
                $maxTokens = $request['max_tokens'] ?? 2000;
                
                $response = $this->httpClient->post($this->baseUrl, [
                    'json' => [
                        'model' => $model,
                        'input' => [
                            'messages' => $messages,
                        ],
                        'parameters' => [
                            'temperature' => $temperature,
                            'max_tokens' => $maxTokens,
                            'result_format' => 'message',
                        ],
                    ],
                ]);
                
                $responseBody = $response->getBody()->getContents();
                $result = json_decode($responseBody, true);
                
                if (json_last_error() === JSON_ERROR_NONE && 
                    isset($result['output']['choices'][0]['message']['content'])) {
                    return $result;
                }
                
            } catch (\Exception $e) {
                if ($attempt === $retryAttempts) {
                    return ['error' => $e->getMessage()];
                }
                usleep($retryDelay * 1000);
            }
        }
        
        return ['error' => '重试次数耗尽'];
    }

    /**
     * 构建消息数组
     *
     * @param string $systemPrompt 系统提示词
     * @param string $userPrompt 用户提示词
     * @return array 消息数组
     */
    public static function buildMessages(string $systemPrompt, string $userPrompt): array
    {
        return [
            [
                'role' => 'system',
                'content' => $systemPrompt,
            ],
            [
                'role' => 'user',
                'content' => $userPrompt,
            ],
        ];
    }

    /**
     * 获取API密钥
     *
     * @return string
     */
    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    /**
     * 获取基础URL
     *
     * @return string
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }
}