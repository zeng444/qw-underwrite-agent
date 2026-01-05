# 保险承保分析智能体（优化版）

基于通义千问 API 的 PHP Composer 插件，将 SDK 层与业务层分离，提供专业、高效、可靠的保险承保分析功能。

## 架构设计

本项目采用统一管理架构，经过全面优化：

### UnderwriteAgent（业务层统一管理）

- **职责**: 统一管理配置和 QwenClient 实例，负责保险承保分析业务逻辑
- **功能**:
  - 配置管理：接收配置数组，内部创建 QwenClient
  - 业务逻辑：参数验证、专业提示词定义、结果解析
  - 异常处理：业务异常和系统异常处理
  - 日志记录：完整的操作日志和错误日志
  - 缓存机制：支持内存缓存和文件缓存

### 内部架构

- **SDK 层**: QwenClient 在内部实例化，负责 API 交互，具备重试机制和错误处理
- **业务层**: 定义专业的保险承保分析提示词和业务逻辑，集成日志和缓存
- **支持层**: 配置管理、日志系统、缓存机制、异常处理

## 功能特性

- **单次承保分析**: 对单个承保条件进行专业分析
- **批量承保分析**: 并发处理多个承保分析请求，支持错误隔离
- **综合承保分析**: 一次性分析多组承保条件，提供综合评估

## 配置参考

### 完整配置选项

| 配置项           | 类型   | 默认值        | 说明                                                                 |
| ---------------- | ------ | ------------- | -------------------------------------------------------------------- |
| `apiKey`         | string | -             | **必需** 通义千问 API 密钥                                           |
| `baseUrl`        | string | 官方 API 地址 | API 基础地址                                                         |
| `timeout`        | int    | 30            | 请求超时时间（秒）                                                   |
| `connectTimeout` | int    | 10            | 连接超时时间（秒）                                                   |
| `retryAttempts`  | int    | 3             | 重试次数                                                             |
| `retryDelay`     | int    | 1000          | 重试延迟（毫秒）                                                     |
| `model`          | string | qwen3-max     | 使用的模型                                                           |
| `temperature`    | float  | 0.3           | 温度参数（0-1）                                                      |
| `maxToken`       | int    | 8192          | 最大 Token 数                                                        |
| `cache`          | string | none          | 缓存类型：none/memory/file。当设置为 memory 或 file 时，自动启用缓存 |
| `cacheDir`       | string | null          | 文件缓存目录（使用文件缓存时必需）                                   |
| `cacheTtl`       | int    | 3600          | 缓存时间（秒）                                                       |
| `cacheEnabled`   | bool   | false         | 缓存启用状态（由 cache 参数自动决定，无需手动设置）                  |
| `logFile`        | string | null          | 日志文件路径（设置后启用日志）                                       |
| `logLevel`       | string | info          | 日志级别：debug/info/warning/error                                   |

### 重要配置说明

#### 日志配置注意事项

**默认行为**：

- `logEnabled` 默认为 `true`（日志功能开启）
- `logFile` 默认为 `null`（不指定日志文件路径）
- **重要**：当 `logFile` 为 `null` 时，系统使用 `NullLogger`，**不会写入任何日志文件**

**正确启用日志文件的方法**：
必须显式设置 `logFile` 参数，指定日志文件路径：

```php
$config = [
    'apiKey' => $_ENV['QWEN_API_KEY'],
    'logFile' => '/data/app.log',  // 正确：指定日志文件路径
    'logLevel' => 'debug',        // 可选：设置日志级别
];
```

**常见误区**：

```php
// 错误：没有设置 logFile，实际上不会写入日志
$config = [
    'apiKey' => $_ENV['QWEN_API_KEY'],
    'logLevel' => 'debug',  // 虽然设置了日志级别，但没有指定文件路径
];
```

### 配置示例

#### 开发环境配置

