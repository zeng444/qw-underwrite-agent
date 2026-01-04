<?php

declare(strict_types=1);

namespace Janfish\UnderwriteAgent;

use Janfish\UnderwriteAgent\Exception\Exception;
use Janfish\UnderwriteAgent\Exception\RuntimeException;

/**
 * 保险承保分析业务层
 * 负责业务逻辑和提示词定义，调用QwenClient进行AI分析
 */
class UnderwriteAgent
{
    private QwenClient $qwenClient;
    private array $config;

    /**
     * @param array $config 配置数组
     *  - apiKey: string API密钥（必需）
     *  - baseUrl: string API地址（可选）
     *  - timeout: int 请求超时时间（可选，默认30秒）
     *  - connectTimeout: int 连接超时时间（可选，默认10秒）
     */
    public function __construct(array $config)
    {
        $this->validateConfig($config);
        $this->config = $config;
        
        // 内部实例化QwenClient
        $this->qwenClient = new QwenClient(
            $config['apiKey'],
            $config['baseUrl'] ?? 'https://dashscope.aliyuncs.com/api/v1/services/aigc/text-generation/generation',
            $config['timeout'] ?? 30,
            $config['connectTimeout'] ?? 10
        );
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
        $systemPrompt = "你是一位专业的车险承保分析专家。请根据提供的承保条件，从风险评估、定价策略、市场合规性等专业角度进行详细分析。返回格式必须是有效的JSON格式，包含以下字段：
        - riskAssessment: 风险评估（高/中/低）
        - riskFactors: 主要风险因素数组
        - pricingAnalysis: 定价分析
        - marketCompliance: 市场合规性分析
        - recommendations: 承保建议数组
        - confidenceLevel: 分析置信度（0-1）";
        
        // 定义用户提示词模板
        $userPrompt = $this->buildCarInsurancePrompt($params, $user);
        
        // 使用QwenClient发送请求
        $messages = QwenClient::buildMessages($systemPrompt, $userPrompt);
        $response = $this->qwenClient->chat($messages);
        
        return $this->parseResponse($response);
    }

