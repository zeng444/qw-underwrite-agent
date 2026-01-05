<?php

declare(strict_types=1);

namespace Janfish\UnderwriteAgent;

use Janfish\UnderwriteAgent\Exception\Exception;
use Janfish\UnderwriteAgent\Exception\RuntimeException;
use Janfish\UnderwriteAgent\Prompt\Prompt;

/**
 * 保险承保分析业务层
 * 负责业务逻辑和提示词定义，调用QwenClient进行AI分析
 */
class UnderwriteAgent
{
    private QwenClient $qwenClient;
    private array $config;

    private float $temperature = 0.3;
    private int $maxToken = 8192;
    private string $model = 'qwen3-max';

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
        $this->validateConfig($config);
        $this->config = $config;

        if (isset($config['maxToken '])) {
            $this->maxToken = $config['maxToken '];
        }
        if (isset($config['temperature '])) {
            $this->temperature = $config['temperature '];
        }
        if (isset($config['model '])) {
            $this->model = $config['model '];
        }

        // 内部实例化QwenClient
        $this->qwenClient = new QwenClient(
            $config['apiKey'],
            $config['baseUrl'] ?? 'https://dashscope.aliyuncs.com/api/v1/services/aigc/text-generation/generation',
            $config['timeout'] ?? 600,
            $config['connectTimeout'] ?? 10
        );
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
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
        $this->validateParams($params);

        // 定义车险承保分析的系统提示词
        $systemPrompt = $this->systemPrompt();

        // 定义用户提示词模板
        $userPrompt = $this->buildUserPrompt($params);
        $userPrompt .= "\n注意：**以上任一条件【导致佣金变化】，必须生成独立规则**\n";
        // 使用QwenClient发送请求
        $messages = QwenClient::buildMessages($systemPrompt, $userPrompt);
        $response = $this->qwenClient->chat($messages,$this->model,$this->temperature,$this->maxToken);

        return $this->parseResponse($response);
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
        if (empty($requests)) {
            throw new Exception("Requests cannot be empty");
        }

        // 定义批量分析的系统提示词
        $systemPrompt = $this->systemPrompt();

        // 构建批量请求
        $batchRequests = [];
        foreach ($requests as $index => $request) {
            $params = $request['params'] ?? [];
            $this->validateParams($params);
            $userPrompt = $this->buildUserPrompt($params);
            $batchRequests[$index] = [
                'messages' => QwenClient::buildMessages($systemPrompt, $userPrompt),
                'model' => $this->model,
                'temperature' => $this->temperature,
                'max_tokens' => $this->maxToken,
            ];
        }

        // 使用QwenClient批量发送请求
        $responses = $this->qwenClient->batchChat($batchRequests, $concurrency);

        // 处理响应结果
        $results = [];
        foreach ($responses as $index => $response) {
            if (isset($response['error'])) {
                $results[$index] = [
                    'index' => $index,
                    'success' => false,
                    'error' => $response['error'],
                ];
            } else {
                try {
                    $parsedData = $this->parseResponse($response);
                    $results[$index] = [
                        'index' => $index,
                        'success' => true,
                        'data' => $parsedData,
                    ];
                } catch (\Exception $e) {
                    $results[$index] = [
                        'index' => $index,
                        'success' => false,
                        'error' => 'Response processing failed: ' . $e->getMessage(),
                    ];
                }
            }
        }

        return $results;
    }

    /**
     * 执行综合承保条件分析
     * 定义综合分析的系统提示词
     *
     * @param array $requests 请求数组
     * @return array 解析后的业务数据
     * @throws Exception|RuntimeException
     */
    public function compositeAnalyze(array $requests): array
    {
        if (empty($requests)) {
            throw new Exception('Requests cannot be empty');
        }

        // 定义综合分析的系统提示词
        $systemPrompt = $this->systemPrompt();

        // 构建综合分析的用户提示词
        $userPrompt = $this->buildCompositePrompt($requests);
        $userPrompt .= "\n注意：**以上每组承保信息互不影响，但组内任一条件【导致佣金变化】，必须生成独立规则**";
        // 使用QwenClient发送请求
        $messages = QwenClient::buildMessages($systemPrompt, $userPrompt);
        $response = $this->qwenClient->chat($messages, $this->model, $this->temperature, $this->maxToken);

        return $this->parseResponse($response);
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
     * @throws Exception
     */
    private function validateParams(array $params): void
    {
        $requiredFields = ['company', 'type', 'car', 'region', 'policy', 'agentPolicy'];

        foreach ($requiredFields as $field) {
            if (empty($params[$field])) {
                throw new Exception("Missing required parameter: {$field}");
            }
        }
        // 验证费率参数（如果存在）
        $rateFields = ['VCIAgentRate', 'TCIAgentRate', 'NCAgentRate', 'TCIRate', 'VCIRate', 'NCRate'];
        foreach ($rateFields as $field) {
            if (isset($params[$field])) {
                $rate = (float)$params[$field];
                if ($rate < 0 || $rate > 1) {
                    throw new Exception("Invalid rate value for {$field}: must be between 0 and 1");
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
            throw new RuntimeException('Invalid API response format. Available keys: ' . implode(', ', array_keys($response)));
        }

        // 尝试解析JSON内容
        $result = json_decode($content, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $result;
        }

        // 如果JSON解析失败，返回包含原始内容的数组
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
}