```php
$config = [
    'apiKey' => $_ENV['QWEN_API_KEY'],
    'cache' => 'memory',        // 开发环境使用内存缓存，自动启用缓存
    'logFile' => __DIR__ . '/logs/app.log',  // 必须指定日志文件路径才能写入日志
    'logLevel' => 'debug',      // 开发环境使用调试日志
];
```

#### 生产环境配置

```php
$config = [
    'apiKey' => $_ENV['QWEN_API_KEY'],
    'timeout' => 60,
    'retryAttempts' => 5,
    'cache' => 'file',                              // 生产环境使用文件缓存，自动启用缓存
    'cacheDir' => '/var/cache/underwrite',        // 指定缓存目录（必需）
    'cacheTtl' => 7200,                            // 2小时缓存
    'logFile' => '/var/log/underwrite/app.log',   // 生产日志文件
    'logLevel' => 'info',                          // 生产环境日志级别
];
```

## 环境要求

- PHP >= 7.4
- Guzzle HTTP Client >= 7.0

## 快速开始

### 安装

```bash
composer require janfish/qw-underwrite-agent
```

### 基础使用（推荐方式）

```php
<?php

use Janfish\UnderwriteAgent\UnderwriteAgent;

// 配置信息（支持环境变量）
$config = [
    'apiKey' => $_ENV['QWEN_API_KEY'] ?? 'your-api-key',  // 推荐从环境变量读取
    'timeout' => 600,                      // 可选：请求超时时间（秒）
    'connectTimeout' => 10,               // 可选：连接超时时间（秒）
    'logFile' => __DIR__ . '/logs/app.log', // 重要：必须指定日志文件路径才能写入日志
    'logLevel' => 'info',                  // 可选：日志级别
    'cache' => 'memory',                   // 可选：缓存类型（memory/file/none）
];

// 创建承保分析智能体
$agent = new UnderwriteAgent($config);

// 准备分析参数
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

// 执行单次分析
try {
    $result = $agent->analyze($params);
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} catch (\Exception $e) {
    echo "分析失败: " . $e->getMessage() . "\n";
    // 查看日志文件获取详细信息
}
```

### 环境变量配置（推荐）

```bash
# Linux/Mac
export QWEN_API_KEY="your-api-key-here"

# Windows
set QWEN_API_KEY=your-api-key-here
```

## 参数说明

### 保险承保分析参数格式

分析参数 `$params` 必须包含以下字段：

| 参数名         | 类型   | 必填 | 说明           | 示例                                                                                              |
| -------------- | ------ | ---- | -------------- | ------------------------------------------------------------------------------------------------- |
| `company`      | string | 是   | 保险公司名称   | '中意'                                                                                            |
| `type`         | string | 是   | 保单类型       | '套单'                                                                                            |
| `car`          | string | 是   | 车辆类型       | '燃油 旧车'                                                                                       |
| `region`       | string | 是   | 承保区域       | '只保:川 C,E,F,B,H,J,L,Z,X,S,Y,A,G'                                                               |
| `policy`       | string | 是   | 保单政策描述   | '续保，家自车，套单，非过户，含车损车龄 10 年以内；无车损车龄 15 年以内； 川 F 费用 20%，交强 3%' |
| `agentPolicy`  | string | 是   | 代理人政策描述 | '续保，家自车，套单，非过户，含车损车龄 10 年以内；无车损车龄 15 年以内； 川 F 费用 20%，交强 3%' |
| `VCIAgentRate` | string | 否   | 商业险代理费率 | '0.25'                                                                                            |
| `TCIAgentRate` | string | 否   | 交强险代理费率 | '0.25'                                                                                            |
| `NCAgentRate`  | string | 否   | 非车险代理费率 | '0'                                                                                               |
| `TCIRate`      | string | 否   | 交强险费率     | '0.23'                                                                                            |
| `VCIRate`      | string | 否   | 商业险费率     | '0.23'                                                                                            |
| `NCRate`       | string | 否   | 非车险险费率   | '0'                                                                                               |

### 费率参数说明

费率参数支持小数格式（如 '0.25' 表示 25%），系统会自动解析和处理，支持范围验证。

### 参数长度限制