    /**
     * 执行批量承保条件分析（并发请求）
     * 定义批量分析的系统提示词
     *
     * @param array $requests 请求数组
     * @param int $concurrency 并发数限制
     * @return array 包含每个请求结果的数组
     * @throws RuntimeException
     */
    public function batchAnalyze(array $requests, int $concurrency = 5): array
    {
        if (empty($requests)) {
            throw new Exception("Requests cannot be empty");
        }

        // 定义批量分析的系统提示词
        $systemPrompt = "你是一位专业的车险承保分析师。请对每份承保申请进行独立的风险评估和分析。返回格式必须是有效的JSON格式，确保每份申请都得到准确的专业分析。";
        
        // 构建批量请求
        $batchRequests = [];
        foreach ($requests as $index => $request) {
            $params = $request['params'] ?? [];
            $user = $request['user'] ?? 'default';
            
            $this->validateParams($params);
            $userPrompt = $this->buildCarInsurancePrompt($params, $user);
            
            $batchRequests[$index] = [
                'messages' => QwenClient::buildMessages($systemPrompt, $userPrompt),
                'model' => 'qwen-turbo',
                'temperature' => 0.7,
                'max_tokens' => 2000,
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
        $systemPrompt = "你是一位资深的保险集团首席承保官。请对以下多份车险承保申请进行综合评估和比较分析，从集团风险管控、市场策略、资源配置等角度提供专业的综合分析报告。返回格式必须是有效的JSON格式，包含：
        - overallAssessment: 总体评估
        - comparativeAnalysis: 比较分析
        - riskPortfolio: 风险组合分析
        - strategicRecommendations: 战略建议
        - priorityRanking: 优先级排序";
        
        // 构建综合分析的用户提示词
        $userPrompt = $this->buildCompositePrompt($requests);
        
        // 使用QwenClient发送请求
        $messages = QwenClient::buildMessages($systemPrompt, $userPrompt);
        $response = $this->qwenClient->chat($messages, 'qwen-turbo', 0.7, 4000);
        
        return $this->parseResponse($response);
    }

    /**
     * 构建车险承保分析提示词
     *
     * @param array $params 承保参数
     * @param string $user 用户标识
     * @return string 用户提示词
     */
    private function buildCarInsurancePrompt(array $params, string $user): string
    {
        $prompt = "请对以下车险承保条件进行专业分析：\n\n";
        $prompt .= "【基本信息】\n";
        $prompt .= "• 保险公司：" . $params['company'] . "\n";
        $prompt .= "• 保单类型：" . $params['type'] . "\n";
        $prompt .= "• 车辆类型：" . $params['car'] . "\n";
        $prompt .= "• 承保区域：" . $params['region'] . "\n\n";
        
        $prompt .= "【政策信息】\n";
        $prompt .= "• 保单政策：" . $params['policy'] . "\n";
        $prompt .= "• 代理人政策：" . $params['agentPolicy'] . "\n\n";
        
        $prompt .= "【费率信息】\n";
        if (isset($params['VCIAgentRate'])) {
            $prompt .= "• 车损险代理费率：" . $params['VCIAgentRate'] . "\n";
        }
        if (isset($params['TCIAgentRate'])) {
            $prompt .= "• 三者险代理费率：" . $params['TCIAgentRate'] . "\n";
        }
        if (isset($params['NCAgentRate'])) {
            $prompt .= "• 无法找到代理费率：" . $params['NCAgentRate'] . "\n";
        }
        if (isset($params['TCIRate'])) {
            $prompt .= "• 三者险费率：" . $params['TCIRate'] . "\n";
        }
        if (isset($params['VCIRate'])) {
            $prompt .= "• 车损险费率：" . $params['VCIRate'] . "\n";
        }
        if (isset($params['NCRate'])) {
            $prompt .= "• 无法找到费率：" . $params['NCRate'] . "\n";
        }
        
        $prompt .= "\n【分析要求】\n";
        $prompt .= "请从以下维度进行专业分析：\n";
        $prompt .= "1. 风险评估：识别主要风险点和风险等级\n";
        $prompt .= "2. 定价分析：评估定价合理性和竞争力\n";
        $prompt .= "3. 合规性检查：分析是否符合监管要求\n";
        $prompt .= "4. 市场分析：评估市场接受度和竞争优势\n";
        $prompt .= "5. 承保建议：提供具体的承保决策建议\n\n";
        
        $prompt .= "用户标识：" . $user . "\n";
        $prompt .= "请提供详细的专业分析报告。";
        
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
        $prompt = "请对以下多份车险承保申请进行综合评估和比较分析：\n\n";
        
        foreach ($requests as $index => $request) {
            $params = $request['params'] ?? [];
            $user = $request['user'] ?? 'default';
            
            // 确保索引是数字类型
            $numericIndex = is_numeric($index) ? (int)$index : 0;
            
            $prompt .= "=== 第 " . ($numericIndex + 1) . " 份承保申请 ===\n";
            $prompt .= "• 保险公司：" . ($params['company'] ?? 'N/A') . "\n";
            $prompt .= "• 保单类型：" . ($params['type'] ?? 'N/A') . "\n";
            $prompt .= "• 车辆类型：" . ($params['car'] ?? 'N/A') . "\n";
            $prompt .= "• 承保区域：" . ($params['region'] ?? 'N/A') . "\n";
            $prompt .= "• 申请人：" . $user . "\n\n";
        }
        
        $prompt .= "【综合分析要求】\n";
        $prompt .= "请从以下维度进行综合评估：\n";
        $prompt .= "1. 风险组合分析：评估整体风险分布和集中度\n";
        $prompt .= "2. 比较分析：对比各申请的优劣和特点\n";
        $prompt .= "3. 资源配置：分析承保资源的最优配置\n";
        $prompt .= "4. 战略建议：提供集团层面的承保策略建议\n";
        $prompt .= "5. 优先级排序：给出承保决策的优先级建议\n\n";
        
        $prompt .= "请提供详细的综合分析报告。";
        
        return $prompt;
    }

    /**
     * 验证配置参数
     *
     * @param array $config 配置数组
     * @throws Exception
     */
    private function validateConfig(array $config): void
    {
        if (!isset($config['apiKey']) || empty($config['apiKey'])) {
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
            if (!isset($params[$field]) || empty($params[$field])) {
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
        
        // 验证公司参数
        $validCompanies = ['中意', '平安', '人保', '太保', '国寿', '大地', '阳光', '太平'];
        if (!in_array($params['company'], $validCompanies)) {
            // 不抛出异常，只是警告，因为可能有新的保险公司
            // throw new Exception("Invalid company: {$params['company']}");
        }
        
        // 验证类型参数
        $validTypes = ['套单', '首保', '续保', '转保'];
        if (!in_array($params['type'], $validTypes)) {
            // 不抛出异常，只是警告，因为可能有新的业务类型
            // throw new Exception("Invalid type: {$params['type']}");
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
        // 尝试不同的响应格式
        $content = null;
        
        // 格式1: choices[0]['message']['content'] (标准格式)
        if (isset($response['choices'][0]['message']['content'])) {
            $content = $response['choices'][0]['message']['content'];
        }
        // 格式2: output['choices'][0]['message']['content'] (某些API版本)
        elseif (isset($response['output']['choices'][0]['message']['content'])) {
            $content = $response['output']['choices'][0]['message']['content'];
        }
        // 格式3: output['text'] (简化格式)
        elseif (isset($response['output']['text'])) {
            $content = $response['output']['text'];
        }
        // 格式4: choices[0]['text'] (另一种格式)
        elseif (isset($response['choices'][0]['text'])) {
            $content = $response['choices'][0]['text'];
        }
        // 格式5: text (直接文本格式)
        elseif (isset($response['text'])) {
            $content = $response['text'];
        }
        else {
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
}