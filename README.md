# 保险承保分析智能体（新架构版本）

基于通义千问 API 的 PHP Composer 插件，采用分层架构设计，将 SDK 层与业务层分离，提供更专业的保险承保分析功能。

## 🏗️ 架构设计

本项目采用统一管理架构：

### UnderwriteAgent（业务层统一管理）

- **职责**: 统一管理配置和 QwenClient 实例，负责保险承保分析业务逻辑
- **功能**:
  - 配置管理：接收配置数组，内部创建 QwenClient
  - 业务逻辑：参数验证、专业提示词定义、结果解析
  - 异常处理：业务异常和系统异常处理
- **特点**: 简化使用，封装性好，专注于保险承保分析领域

### 内部架构

- **SDK 层**: QwenClient 在内部实例化，负责 API 交互
- **业务层**: 定义专业的保险承保分析提示词和业务逻辑

## ✨ 功能特性

- **单次承保分析**: 对单个承保条件进行专业分析
- **批量承保分析**: 并发处理多个承保分析请求
- **综合承保分析**: 一次性分析多组承保条件，提供综合评估
- **分层架构**: SDK 层与业务层分离，职责清晰
- **专业提示词**: 业务层定义专业的保险承保分析提示词
- **并发支持**: 支持高并发请求处理
- **完善的错误处理**: 区分业务异常和系统异常

## 📋 环境要求

- PHP >= 7.4
- Guzzle HTTP Client >= 7.0
- Composer
- 通义千问 API 密钥

## 🚀 快速开始

### 安装

```bash
composer require janfish/underwrite-agent
```

### 基础使用

```php
<?php

use Janfish\UnderwriteAgent\UnderwriteAgent;

// 配置信息
$config = [
    'apiKey' => 'your-api-key',           // 必需：API密钥
    'timeout' => 30,                      // 可选：请求超时时间（秒）
    'connectTimeout' => 10                // 可选：连接超时时间（秒）
];

// 创建承保分析智能体（内部自动管理QwenClient）
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
$result = $agent->analyze($params);
echo json_encode($result, JSON_PRETTY_PRINT);
```

## 📋 参数说明

### 保险承保分析参数格式

分析参数 `$params` 必须包含以下字段：

| 参数名         | 类型   | 必填 | 说明               | 示例                                                                                              |
| -------------- | ------ | ---- | ------------------ | ------------------------------------------------------------------------------------------------- |
| `company`      | string | 是   | 保险公司名称       | '中意'                                                                                            |
| `type`         | string | 是   | 保单类型           | '套单'                                                                                            |
| `car`          | string | 是   | 车辆类型           | '燃油 旧车'                                                                                       |
| `region`       | string | 是   | 承保区域           | '只保:川 C,E,F,B,H,J,L,Z,X,S,Y,A,G'                                                               |
| `policy`       | string | 是   | 保单政策描述       | '续保，家自车，套单，非过户，含车损车龄 10 年以内；无车损车龄 15 年以内； 川 F 费用 20%，交强 3%' |
| `agentPolicy`  | string | 是   | 代理人政策描述     | '续保，家自车，套单，非过户，含车损车龄 10 年以内；无车损车龄 15 年以内； 川 F 费用 20%，交强 3%' |
| `VCIAgentRate` | string | 否   | 商业险代理费率     | '0.25'                                                                                            |
| `TCIAgentRate` | string | 否   | 交强险代理费率     | '0.25'                                                                                            |
| `NCAgentRate`  | string | 否   | 不计免赔险代理费率 | '0'                                                                                               |
| `TCIRate`      | string | 否   | 交强险费率         | '0.23'                                                                                            |
| `VCIRate`      | string | 否   | 商业险费率         | '0.23'                                                                                            |
| `NCRate`       | string | 否   | 不计免赔险费率     | '0'                                                                                               |

### 参数格式示例

```php
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
```

### 费率参数说明

费率参数支持小数格式（如 '0.25' 表示 25%），系统会自动解析和处理。

## 📖 详细使用

### 1. 创建客户端

```php
use Janfish\UnderwriteAgent\QwenClient;

// 基础配置
$qwenClient = new QwenClient(
    'your-api-key',
    'https://dashscope.aliyuncs.com/api/v1/services/aigc/text-generation/generation',
    30,  // 请求超时时间（秒）
    10   // 连接超时时间（秒）
);
```

### 2. 单次承保分析

```php
$result = $agent->analyze(array $params, string $user = 'default'): array
```

**参数说明:**

- `$params`: 分析参数数组，包含以下必需字段：
  - `company`: 保险公司名称（如：'中意'）
  - `type`: 保单类型（如：'套单'）
  - `car`: 车辆类型（如：'燃油 旧车'）
  - `region`: 承保区域（如：'只保:川 C,E,F,B,H,J,L,Z,X,S,Y,A,G'）
  - `policy`: 保单政策描述
  - `agentPolicy`: 代理人政策描述
  - 可选字段：`VCIAgentRate`, `TCIAgentRate`, `NCAgentRate`, `TCIRate`, `VCIRate`, `NCRate`
- `$user`: 用户标识（可选，默认为'default'）

**返回值:** 解析后的业务数据（JSON 格式）

**特点:** 业务层定义专业的车险承保分析提示词，包含风险评估、定价分析、合规性检查等维度。

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

**特点:** 业务层定义批量分析的专业提示词，SDK 层处理并发请求。

### 4. 综合承保分析

```php
$result = $agent->compositeAnalyze(array $requests): array
```

**参数说明:**

- `$requests`: 请求数组，每个元素包含`params`和`user`字段