系统对以下参数有长度限制，超出限制将抛出验证异常：

| 参数名        | 最大长度    | 说明           |
| ------------- |---------| -------------- |
| `company`     | 50 字符   | 保险公司名称   |
| `type`        | 50 字符   | 保单类型       |
| `car`         | 200 字符  | 车辆类型       |
| `region`      | 400 字符  | 承保区域       |
| `policy`      | 2000 字符 | 保单政策描述   |
| `agentPolicy` | 2000 字符 | 代理人政策描述 |

**注意**: 所有参数必须为字符串类型，且不能为空或仅包含空白字符。

## 详细使用

### 1. 创建客户端（高级用法）

```php
use Janfish\UnderwriteAgent\QwenClient;
use Janfish\UnderwriteAgent\Logger\Logger;
use Janfish\UnderwriteAgent\Cache\MemoryCache;

// 基础配置
$qwenClient = new QwenClient(
    'your-api-key',
    'https://dashscope.aliyuncs.com/api/v1/services/aigc/text-generation/generation',
    30,  // 请求超时时间（秒）
    10,  // 连接超时时间（秒）
    3,   // 重试次数
    1000 // 重试延迟（毫秒）
);

// 添加日志支持
$logger = new Logger('/path/to/log/file.log', 'debug');

// 添加缓存支持
$cache = new MemoryCache();
```

### 2. 单次承保分析

```php
$result = $agent->analyze(array $params, string $user = 'default'): array
```

**参数说明:**

- `$params`: 分析参数数组（见上表）
- `$user`: 用户标识（可选，默认为'default'）

**返回值:** 解析后的业务数据（JSON 格式）

**特点:**

- 业务层定义专业的车险承保分析提示词
- 包含风险评估、定价分析、合规性检查等维度
- 完整的参数验证和错误处理
- 自动日志记录和缓存支持

### 3. 批量承保分析

```php
$results = $agent->batchAnalyze(array $requests, int $concurrency = 5): array
```

**参数说明:**

- `$requests`: 请求数组，每个元素包含`params`和`user`字段
- `$concurrency`: 并发数限制，默认为 5

**返回值:** 包含每个请求结果的数组，每个元素包含：

- `index`: 请求索引
- `success`: 是否成功
- `error`: 错误信息（失败时）
- `data`: 解析后的业务数据（成功时）

**特点:**

- 业务层定义批量分析的专业提示词
- SDK 层处理并发请求，支持错误隔离
- 单个请求失败不影响其他请求
- 支持并发数控制和重试机制

### 4. 综合承保分析

```php
$result = $agent->compositeAnalyze(array $requests): array
```

**参数说明:**

- `$requests`: 请求数组，每个元素包含`params`和`user`字段

**返回值:** 解析后的业务数据（JSON 格式）

**特点:**

- 区别于 batchAnalyze，综合承保条件分析是将多组承保条件交给大模型一次分析完成
- 从集团风险管控、市场策略、资源配置等角度提供专业的综合分析报告
- 支持复杂的跨条件分析和综合评估

## 配置选项

### 完整配置选项

UnderwriteAgent 支持以下配置参数：

```php
$config = [
    'apiKey' => 'your-api-key',           // 必需：通义千问 API 密钥
    'baseUrl' => 'https://dashscope.aliyuncs.com/api/v1/services/aigc/text-generation/generation', // 可选：API 地址
    'timeout' => 30,                      // 可选：请求超时时间（秒），默认 30
    'connectTimeout' => 10,                // 可选：连接超时时间（秒），默认 10
    'retryAttempts' => 3,                  // 可选：重试次数，默认 3
    'retryDelay' => 1000,                 // 可选：重试延迟（毫秒），默认 1000
    'logFile' => null,                     // 可选：日志文件路径，默认系统临时目录
    'logLevel' => 'info',                  // 可选：日志级别（debug/info/warning/error），默认 info
    'cache' => 'none',                    // 可选：缓存类型（memory/file/none），默认 none
    'cacheDir' => null,                   // 可选：文件缓存目录，默认系统临时目录
];

$agent = new UnderwriteAgent($config);
```

