<?php

declare(strict_types=1);

namespace Janfish\UnderwriteAgent\Logger;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * 简单的日志记录器实现
 * 支持文件输出和基本的日志级别控制
 */
class Logger implements LoggerInterface
{
    private string $logFile;
    private string $logLevel;
    private array $logLevels = [
        LogLevel::EMERGENCY => 0,
        LogLevel::ALERT => 1,
        LogLevel::CRITICAL => 2,
        LogLevel::ERROR => 3,
        LogLevel::WARNING => 4,
        LogLevel::NOTICE => 5,
        LogLevel::INFO => 6,
        LogLevel::DEBUG => 7,
    ];

    public function __construct(string $logFile = null, string $logLevel = LogLevel::INFO)
    {
        $this->logFile = $logFile ?? sys_get_temp_dir() . '/underwrite_agent.log';
        $this->logLevel = $logLevel;

        // 确保日志目录存在
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    /**
     * 系统不可用
     */
    public function emergency($message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    /**
     * 必须立即采取行动
     */
    public function alert($message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    /**
     * 严重错误
     */
    public function critical($message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    /**
     * 运行时错误，不需要立即处理，但应该被记录和监控
     */
    public function error($message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    /**
     * 异常但非错误，可能使用了废弃的API等
     */
    public function warning($message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    /**
     * 正常但重要的事件
     */
    public function notice($message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    /**
     * 一般性信息
     */
    public function info($message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    /**
     * 详细信息，仅在调试时输出
     */
    public function debug($message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    /**
     * 记录日志
     */
    public function log($level, $message, array $context = []): void
    {
        if (!isset($this->logLevels[$level])) {
            throw new \InvalidArgumentException("Invalid log level: {$level}");
        }

        // 检查日志级别
        if ($this->logLevels[$level] > $this->logLevels[$this->logLevel]) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $formattedMessage = $this->formatMessage($level, $message, $context);

        $logEntry = "[{$timestamp}] [{$level}] {$formattedMessage}" . PHP_EOL;

        // 写入日志文件
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }

    /**
     * 格式化日志消息
     */
    private function formatMessage(string $level, string $message, array $context): string
    {
        // 替换上下文中的占位符
        if (!empty($context)) {
            $replacements = [];
            foreach ($context as $key => $value) {
                if (is_scalar($value)) {
                    $replacements['{' . $key . '}'] = (string)$value;
                } elseif (is_array($value) || is_object($value)) {
                    $replacements['{' . $key . '}'] = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
            }
            $message = strtr($message, $replacements);
        }

        return $message;
    }

    /**
     * 设置日志级别
     */
    public function setLogLevel(string $level): void
    {
        if (!isset($this->logLevels[$level])) {
            throw new \InvalidArgumentException("Invalid log level: {$level}");
        }

        $this->logLevel = $level;
    }

    /**
     * 获取日志级别
     */
    public function getLogLevel(): string
    {
        return $this->logLevel;
    }

    /**
     * 获取日志文件路径
     */
    public function getLogFile(): string
    {
        return $this->logFile;
    }

    /**
     * 清理日志文件
     */
    public function clearLog(): bool
    {
        if (file_exists($this->logFile)) {
            return file_put_contents($this->logFile, '') !== false;
        }

        return true;
    }

    /**
     * 获取日志文件大小（字节）
     */
    public function getLogFileSize(): int
    {
        return file_exists($this->logFile) ? filesize($this->logFile) : 0;
    }
}