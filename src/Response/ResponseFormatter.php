<?php

declare(strict_types=1);

namespace OephpOpen\TianDiTu\Response;

/**
 * 统一响应格式化类
 */
class ResponseFormatter
{
    /**
     * 成功响应
     *
     * @param mixed $data 响应数据
     * @param string $message 成功消息
     * @return array
     */
    public static function success($data = null, string $message = '操作成功'): array
    {
        return [
            'ret' => 1,
            'msg' => $message,
            'data' => $data
        ];
    }

    /**
     * 失败响应
     *
     * @param string $message 错误消息
     * @param mixed $data 附加数据
     * @return array
     */
    public static function failure(string $message = '操作失败', $data = null): array
    {
        return [
            'ret' => 0,
            'msg' => $message,
            'data' => $data
        ];
    }

    /**
     * 异常响应
     *
     * @param \Exception $exception 异常对象
     * @param mixed $data 附加数据
     * @return array
     */
    public static function exception(\Exception $exception, $data = null): array
    {
        return [
            'ret' => 0,
            'msg' => $exception->getMessage(),
            'data' => $data
        ];
    }
}