### 配置管理（高级）

```php
use Janfish\UnderwriteAgent\Config\ConfigManager;

// 使用配置管理器
$configManager = new ConfigManager();
$configManager->loadFromFile('/path/to/config.json');
$configManager->set('apiKey', $_ENV['QWEN_API_KEY']);
$configManager->validate();

$config = $configManager->getAll();
$agent = new UnderwriteAgent($config);
```

### 高级集成配置（Swoft 框架）

```php
use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Log\Logger as SwoftLogger;
use Swoft\Cache\Cache;
use Janfish\UnderwriteAgent\UnderwriteAgent;

/**
 * @Bean()
 */
class UnderwriteService
{
    private UnderwriteAgent $agent;

    public function __construct()
    {
        $config = [
            'apiKey' => $_ENV['QWEN_API_KEY'],
            'cache' => 'custom',
            'logLevel' => 'info',
        ];

        $this->agent = new UnderwriteAgent($config);

        // 注入 Swoft 日志
        $this->agent->setLogger(new SwoftLoggerAdapter(\bean(SwoftLogger::class)));

        // 注入 Swoft 缓存
        $this->agent->setCache(new SwoftCacheAdapter(\bean(Cache::class)));
    }

    public function analyzeInsurance(array $params): array
    {
        return $this->agent->analyze($params);
    }
}
```

## 错误处理

SDK 提供完善的错误处理机制：

- **参数验证异常**: 参数格式、类型、范围验证失败
- **业务逻辑异常**: 业务规则验证失败
- **网络异常**: API 请求失败，支持自动重试
- **响应解析异常**: API 响应格式异常
- **系统异常**: 其他系统级错误

```php
use Janfish\UnderwriteAgent\Exception\Exception;
use Janfish\UnderwriteAgent\Exception\RuntimeException;
use Janfish\UnderwriteAgent\Exception\ValidationException;

try {
    $result = $agent->analyze($params);
} catch (ValidationException $e) {
    // 处理参数验证错误
    echo "参数错误: " . $e->getMessage();
} catch (Exception $e) {
    // 处理业务异常（如参数错误）
    echo "业务错误: " . $e->getMessage();
} catch (RuntimeException $e) {
    // 处理系统异常（如网络错误）
    echo "系统错误: " . $e->getMessage();
    // 查看日志获取详细信息
}
```

## 示例代码

项目提供了丰富的示例代码：

- `examples/test.php`: 基础使用示例，展示分层架构的使用
- `test_optimization.php`: 系统优化测试，验证所有新功能

运行示例：

```bash
# 基础示例
php examples/test.php

# 系统测试
php test_optimization.php
```

## 日志系统


### 正确启用日志文件的方法

必须显式设置 `logFile` 参数，指定日志文件路径：

```php
$config = [
    'apiKey' => $_ENV['QWEN_API_KEY'],
    'logFile' => __DIR__ . '/logs/underwrite.log',  // 必须指定日志文件路径
    'logLevel' => 'info',                           // 日志级别
];

$agent = new UnderwriteAgent($config);
```

### 日志级别

- **debug**: 调试信息，包含详细的技术细节（API 请求参数、响应时间等）
- **info**: 一般信息，记录正常操作流程（分析开始/完成等）
- **warning**: 警告信息，记录需要注意的情况（参数异常、重试等）
- **error**: 错误信息，记录系统错误和异常（API 失败、解析错误等）

### 日志格式

```
[2024-01-05 10:30:45] [info] 开始承保分析 {"company": "中意", "type": "套单"}
[2024-01-05 10:30:46] [debug] API请求成功 {"response_time": 850, "retry_count": 0}
[2024-01-05 10:30:46] [info] 承保分析完成 {"risk_level": "高风险", "cache_hit": false}
[2024-01-05 10:30:47] [warning] 参数验证警告 {"field": "company", "value": "", "message": "公司名称为空"}
[2024-01-05 10:30:48] [error] API请求失败 {"error": "timeout", "retry_attempts": 3}
```

