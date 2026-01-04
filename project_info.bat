@echo off
echo 保险承保分析智能体项目创建完成！
echo ==============================
echo.
echo 项目结构：
tree /F
echo.
echo 主要文件说明：
echo - src/UnderwriteAgent.php: 核心智能体类
echo - src/Exception/: 异常处理类
echo - examples/: 使用示例
echo - composer.json: Composer配置文件
echo - docker-compose.yml: Docker测试环境
echo - README.md: 项目文档
echo.
echo 使用Docker测试环境：
echo 1. 确保Docker已安装
echo 2. 运行: docker-compose up -d
echo 3. 进入容器: docker-compose exec php-test bash
echo 4. 安装依赖: composer install
echo 5. 运行示例: php examples/test.php
echo.
echo 在本地环境中：
echo 1. 安装PHP 7.4+ 和 Composer
echo 2. 运行: composer install
echo 3. 配置API密钥后运行示例
echo.
echo 注意：使用前需要在示例文件中配置正确的通义千问API密钥
echo.
pause