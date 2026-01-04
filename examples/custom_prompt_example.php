<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Janfish\UnderwriteAgent\UnderwriteAgent;

/**
 * 自定义提示词使用示例（新架构版本）
 */
class CustomPromptExample
{
    private UnderwriteAgent $agent;
    
    public function __construct(array $config)
    {
        // 创建承保分析智能体（业务层统一管理配置和QwenClient）
        $this->agent = new UnderwriteAgent($config);
    }
    
    /**
     * 示例1：使用自定义系统提示词
     */
    public function customSystemPromptExample(): void
    {
        echo "=== 示例1：自定义系统提示词 ===\n";
        
        // 自定义系统提示词
        $customSystemPrompt = "你是一位经验丰富的保险精算师，专门负责车险承保分析。请以专业的角度分析承保条件，重点关注风险评估和定价策略。请以JSON格式返回分析结果。";
        
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
        
        try {
            $result = $this->agent->analyze($params, 'custom_user', $customSystemPrompt);
            echo "使用自定义系统提示词的分析结果：\n";
            echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
        } catch (\Exception $e) {
            echo "分析失败：" . $e->getMessage() . "\n\n";
        }
    }
    
    /**
     * 示例2：使用自定义用户提示词
     */
    public function customUserPromptExample(): void
    {
        echo "=== 示例2：自定义用户提示词 ===\n";
        
        // 自定义用户提示词模板
        $customUserPrompt = "请作为资深保险顾问，为以下承保条件提供专业的风险评估和建议：\n\n";
        $customUserPrompt .= "保险公司：{company}\n";
        $customUserPrompt .= "保单类型：{type}\n";
        $customUserPrompt .= "车辆信息：{car}\n";
        $customUserPrompt .= "承保区域：{region}\n";
        $customUserPrompt .= "\n请重点分析：\n";
        $customUserPrompt .= "1. 风险等级评估\n";
        $customUserPrompt .= "2. 保费定价建议\n";
        $customUserPrompt .= "3. 承保决策建议\n";
        $customUserPrompt .= "4. 需要关注的风险点\n\n";
        $customUserPrompt .= "请以结构化JSON格式返回分析结果。";
        
        // 替换模板变量
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
        
        // 替换模板中的变量
        foreach ($params as $key => $value) {
            $customUserPrompt = str_replace('{' . $key . '}', $value, $customUserPrompt);
        }
        
        try {
            $result = $this->agent->analyze($params, 'template_user', null, $customUserPrompt);
            echo "使用自定义用户提示词的分析结果：\n";
            echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
        } catch (\Exception $e) {
            echo "分析失败：" . $e->getMessage() . "\n\n";
        }
    }
    
    /**
     * 示例3：同时使用自定义系统提示词和用户提示词
     */
    public function bothCustomPromptsExample(): void
    {
        echo "=== 示例3：同时使用自定义系统提示词和用户提示词 ===\n";
        
        // 自定义系统提示词
        $customSystemPrompt = "你是一位专注于车险领域的AI承保助手，具有丰富的风险评估经验。请提供客观、准确的风险分析。";
        
        // 自定义用户提示词
        $customUserPrompt = "请分析以下新能源车辆的承保风险：\n\n";
        $customUserPrompt .= "承保信息：\n";
        $customUserPrompt .= "- 保险公司：{company}\n";
        $customUserPrompt .= "- 车辆类型：{car}\n";
        $customUserPrompt .= "- 承保区域：{region}\n";
        $customUserPrompt .= "\n特别关注：\n";
        $customUserPrompt .= "• 电池安全风险\n";
        $customUserPrompt .= "• 充电设施风险\n";
        $customUserPrompt .= "• 技术更新风险\n";
        $customUserPrompt .= "• 维修成本评估\n\n";
        $customUserPrompt .= "返回JSON格式结果。";
        
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
        
        // 替换模板变量
        foreach ($params as $key => $value) {
            $customUserPrompt = str_replace('{' . $key . '}', $value, $customUserPrompt);
        }
        
        try {
            $result = $this->agent->analyze($params, 'both_custom_user', $customSystemPrompt, $customUserPrompt);
            echo "同时使用自定义提示词的分析结果：\n";
            echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
        } catch (\Exception $e) {
            echo "分析失败：" . $e->getMessage() . "\n\n";
        }
    }
    
    /**
     * 示例4：批量分析中使用自定义提示词
     */
    public function batchCustomPromptExample(): void
    {
        echo "=== 示例4：批量分析中使用自定义提示词 ===\n";
        
        // 批量分析的自定义提示词
        $customSystemPrompt = "你是一位保险承保专家，请对每份承保申请进行专业评估。";
        
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
                'user' => 'batch_user_1'
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
                'user' => 'batch_user_2'
            ]
        ];
        
        try {
            $results = $this->agent->batchAnalyze($requests, 2, $customSystemPrompt);
            
            echo "批量分析结果（使用自定义系统提示词）：\n";
            foreach ($results as $index => $result) {
                echo "--- 请求 " . ($index + 1) . " ---\n";
                if ($result['success']) {
                    echo "分析结果：\n";
                    echo json_encode($result['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
                } else {
                    echo "失败: " . $result['error'] . "\n";
                }
                echo "\n";
            }
        } catch (\Exception $e) {
            echo "批量分析失败：" . $e->getMessage() . "\n\n";
        }
    }
    
    /**
     * 示例5：综合分析中使用自定义提示词
     */
    public function compositeCustomPromptExample(): void
    {
        echo "=== 示例5：综合分析中使用自定义提示词 ===\n";
        
        $customSystemPrompt = "你是一位保险集团的首席承保官，请对以下多份承保申请进行综合评估和比较分析。";
        
        $requests = [
            [
                'params' => [
                    'company' => '新华保险',
                    'type' => '特种车险',
                    'car' => '混凝土搅拌车',
                    'region' => '广州市',
                    'policy' => '特种车辆保险政策',
                    'agentPolicy' => '特种车辆代理政策',
                    'VCIAgentRate' => '22%',
                    'TCIAgentRate' => '19%',
                ],
                'user' => 'composite_user_1'
            ],
            [
                'params' => [
                    'company' => '阳光保险',
                    'type' => '营业车险',
                    'car' => '19座营业客车',
                    'region' => '成都市',
                    'policy' => '营业客车保险政策',
                    'agentPolicy' => '营业代理人政策',
                    'VCIAgentRate' => '20%',
                    'TCIAgentRate' => '17%',
                ],
                'user' => 'composite_user_2'
            ]
        ];
        
        try {
            $result = $this->agent->compositeAnalyze($requests, $customSystemPrompt);
            echo "综合分析结果（使用自定义系统提示词）：\n";
            echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
        } catch (\Exception $e) {
            echo "综合分析失败：" . $e->getMessage() . "\n\n";
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
    
    echo "保险承保分析智能体 - 自定义提示词使用示例（新架构版本）\n";
    echo "==================================================\n\n";
    
    try {
        $example = new CustomPromptExample($config);
        
        // 运行所有示例
        $example->customSystemPromptExample();
        $example->customUserPromptExample();
        $example->bothCustomPromptsExample();
        $example->batchCustomPromptExample();
        $example->compositeCustomPromptExample();
        
        echo "=== 所有自定义提示词示例执行完成 ===\n";
        
    } catch (\Exception $e) {
        echo "程序执行失败：" . $e->getMessage() . "\n";
    }
}

// 运行主函数
main();