**返回值:** 解析后的业务数据（JSON 格式）

**特点:** 区别于 batchAnalyze，综合承保条件分析是将多组承保条件交给大模型一次分析完成，从集团风险管控、市场策略、资源配置等角度提供专业的综合分析报告。

### 5. 直接使用 SDK 层

```php
use Janfish\UnderwriteAgent\QwenClient;

$qwenClient = new QwenClient('your-api-key');

// 自定义提示词
$systemPrompt = "你是一位通用的AI助手。";
$userPrompt = "请介绍一下人工智能在保险行业的应用。";

// 构建消息
$messages = QwenClient::buildMessages($systemPrompt, $userPrompt);

// 发送请求
$response = $qwenClient->chat($messages);

// 处理响应
if (isset($response['choices'][0]['message']['content'])) {
    echo $response['choices'][0]['message']['content'];
}
```

## ⚙️ 配置选项

### 配置选项

UnderwriteAgent 支持以下配置参数：

```php
$config = [
    'apiKey' => 'your-api-key',           // 必需：通义千问 API 密钥
    'baseUrl' => 'https://dashscope.aliyuncs.com/api/v1/services/aigc/text-generation/generation', // 可选：API 地址
    'timeout' => 30,                      // 可选：请求超时时间（秒），默认 30
    'connectTimeout' => 10                // 可选：连接超时时间（秒），默认 10
];

$agent = new UnderwriteAgent($config);
```

## 🛡️ 错误处理

SDK 提供两种异常类型：

- `Janfish\UnderwriteAgent\Exception\Exception`: 业务异常（如参数验证失败）
- `Janfish\UnderwriteAgent\Exception\RuntimeException`: 系统异常（如网络错误、API 响应错误）

```php
use Janfish\UnderwriteAgent\Exception\Exception;
use Janfish\UnderwriteAgent\Exception\RuntimeException;

try {
    $result = $agent->analyze($params);
} catch (Exception $e) {
    // 处理业务异常（如参数错误）
    echo "业务错误: " . $e->getMessage();
} catch (RuntimeException $e) {
    // 处理系统异常（如网络错误）
    echo "系统错误: " . $e->getMessage();
}
```

## 🐳 Docker 测试环境

### 使用 docker-compose

```bash
# 启动测试环境
docker-compose up -d

# 进入容器
docker-compose exec php-test bash

# 在容器中安装依赖
composer install

# 运行示例
php examples/test.php
```

### 使用 Dockerfile 构建

```bash
# 构建镜像
docker build -t underwrite-agent .

# 运行容器
docker run -it -v $(pwd):/data --network janfish underwrite-agent bash

# 在容器中安装依赖
composer install

# 运行示例
php examples/test.php
```

## 📚 示例代码

项目提供了丰富的示例代码：

- `examples/test.php`: 基础使用示例，展示分层架构的使用
- `examples/advanced_example.php`: 高级功能示例，包含所有 API 的详细使用

运行示例：

```bash
# 基础示例
php examples/test.php

# 高级示例
php examples/advanced_example.php
```

## 🎯 架构优势

### 1. 统一管理

- **配置管理**: 通过数组统一传入配置，内部自动管理 QwenClient
- **实例管理**: QwenClient 在内部实例化，无需外部管理
- **简化使用**: 用户只需关注业务逻辑，无需关心 SDK 细节

### 2. 封装性好

- **内部封装**: SDK 层细节完全封装在业务层内部
- **接口简洁**: 提供简洁的业务接口，隐藏复杂实现
- **易于维护**: 内部架构变化不影响外部使用

### 3. 专业化提示词

业务层定义了专业的保险承保分析提示词：

- **单次分析**: 包含风险评估、定价分析、合规性检查、市场分析、承保建议
- **批量分析**: 针对批量场景优化的专业提示词
- **综合分析**: 从集团层面的综合评估和战略分析

### 4. 可扩展性强

- 可以轻松添加新的业务场景
- 可以独立升级 SDK 层或业务层
- 支持直接使用 SDK 层进行通用 AI 调用

### 5. 测试友好

- 各层可以独立测试
- SDK 层可以单独测试 API 交互
- 业务层可以单独测试业务逻辑

## 📊 API 响应格式

智能体返回的分析结果为标准 JSON 格式，包含：

```json
{
  "riskAssessment": "高风险",
  "riskFactors": ["车辆类型风险", "区域风险", "政策风险"],
  "pricingAnalysis": "定价合理，具有市场竞争力",
  "marketCompliance": "符合监管要求",
  "recommendations": ["建议承保", "建议加强风险监控"],
  "confidenceLevel": 0.85
}
```

## ⚠️ 注意事项

1. **API 密钥安全**: 请妥善保管您的通义千问 API 密钥，不要将其提交到代码仓库
2. **并发限制**: 批量分析时请注意 API 的并发限制，合理设置 concurrency 参数
3. **超时设置**: 根据网络状况和 API 响应时间，适当调整 timeout 配置
4. **错误处理**: 建议对所有 API 调用进行适当的异常处理
5. **分层使用**: 推荐按照分层架构使用，业务逻辑放在业务层，API 调用放在 SDK 层

## 📄 许可证

MIT License

## 🤝 支持

如有问题，请通过以下方式联系：

- 邮箱：janfish@example.com
- 提交 Issue 到项目仓库

---

**架构说明**: 新架构采用统一管理设计，UnderwriteAgent 内部封装 QwenClient，通过配置数组统一管理。这种设计提供了更好的封装性、简化使用和易于维护的优势。用户只需关注业务逻辑，无需管理 SDK 细节。
