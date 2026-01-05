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
     * @return array 响应结果
     * @throws RuntimeException
     */
    public function chat(array $messages, string $model = 'qwen-turbo', float $temperature = 0.7, int $maxTokens = 2000): array
    {
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
            $result = json_decode($response->getBody()->getContents(), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new RuntimeException('Invalid JSON response from API');
            }

            return $result;
        } catch (GuzzleException $e) {
            throw new RuntimeException('API request failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * 批量发送聊天请求
     *
     * @param array $requests 请求数组，每个元素包含messages和其他参数
     * @param int $concurrency 并发数
     * @return array 响应结果数组
     * @throws RuntimeException
     */
    public function batchChat(array $requests, int $concurrency = 5): array
    {
        $promises = [];
        $results = [];

        foreach ($requests as $index => $request) {
            $messages = $request['messages'] ?? [];
            $model = $request['model'];
            $temperature = $request['temperature'] ?? 0.7;
            $maxTokens = $request['max_tokens'] ?? 2000;

            $promises[$index] = $this->httpClient->postAsync($this->baseUrl, [
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

        $responses = \GuzzleHttp\Promise\Utils::settle($promises)->wait();

        foreach ($responses as $index => $response) {
            if ($response['state'] === 'fulfilled') {
                $result = json_decode($response['value']->getBody()->getContents(), true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $results[$index] = $result;
                } else {
                    $results[$index] = ['error' => 'Invalid JSON response'];
                }
            } else {
                $results[$index] = ['error' => $response['reason']->getMessage()];
            }
        }

        return $results;
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