<?php

declare(strict_types=1);

namespace Janfish\UnderwriteAgent\Exception;

use Throwable;

/**
 * API异常
 */
class ApiException extends Exception
{
    private ?array $responseData;

    /**
     * @param string $message
     * @param int $code
     * @param array|null $responseData API响应数据
     * @param Throwable|null $previous
     */
    public function __construct(string $message, int $code = 0, ?array $responseData = null, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->responseData = $responseData;
    }

    /**
     * 获取API响应数据
     *
     * @return array|null
     */
    public function getResponseData(): ?array
    {
        return $this->responseData;
    }
}