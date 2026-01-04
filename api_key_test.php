<?php

require_once __DIR__ . '/vendor/autoload.php';

use Janfish\UnderwriteAgent\UnderwriteAgent;

echo "=== API密钥测试 ===\n";
echo "使用提供的API密钥进行测试...\n\n";

// 配置信息 - 使用提供的API密钥
$config = [
    'apiKey' => 'sk-bc3138b8402c471a922a176ae7a642c1',
    'timeout' => 30,
    'connectTimeout' => 10
];

try {
    // 创建承保分析智能体
    $agent = new UnderwriteAgent($config);
    echo "✅ UnderwriteAgent创建成功\n";
    
    // 测试参数
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
    
    echo "测试参数准备完成\n";
    echo "公司：" . $params['company'] . "\n";
    echo "类型：" . $params['type'] . "\n";
    echo "车辆：" . $params['car'] . "\n";
    echo "区域：" . $params['region'] . "\n\n";
    
    // 尝试进行分析
    echo "正在调用API进行分析...\n";
    $result = $agent->analyze($params);
    
    echo "✅ 分析成功！\n";
    echo "结果：\n";
    print_r($result);
    
} catch (\Exception $e) {
    echo "❌ 分析失败：" . $e->getMessage() . "\n";
    echo "异常类型：" . get_class($e) . "\n";
    
    // 如果是运行时异常，可能是API响应格式问题
    if (strpos($e->getMessage(), 'Invalid API response format') !== false) {
        echo "\n这可能是因为API返回的格式不符合预期。\n";
        echo "请检查：\n";
        echo "1. API密钥是否有效\n";
        echo "2. API服务是否正常运行\n";
        echo "3. 请求参数是否正确\n";
    }
}

echo "\n=== 测试完成 ===\n";