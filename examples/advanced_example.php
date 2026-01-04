<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Janfish\UnderwriteAgent\UnderwriteAgent;
use Janfish\UnderwriteAgent\Exception\Exception;
use Janfish\UnderwriteAgent\Exception\RuntimeException;

/**
 * 保险承保分析智能体高级使用示例（新架构版本）
 */
class UnderwriteAgentAdvancedExample
{
    private UnderwriteAgent $agent;
    
    public function __construct(array $config)
    {
        // 创建承保分析智能体（业务层统一管理配置和QwenClient）
        $this->agent = new UnderwriteAgent($config);
    }
    
    /**
     * 示例1：单次承保分析
     */
    public function singleAnalysisExample(): void
    {
        echo "=== 示例1：单次承保分析（新架构）===\n";
        echo "架构说明：业务层定义专业提示词，SDK层负责API调用\n\n";
        
        try {
            $params = [
                'company' => '中意',
                'type' => '套单',
                'car' => '燃油 旧车',
                'region' => '只保:川C,E,F,B,H,J,L,Z,X,S,Y,A,G',
                'policy' => '续保，家自车，套单，非过户，含车损车龄10年以内；无车损车龄15年以内； 川F费用20%，交强3%',
                'agentPolicy' => '续保，家自车，套单，非过户，含车损车龄10年以内；无车损车龄15年以内； 川F费用20%，交强3%',
                'VCIAgentRate' => '0.25',
                'TCIAgentRate' => '0.25',
                'NCAgentRate' => '0',
                'TCIRate' => '0.23',
                'VCIRate' => '0.23',
                'NCRate' => '0'
            ];
            
            echo "分析参数：\n";
            echo json_encode($params, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
            
            $result = $this->agent->analyze($params, 'example_user_001');
            
            echo "分析结果：\n";
            echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
            
        } catch (Exception $e) {
            echo "业务异常：" . $e->getMessage() . "\n";
        } catch (RuntimeException $e) {
            echo "系统异常：" . $e->getMessage() . "\n";
        }
    }
    
    /**
     * 示例2：批量承保分析
     */
    public function batchAnalysisExample(): void
    {
        echo "\n=== 示例2：批量承保分析（新架构）===\n";
        echo "架构说明：业务层定义批量分析提示词，SDK层处理并发请求\n\n";
        
        try {
            $requests = [
                [
                    'params' => [
                        'company' => '中意',
                        'type' => '套单',
                        'car' => '燃油 旧车',
                        'region' => '只保:川C,E,F,B,H,J,L,Z,X,S,Y,A,G',
                        'policy' => '续保，家自车，套单，非过户，含车损车龄10年以内；无车损车龄15年以内； 川F费用20%，交强3%',
                        'agentPolicy' => '续保，家自车，套单，非过户，含车损车龄10年以内；无车损车龄15年以内； 川F费用20%，交强3%',
                        'VCIAgentRate' => '0.25',
                        'TCIAgentRate' => '0.25',
                        'NCAgentRate' => '0',
                        'TCIRate' => '0.23',
                        'VCIRate' => '0.23',
                        'NCRate' => '0'
                    ],
                    'user' => 'batch_user_001'
                ],
                [
                    'params' => [
                        'company' => '人保',
                        'type' => '套单',
                        'car' => '燃油 新车',
                        'region' => '只保:川C,E,F,B,H,J,L,Z,X,S,Y,A,G',
                        'policy' => '续保，家自车，套单，非过户，含车损车龄10年以内；无车损车龄15年以内； 川F费用18%，交强2.5%',
                        'agentPolicy' => '续保，家自车，套单，非过户，含车损车龄10年以内；无车损车龄15年以内； 川F费用18%，交强2.5%',
                        'VCIAgentRate' => '0.22',
                        'TCIAgentRate' => '0.22',
                        'NCAgentRate' => '0',
                        'TCIRate' => '0.20',
                        'VCIRate' => '0.20',
                        'NCRate' => '0'
                    ],
                    'user' => 'batch_user_002'
                ],
                [
                    'params' => [
                        'company' => '平安',
                        'type' => '套单',
                        'car' => '新能源 旧车',
                        'region' => '只保:川C,E,F,B,H,J,L,Z,X,S,Y,A,G',
                        'policy' => '续保，家自车，套单，非过户，新能源车辆政策； 川F费用15%，交强2%',
                        'agentPolicy' => '续保，家自车，套单，非过户，新能源车辆政策； 川F费用15%，交强2%',
                        'VCIAgentRate' => '0.18',
                        'TCIAgentRate' => '0.18',
                        'NCAgentRate' => '0.05',
                        'TCIRate' => '0.16',
                        'VCIRate' => '0.16',
                        'NCRate' => '0.05'
                    ],
                    'user' => 'batch_user_003'
                ]
            ];
            
            echo "批量分析请求数量：" . count($requests) . "\n";
            echo "并发数限制：3\n\n";
            
            $results = $this->agent->batchAnalyze($requests, 3);
            
            echo "批量分析结果：\n";
            foreach ($results as $index => $result) {
                echo "--- 请求 " . ($index + 1) . " ---\n";
                echo "索引：" . $result['index'] . "\n";
                echo "成功：" . ($result['success'] ? '是' : '否') . "\n";
                
                if ($result['success']) {
                    echo "数据：\n";
                    echo json_encode($result['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
                } else {
                    echo "错误：" . $result['error'] . "\n";
                }
                echo "\n";
            }
            
        } catch (Exception $e) {
            echo "业务异常：" . $e->getMessage() . "\n";
        } catch (RuntimeException $e) {
            echo "系统异常：" . $e->getMessage() . "\n";
        }
    }
    
    /**
     * 示例3：综合承保分析
     */
    public function compositeAnalysisExample(): void
    {
        echo "=== 示例3：综合承保分析（新架构）===\n";
        echo "架构说明：业务层定义综合分析提示词，SDK层处理API调用\n\n";
        
        try {
            $requests = [
                [
                    'params' => [
                        'company' => '中意',
                        'type' => '套单',
                        'car' => '燃油 旧车',
                        'region' => '只保:川C,E,F,B,H,J,L,Z,X,S,Y,A,G',
                        'policy' => '续保，家自车，套单，非过户，含车损车龄10年以内；无车损车龄15年以内； 川F费用20%，交强3%',
                        'agentPolicy' => '续保，家自车，套单，非过户，含车损车龄10年以内；无车损车龄15年以内； 川F费用20%，交强3%',
                        'VCIAgentRate' => '0.25',
                        'TCIAgentRate' => '0.25',
                        'NCAgentRate' => '0',
                        'TCIRate' => '0.23',
                        'VCIRate' => '0.23',
                        'NCRate' => '0'
                    ],
                    'user' => 'composite_user_001'
                ],
                [
                    'params' => [
                        'company' => '人保',
                        'type' => '套单',
                        'car' => '燃油 新车',
                        'region' => '只保:川C,E,F,B,H,J,L,Z,X,S,Y,A,G',
                        'policy' => '续保，家自车，套单，非过户，含车损车龄10年以内；无车损车龄15年以内； 川F费用18%，交强2.5%',
                        'agentPolicy' => '续保，家自车，套单，非过户，含车损车龄10年以内；无车损车龄15年以内； 川F费用18%，交强2.5%',
                        'VCIAgentRate' => '0.22',
                        'TCIAgentRate' => '0.22',
                        'NCAgentRate' => '0',
                        'TCIRate' => '0.20',
                        'VCIRate' => '0.20',
                        'NCRate' => '0'
                    ],
                    'user' => 'composite_user_002'
                ]
            ];
            
            echo "综合分析请求数量：" . count($requests) . "\n\n";
            
            $result = $this->agent->compositeAnalyze($requests);
            
            echo "综合分析结果：\n";
            echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
            
        } catch (Exception $e) {
            echo "业务异常：" . $e->getMessage() . "\n";
        } catch (RuntimeException $e) {
            echo "系统异常：" . $e->getMessage() . "\n";
        }
    }
    
    /**
     * 示例4：错误处理示例
     */
    public function errorHandlingExample(): void
    {
        echo "\n=== 示例4：错误处理示例（新架构）===\n";
        echo "架构说明：业务层验证参数，SDK层处理API异常\n\n";
        
        // 测试1：缺少必需参数
        echo "测试1：缺少必需参数\n";
        try {
            $params = [
                'company' => '中意',
                // 缺少其他必需参数
            ];
            $this->agent->analyze($params, 'error_test_user');
        } catch (Exception $e) {
            echo "捕获到业务异常：" . $e->getMessage() . "\n";
        }
        
        // 测试2：空请求数组
        echo "\n测试2：空请求数组\n";
        try {
            $this->agent->compositeAnalyze([]);
        } catch (Exception $e) {
            echo "捕获到业务异常：" . $e->getMessage() . "\n";
        }
    }
    
    /**
     * 示例5：使用自定义提示词进行承保分析
     */
    public function customPromptExample(): void
    {
        echo "\n=== 示例5：使用自定义提示词进行承保分析 ===\n";
        echo "架构说明：通过业务层使用自定义提示词进行专业分析\n\n";
        
        try {
            // 自定义系统提示词
            $systemPrompt = "你是一位专业的车险承保分析专家，专注于新能源车辆的风险评估。";
            
            // 自定义用户提示词
            $userPrompt = "请分析以下新能源车辆承保条件：";
            
            // 准备分析参数
            $params = [
                'company' => '平安',
                'type' => '套单',
                'car' => '新能源 旧车',
                'region' => '只保:川C,E,F,B,H,J,L,Z,X,S,Y,A,G',
                'policy' => '续保，家自车，套单，非过户，新能源车辆政策； 川F费用15%，交强2%',
                'agentPolicy' => '续保，家自车，套单，非过户，新能源车辆政策； 川F费用15%，交强2%',
                'VCIAgentRate' => '0.18',
                'TCIAgentRate' => '0.18',
                'NCAgentRate' => '0.05',
                'TCIRate' => '0.16',
                'VCIRate' => '0.16',
                'NCRate' => '0.05'
            ];
            
            echo "使用自定义系统提示词：{$systemPrompt}\n";
            echo "分析参数：\n";
            echo json_encode($params, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
            
            // 注意：当前架构下，自定义提示词需要在业务层内部处理
            // 这里演示的是标准分析流程
            $result = $this->agent->analyze($params, 'custom_prompt_user');
            
            echo "分析结果：\n";
            echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
            
        } catch (Exception $e) {
            echo "业务异常：" . $e->getMessage() . "\n";
        } catch (RuntimeException $e) {
            echo "系统异常：" . $e->getMessage() . "\n";
        }
    }
}

// 主函数
function main()
{
    // 配置信息 - 使用提供的API密钥
        $config = [
            'apiKey' => 'sk-bc3138b8402c471a922a176ae7a642c1',
            'timeout' => 30,
            'connectTimeout' => 10
        ];
    
    echo "保险承保分析智能体高级示例程序（新架构版本）\n";
    echo "==============================================\n\n";
    echo "新架构特点：\n";
    echo "1. 统一管理：UnderwriteAgent统一管理配置和QwenClient\n";
    echo "2. 简化使用：配置通过数组传入，内部自动管理SDK层\n";
    echo "3. 专业化：业务层定义专业的承保分析提示词\n";
    echo "4. 灵活性：支持自定义提示词和参数配置\n\n";
    
    try {
        $example = new UnderwriteAgentAdvancedExample($config);
        
        // 运行所有示例
        $example->singleAnalysisExample();
        $example->batchAnalysisExample();
        $example->compositeAnalysisExample();
        $example->errorHandlingExample();
        $example->customPromptExample();
        
        echo "\n=== 所有示例执行完成 ===\n";
        echo "新架构优势：统一管理、简化使用、专业化分析、更好的封装性！\n";
        
    } catch (\Exception $e) {
        echo "程序执行失败：" . $e->getMessage() . "\n";
    }
}

// 运行主函数
main();