### 日志配置选项

```php
$config = [
    'logFile' => __DIR__ . '/logs/underwrite.log',  // 日志文件路径
    'logLevel' => 'debug',                           // 日志级别
    'logMaxFiles' => 7,                              // 保留日志文件数量（轮转）
    'logMaxSize' => 10485760,                        // 单文件最大大小（字节，默认10MB）
];
```


## 最佳实践

### 生产环境配置建议

```php
// 生产环境推荐配置
$config = [
    'apiKey' => $_ENV['QWEN_API_KEY'],              // 从环境变量读取API密钥
    'timeout' => 60,                               // 增加超时时间
    'connectTimeout' => 15,                        // 连接超时
    'retryAttempts' => 5,                          // 增加重试次数
    'retryDelay' => 2000,                          // 重试延迟
    'logFile' => '/var/log/underwrite/underwrite.log', // 生产日志路径（必须指定）
    'logLevel' => 'info',                          // 生产环境日志级别
    'cache' => 'file',                             // 缓存类型: none/memory/file
    'cacheDir' => '/var/cache/underwrite',       // 缓存目录（文件缓存时）
    'cacheTtl' => 7200,                            // 缓存时间（2小时）
];
```


### 监控和告警


### Swoft 框架完整集成示例

```php
<?php
namespace App\Service;

use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Log\Logger as SwoftLogger;
use Swoft\Cache\Cache;
use Janfish\UnderwriteAgent\UnderwriteAgent;

/**
 * 保险承保分析服务
 * @Bean()
 */
class InsuranceService
{
    private UnderwriteAgent $agent;

    public function __construct()
    {
        $config = [
            'apiKey' => $_ENV['QWEN_API_KEY'],
            'timeout' => 60,
            'connectTimeout' => 15,
            'retryAttempts' => 5,
            'retryDelay' => 2000,
            'cache' => 'custom',
            'logLevel' => 'info',
        ];

        $this->agent = new UnderwriteAgent($config);

        // 注入 Swoft 组件
        $this->agent->setLogger(new SwoftLoggerAdapter(\bean(SwoftLogger::class)));
        $this->agent->setCache(new SwoftCacheAdapter(\bean(Cache::class)));
    }

    /**
     * 分析承保风险
     */
    public function analyzeRisk(array $params): array
    {
        try {
            $result = $this->agent->analyze($params);

            // 添加业务监控
            \bean('metric')->increment('underwrite.analyze.success');

            return $result;
        } catch (\Exception $e) {
            // 错误监控
            \bean('metric')->increment('underwrite.analyze.error');
            \bean('logger')->error('承保分析失败', ['error' => $e->getMessage()]);

            throw $e;
        }
    }

    /**
     * 批量分析
     */
    public function batchAnalyze(array $requests): array
    {
        return $this->agent->batchAnalyze($requests, 3); // 限制并发数为3
    }
}
```

## 缓存机制

### 启用缓存功能

缓存功能默认是关闭的，需要在配置中显式启用：

```php
$config = [
    'apiKey' => $_ENV['QWEN_API_KEY'],
    'cache' => 'memory',           // 启用内存缓存
    // 'cache' => 'file',          // 启用文件缓存
    // 'cache' => 'none',          // 禁用缓存（默认）
    'cacheDir' => '/tmp/cache',   // 文件缓存目录（可选）
    'cacheTtl' => 3600,           // 缓存时间（秒，默认3600）
];

$agent = new UnderwriteAgent($config);
```

### 缓存类型

- **memory**: 内存缓存，速度快，生命周期短（推荐用于开发环境）
- **file**: 文件缓存，持久化，可跨请求共享（推荐用于生产环境）
- **none**: 禁用缓存（默认状态）

### 缓存工作原理

系统会自动为相同的承保分析请求生成缓存键，缓存包含：

- 用户参数（排序后）
- 模型配置
- 温度参数
- 版本信息

