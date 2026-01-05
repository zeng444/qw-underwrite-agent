<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Janfish\UnderwriteAgent\UnderwriteAgent;

// 配置信息 - 从环境变量获取API密钥
$apiKey = $_ENV['QWEN_API_KEY'] ?? 'sk-bc3138b8402c471a922a176ae7a642c1';
if (empty($apiKey)) {
    die("错误：请设置 QWEN_API_KEY 环境变量\n");
}

$config = [
    'apiKey' => $apiKey,
    'timeout' => 600,
    'connectTimeout' => 10
];

try {
    // 创建承保分析智能体（业务层，内部管理QwenClient）
    $agent = new UnderwriteAgent($config);

    echo "=== 新架构演示：业务层统一管理 ===\n\n";
    echo "架构说明：\n";
    echo "- UnderwriteAgent: 业务层统一管理配置和QwenClient\n";
    echo "- 配置通过数组传入，内部自动创建QwenClient实例\n\n";

    echo "=== 单次承保分析示例 ===\n";

    // 单次分析参数
    $singleParams = [
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

    // 执行单次分析
    $result = $agent->analyze($singleParams, 'test_user');
    echo "单次分析结果：\n";
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
} catch (\Exception $e) {
    echo "单次分析失败：" . $e->getMessage() . "\n";
}

echo "\n";

try {
    echo "=== 批量承保分析示例 ===\n";
    $batchRequests = [
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
    $agent = new UnderwriteAgent($config);
    // 执行批量分析，设置并发数为3
    $batchResults = $agent->batchAnalyze($batchRequests, 3);

    echo "批量分析结果：\n";
    foreach ($batchResults as $index => $result) {
        echo "请求 {$index}:\n";
        if ($result['success']) {
            echo "成功: " . json_encode($result['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        } else {
            echo "失败: " . $result['error'] . "\n";
        }
        echo "\n";
    }

} catch (\Exception $e) {
    echo "批量分析失败：" . $e->getMessage() . "\n";
}

echo "\n";

try {
    echo "=== 综合承保分析示例 ===\n";
    $agent = new UnderwriteAgent($config);
    // 执行综合分析
    $compositeResult = $agent->compositeAnalyze($batchRequests);

    echo "综合分析结果：\n";
    echo json_encode($compositeResult, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

} catch (\Exception $e) {
    echo "综合分析失败：" . $e->getMessage() . "\n";
}
