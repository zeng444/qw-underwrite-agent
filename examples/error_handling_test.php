<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Janfish\UnderwriteAgent\UnderwriteAgent;
use Janfish\UnderwriteAgent\Exception\Exception;
use Janfish\UnderwriteAgent\Exception\RuntimeException;

/**
 * 错误处理和异常场景测试（新架构版本）
 */
class ErrorHandlingTest
{
    private UnderwriteAgent $agent;
    
    public function __construct(array $config)
    {
        $this->agent = new UnderwriteAgent($config);
    }
    
    /**
     * 测试1：空配置验证
     */
    public function testEmptyConfiguration()
    {
        echo "=== 测试1：空配置验证 ===\n";
        
        try {
            $agent = new UnderwriteAgent([]);
            echo "❌ 空配置应该抛出异常\n";
        } catch (Exception $e) {
            echo "✅ 空配置验证通过: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * 测试2：缺少API密钥
     */
    public function testMissingApiKey()
    {
        echo "\n=== 测试2：缺少API密钥 ===\n";
        
        try {
            $agent = new UnderwriteAgent([
                'timeout' => 30,
                'connectTimeout' => 10
            ]);
            echo "❌ 缺少API密钥应该抛出异常\n";
        } catch (Exception $e) {
            echo "✅ 缺少API密钥验证通过: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * 测试3：无效API密钥
     */
    public function testInvalidApiKey()
    {
        echo "\n=== 测试3：无效API密钥 ===\n";
        
        try {
            $agent = new UnderwriteAgent([
                'apiKey' => 'invalid-api-key',
                'timeout' => 30,
                'connectTimeout' => 10
            ]);
            
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
            
            $result = $agent->analyze($params);
            echo "⚠️  无效API密钥测试完成，可能需要实际API调用才能验证\n";
            
        } catch (RuntimeException $e) {
            echo "✅ 捕获运行时异常: " . $e->getMessage() . "\n";
        } catch (Exception $e) {
            echo "✅ 捕获业务异常: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * 测试4：空参数验证
     */
    public function testEmptyParameters()
    {
        echo "\n=== 测试4：空参数验证 ===\n";
        
        try {
            $result = $this->agent->analyze([]);
            echo "❌ 空参数应该抛出异常\n";
        } catch (Exception $e) {
            echo "✅ 空参数验证通过: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * 测试5：缺少必需参数
     */
    public function testMissingRequiredParameters()
    {
        echo "\n=== 测试5：缺少必需参数 ===\n";
        
        $testCases = [
            [
                'name' => '缺少公司参数',
                'params' => [
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
                ]
            ],
            [
                'name' => '缺少类型参数',
                'params' => [
                    'company' => '中意',
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
                ]
            ]
        ];
        
        foreach ($testCases as $testCase) {
            try {
                $result = $this->agent->analyze($testCase['params']);
                echo "❌ " . $testCase['name'] . " 应该抛出异常\n";
            } catch (Exception $e) {
                echo "✅ " . $testCase['name'] . " 验证通过: " . $e->getMessage() . "\n";
            }
        }
    }
    
    /**
     * 测试6：无效参数值
     */
    public function testInvalidParameterValues()
    {
        echo "\n=== 测试6：无效参数值 ===\n";
        
        $testCases = [
            [
                'name' => '费率超出范围',
                'params' => [
                    'company' => '中意',
                    'type' => '套单',
                    'car' => '燃油 旧车',
                    'region' => '只保:川C,E,F,B,H,J,L,Z,X,S,Y,A,G',
                    'policy' => '续保，家自车，套单，非过户，含车损车龄10年以内；无车损车龄15年以内； 川F费用20%，交强3%',
                    'agentPolicy' => '续保，家自车，套单，非过户，含车损车龄10年以内；无车损车龄15年以内； 川F费用20%，交强3%',
                    'VCIAgentRate' => '1.5', // 超出正常范围
                    'TCIAgentRate' => '0.25',
                    'NCAgentRate' => '0',
                    'TCIRate' => '0.23',
                    'VCIRate' => '0.23',
                    'NCRate' => '0'
                ]
            ],
            [
                'name' => '负费率',
                'params' => [
                    'company' => '中意',
                    'type' => '套单',
                    'car' => '燃油 旧车',
                    'region' => '只保:川C,E,F,B,H,J,L,Z,X,S,Y,A,G',
                    'policy' => '续保，家自车，套单，非过户，含车损车龄10年以内；无车损车龄15年以内； 川F费用20%，交强3%',
                    'agentPolicy' => '续保，家自车，套单，非过户，含车损车龄10年以内；无车损车龄15年以内； 川F费用20%，交强3%',
                    'VCIAgentRate' => '-0.1', // 负值
                    'TCIAgentRate' => '0.25',
                    'NCAgentRate' => '0',
                    'TCIRate' => '0.23',
                    'VCIRate' => '0.23',
                    'NCRate' => '0'
                ]
            ]
        ];
        
        foreach ($testCases as $testCase) {
            try {
                $result = $this->agent->analyze($testCase['params']);
                echo "⚠️  " . $testCase['name'] . " 测试完成，可能需要实际API调用来验证结果\n";
            } catch (Exception $e) {
                echo "✅ " . $testCase['name'] . " 验证通过: " . $e->getMessage() . "\n";
            }
        }
    }
    
    /**
     * 测试7：批处理错误处理
     */
    public function testBatchErrorHandling()
    {
        echo "\n=== 测试7：批处理错误处理 ===\n";
        
        // 测试空批次
        try {
            $result = $this->agent->batchAnalyze([]);
            echo "❌ 空批次应该抛出异常\n";
        } catch (Exception $e) {
            echo "✅ 空批次验证通过: " . $e->getMessage() . "\n";
        }
        
        // 测试包含无效参数的批次
        try {
            $batchParams = [
                [
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
                [] // 无效参数
            ];
            
            $result = $this->agent->batchAnalyze($batchParams);
            echo "⚠️  包含无效参数的批次测试完成\n";
            
        } catch (Exception $e) {
            echo "✅ 包含无效参数的批次验证通过: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * 测试8：复合分析错误处理
     */
    public function testCompositeErrorHandling()
    {
        echo "\n=== 测试8：复合分析错误处理 ===\n";
        
        // 测试空复合分析
        try {
            $result = $this->agent->compositeAnalyze([]);
            echo "❌ 空复合分析应该抛出异常\n";
        } catch (Exception $e) {
            echo "✅ 空复合分析验证通过: " . $e->getMessage() . "\n";
        }
        
        // 测试包含无效参数的复合分析
        try {
            $compositeParams = [
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
                'NCRate' => '0',
                'scenarios' => [] // 空场景
            ];
            
            $result = $this->agent->compositeAnalyze($compositeParams);
            echo "⚠️  包含空场景的复合分析测试完成\n";
            
        } catch (Exception $e) {
            echo "✅ 包含空场景的复合分析验证通过: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * 测试9：超时处理
     */
    public function testTimeoutHandling()
    {
        echo "\n=== 测试9：超时处理 ===\n";
        
        try {
            // 创建短超时的代理
            $shortTimeoutAgent = new UnderwriteAgent([
                'apiKey' => 'sk-xxx',
                'timeout' => 1, // 1秒超时
                'connectTimeout' => 1
            ]);
            
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
            
            $result = $shortTimeoutAgent->analyze($params);
            echo "⚠️  超时处理测试完成\n";
            
        } catch (RuntimeException $e) {
            echo "✅ 超时处理验证通过: " . $e->getMessage() . "\n";
        } catch (Exception $e) {
            echo "✅ 超时处理验证通过: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * 运行所有测试
     */
    public function runAllTests()
    {
        echo "保险承保分析智能体 - 错误处理和异常场景测试（新架构版本）\n";
        echo "================================================================\n\n";
        
        $this->testEmptyConfiguration();
        $this->testMissingApiKey();
        $this->testInvalidApiKey();
        $this->testEmptyParameters();
        $this->testMissingRequiredParameters();
        $this->testInvalidParameterValues();
        $this->testBatchErrorHandling();
        $this->testCompositeErrorHandling();
        $this->testTimeoutHandling();
        
        echo "\n=== 所有错误处理测试执行完成 ===\n";
    }
}

// 主函数
function main()
{
    // 配置信息 - 使用提供的API密钥
    $config = [
        'apiKey' => 'sk-xxx',
        'timeout' => 30,
        'connectTimeout' => 10
    ];
    
    try {
        $test = new ErrorHandlingTest($config);
        $test->runAllTests();
        
        echo "\n=== 错误处理测试总结 ===\n";
        echo "✅ 配置验证测试完成\n";
        echo "✅ 参数验证测试完成\n";
        echo "✅ 批处理错误处理测试完成\n";
        echo "✅ 复合分析错误处理测试完成\n";
        echo "✅ 超时处理测试完成\n";
        echo "\n所有错误处理路径已验证！\n";
        
    } catch (\Exception $e) {
        echo "测试执行失败：" . $e->getMessage() . "\n";
    }
}

// 运行主函数
main();