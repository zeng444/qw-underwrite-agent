<?php

require_once __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

echo "=== API响应调试测试 ===\n";
echo "使用提供的API密钥进行测试...\n\n";

$apiKey = 'sk-bc3138b8402c471a922a176ae7a642c1';
$baseUrl = 'https://dashscope.aliyuncs.com/api/v1/services/aigc/text-generation/generation';

$httpClient = new Client([
    'headers' => [
        'Authorization' => 'Bearer ' . $apiKey,
        'Content-Type' => 'application/json',
    ],
    'timeout' => 30,
    'connect_timeout' => 10,
]);

$messages = [
    [
        'role' => 'system',
        'content' => '你是一位专业的车险承保分析专家。请根据提供的承保条件，从风险评估、定价策略、市场合规性等专业角度进行详细分析。返回格式必须是有效的JSON格式。'
    ],
    [
        'role' => 'user',
        'content' => '请分析以下车险承保条件：
保险公司：中意
保单类型：套单
车辆类型：燃油 旧车
承保区域：只保:川C,E,F,B,H,J,L,Z,X,S,Y,A,G
承保政策：续保，家自车，套单，非过户，含车损车龄10年以内；无车损车龄15年以内； 川F费用20%，交强3%
代理政策：续保，家自车，套单，非过户，含车损车龄10年以内；无车损车龄15年以内； 川F费用20%，交强3%
费率信息：
- 商业险代理费率：0.25
- 交强险代理费率：0.25
- 代收代缴费率：0
- 交强险费率：0.23
- 商业险费率：0.23
- 代收代缴费率：0

请提供详细的专业分析。'
    ]
];

try {
    echo "正在发送API请求...\n";
    
    $response = $httpClient->post($baseUrl, [
        'json' => [
            'model' => 'qwen-turbo',
            'input' => [
                'messages' => $messages,
            ],
            'parameters' => [
                'temperature' => 0.7,
                'max_tokens' => 2000,
                'result_format' => 'message',
            ],
        ],
    ]);

    $statusCode = $response->getStatusCode();
    $responseBody = $response->getBody()->getContents();
    
    echo "✅ API请求成功\n";
    echo "状态码：" . $statusCode . "\n";
    echo "响应内容：\n";
    echo $responseBody . "\n\n";
    
    $result = json_decode($responseBody, true);
    
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "✅ JSON解析成功\n";
        echo "响应结构：\n";
        print_r(array_keys($result));
        
        if (isset($result['choices'][0]['message']['content'])) {
            echo "\n✅ 找到预期的响应内容\n";
            echo "内容：" . $result['choices'][0]['message']['content'] . "\n";
        } else {
            echo "\n❌ 未找到预期的响应结构\n";
            echo "可用的键：" . implode(', ', array_keys($result)) . "\n";
            
            if (isset($result['output'])) {
                echo "输出结构：" . json_encode($result['output'], JSON_PRETTY_PRINT) . "\n";
            }
        }
    } else {
        echo "❌ JSON解析失败：" . json_last_error_msg() . "\n";
    }
    
} catch (GuzzleException $e) {
    echo "❌ API请求失败：" . $e->getMessage() . "\n";
    
    if ($e->hasResponse()) {
        $errorResponse = $e->getResponse();
        echo "错误状态码：" . $errorResponse->getStatusCode() . "\n";
        echo "错误响应：" . $errorResponse->getBody()->getContents() . "\n";
    }
} catch (\Exception $e) {
    echo "❌ 其他错误：" . $e->getMessage() . "\n";
}

echo "\n=== 调试测试完成 ===\n";