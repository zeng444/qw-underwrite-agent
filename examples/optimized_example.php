<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Janfish\UnderwriteAgent\UnderwriteAgent;
use Janfish\UnderwriteAgent\Logger\Logger;
use Janfish\UnderwriteAgent\Cache\FileCache;

/**
 * 优化后的使用示例
 * 展示如何使用配置管理器、日志记录器和缓存系统
 */

// 配置信息 - 从环境变量获取API密钥
$apiKey = $_ENV['QWEN_API_KEY'] ?? '';
if (empty($apiKey)) {
    die("错误：请设置 QWEN_API_KEY 环境变量\n");
}

// 创建配置数组
$config = [
    'apiKey' => $apiKey,
    'baseUrl' => 'https://dashscope.aliyuncs.com/api/v1/services/aigc/text-generation/generation',
    'timeout' => 60,
    'connectTimeout' => 10,
    'maxToken' => 8192,
    'temperature' => 0.3,
    'model' => 'qwen3-max',
    'retryAttempts' => 3,
    'retryDelay' => 1000,
    'cacheEnabled' => true,
    'cacheTtl' => 3600, // 1小时
    'logEnabled' => true,
    'logLevel' => 'info',
];

try {
    // 创建承保分析代理
    $agent = new UnderwriteAgent($config);
    
    // 设置日志记录器
    $logger = new Logger(__DIR__ . '/logs/underwrite_agent.log', 'info');
    $agent->setLogger($logger);
    
    // 设置文件缓存（可选）
    $cache = new FileCache(__DIR__ . '/cache', 3600);
    $agent->setCache($cache);
    
    echo "=== 保险承保分析系统 ===\n";
    echo "日志文件：" . $logger->getLogFile() . "\n";
    echo "缓存目录：" . __DIR__ . '/cache' . "\n\n";
    
    // 示例1：单次分析
    echo "1. 执行单次承保分析...\n";
    
    $params = [
        'company' => '中国平安财产保险股份有限公司',
        'type' => '家庭自用车（6座以下）',
        'car' => '新车购置价15万，车龄2年，无重大事故记录',
        'region' => '北京市朝阳区',
        'policy' => '车损险保额15万，三者险保额100万，司机责任险5万，乘客责任险每座1万×4座，盗抢险保额12万，玻璃单独破碎险（国产玻璃），自燃损失险保额12万，车身划痕损失险保额5000元，涉水行驶损失险，不计免赔特约条款',
        'agentPolicy' => '车损险保额15万，三者险保额100万，司机责任险5万，乘客责任险每座1万×4座，不计免赔特约条款',
        'VCIAgentRate' => 0.15,
        'TCIAgentRate' => 0.04,
        'NCAgentRate' => 0.20,
        'TCIRate' => 0.04,
        'VCIRate' => 0.20,
        'NCRate' => 0.25,
    ];
    
    $result = $agent->analyze($params, 'user_001');
    
    echo "分析结果：\n";
    if (($result)) {
        echo "成功获取分析结果\n";
        if (isset($result['rules'])) {
            echo "  - 生成规则数：" . count($result['rules']) . "\n";
        }
        if (isset($result['summary'])) {
            echo "  - 摘要：" . substr($result['summary'], 0, 100) . "...\n";
        }
    } else {
        echo "分析失败\n";
    }
    
    echo "\n";
    
    // 示例2：批量分析
    echo "2. 执行批量承保分析...\n";
    
    $batchRequests = [
        [
            'params' => [
                'company' => '中国人民财产保险股份有限公司',
                'type' => '营业货车（2吨以下）',
                'car' => '新车购置价25万，车龄1年，营运性质',
                'region' => '上海市浦东新区',
                'policy' => '车损险保额25万，三者险保额150万，车上人员责任险司机20万，乘客每座10万×2座，盗抢险保额20万，玻璃单独破碎险（进口玻璃），自燃损失险保额20万，车身划痕损失险保额10000元，不计免赔特约条款',
                'agentPolicy' => '车损险保额25万，三者险保额150万，车上人员责任险司机20万，不计免赔特约条款',
                'VCIAgentRate' => 0.18,
                'TCIAgentRate' => 0.05,
                'NCAgentRate' => 0.22,
                'TCIRate' => 0.05,
                'VCIRate' => 0.22,
                'NCRate' => 0.28,
            ]
        ],
        [
            'params' => [
                'company' => '中国太平洋财产保险股份有限公司',
                'type' => '非营业客车（6-10座）',
                'car' => '新车购置价30万，车龄3年，保养良好',
                'region' => '广州市天河区',
                'policy' => '车损险保额30万，三者险保额200万，车上人员责任险司机30万，乘客每座20万×6座，盗抢险保额25万，玻璃单独破碎险（进口玻璃），自燃损失险保额25万，车身划痕损失险保额15000元，涉水行驶损失险，不计免赔特约条款',
                'agentPolicy' => '车损险保额30万，三者险保额200万，车上人员责任险司机30万，不计免赔特约条款',
                'VCIAgentRate' => 0.16,
                'TCIAgentRate' => 0.045,
                'NCAgentRate' => 0.21,
                'TCIRate' => 0.045,
                'VCIRate' => 0.21,
                'NCRate' => 0.26,
            ]
        ]
    ];
    
    $batchResults = $agent->batchAnalyze($batchRequests, 2);
    
    echo "批量分析结果：\n";
    foreach ($batchResults as $index => $result) {
        if ($result['success']) {
            echo "请求 {$index}：成功\n";
            if (isset($result['data']['rules'])) {
                echo "  - 生成规则数：" . count($result['data']['rules']) . "\n";
            }
        } else {
            echo "请求 {$index}：失败 - " . $result['error'] . "\n";
        }
    }
    
    echo "\n";
    
    // 示例3：综合分析
    echo "3. 执行综合承保分析...\n";
    
    $compositeRequests = [
        [
            'params' => [
                'company' => '中华联合财产保险股份有限公司',
                'type' => '家庭自用车（6座以下）',
                'car' => '新车购置价20万，车龄1年，无事故记录',
                'region' => '深圳市南山区',
                'policy' => '车损险保额20万，三者险保额100万，司机责任险10万，乘客责任险每座5万×4座，盗抢险保额16万，玻璃单独破碎险（国产玻璃），自燃损失险保额16万，车身划痕损失险保额8000元，不计免赔特约条款',
                'agentPolicy' => '车损险保额20万，三者险保额100万，司机责任险10万，不计免赔特约条款',
                'VCIAgentRate' => 0.17,
                'TCIAgentRate' => 0.045,
                'NCAgentRate' => 0.21,
                'TCIRate' => 0.045,
                'VCIRate' => 0.21,
                'NCRate' => 0.27,
            ]
        ],
        [
            'params' => [
                'company' => '阳光财产保险股份有限公司',
                'type' => '营业货车（2-5吨）',
                'car' => '新车购置价35万，车龄2年，营运性质',
                'region' => '杭州市西湖区',
                'policy' => '车损险保额35万，三者险保额200万，车上人员责任险司机25万，乘客每座15万×2座，盗抢险保额28万，玻璃单独破碎险（进口玻璃），自燃损失险保额28万，车身划痕损失险保额12000元，涉水行驶损失险，不计免赔特约条款',
                'agentPolicy' => '车损险保额35万，三者险保额200万，车上人员责任险司机25万，不计免赔特约条款',
                'VCIAgentRate' => 0.19,
                'TCIAgentRate' => 0.055,
                'NCAgentRate' => 0.23,
                'TCIRate' => 0.055,
                'VCIRate' => 0.23,
                'NCRate' => 0.29,
            ]
        ]
    ];
    
    $compositeResult = $agent->compositeAnalyze($compositeRequests);
    
    echo "综合分析结果：\n";
    if ($compositeResult) {
        echo "综合分析成功\n";
        if (isset($compositeResult['rules'])) {
            echo "  - 生成规则数：" . count($compositeResult['rules']) . "\n";
        }
        if (isset($compositeResult['summary'])) {
            echo "  - 摘要：" . substr($compositeResult['summary'], 0, 150) . "...\n";
        }
    } else {
        echo "综合分析失败\n";
    }
    
    echo "\n";
    
    // 显示统计信息
    echo "=== 系统统计 ===\n";
    
    // 日志统计
    $logSize = $logger->getLogFileSize();
    echo "日志文件大小：" . round($logSize / 1024, 2) . " KB\n";
    
    // 缓存统计
    $cacheStats = $cache->getStats();
    echo "缓存统计：\n";
    echo "  - 总文件数：" . $cacheStats['total_files'] . "\n";
    echo "  - 总大小：" . $cacheStats['total_size_mb'] . " MB\n";
    echo "  - 过期文件数：" . $cacheStats['expired_files'] . "\n";
    echo "  - 有效文件数：" . $cacheStats['valid_files'] . "\n";
    
    echo "\n所有操作完成！\n";
    
} catch (Exception $e) {
    echo "\n错误：" . $e->getMessage() . "\n";
    echo "详细信息：\n";
    echo $e->getTraceAsString() . "\n";
}