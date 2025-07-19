<?php

declare(strict_types=1);

namespace OephpOpen\TianDiTu\Services;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use OephpOpen\TianDiTu\Exceptions\TianDiTuException;
use OephpOpen\TianDiTu\Exceptions\ApiException;
use OephpOpen\TianDiTu\Exceptions\NetworkException;
use OephpOpen\TianDiTu\Response\ResponseFormatter;

/**
 * 基础服务类
 */
abstract class BaseService
{
    /**
     * HTTP 客户端
     *
     * @var ClientInterface
     */
    protected $httpClient;

    /**
     * 基础 URL
     *
     * @var string
     */
    protected $baseUrl;

    /**
     * API 密钥
     *
     * @var string
     */
    protected $apiKey;

    /**
     * 构造函数
     *
     * @param ClientInterface $httpClient HTTP客户端
     * @param string $baseUrl 基础URL
     * @param string $apiKey API密钥
     */
    public function __construct($httpClient, $baseUrl, $apiKey)
    {
        $this->httpClient = $httpClient;
        $this->baseUrl = $baseUrl;
        $this->apiKey = $apiKey;
    }

    /**
     * 发送 GET 请求
     *
     * @param string $endpoint API 端点
     * @param array $params 请求参数
     * @return array 响应数据
     * @throws TianDiTuException
     */
    protected function get($endpoint, array $params = [])
    {
        $params['tk'] = $this->apiKey;
        $url = $this->baseUrl . $endpoint;

        try {
            $response = $this->httpClient->request('GET', $url, [
                'query' => $params,
                'headers' => [
                    'Accept' => 'application/json',
                    'User-Agent' => 'TianDiTu-PHP-SDK/1.0',
                ],
            ]);

            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new ApiException('Invalid JSON response: ' . json_last_error_msg());
            }

            // 检查 API 响应状态
            // 不同的API端点有不同的状态格式
            if (isset($data['status'])) {
                // 对于 v2/search API (POI搜索)
                if (is_array($data['status']) && isset($data['status']['infocode'])) {
                    if ($data['status']['infocode'] !== 1000) {
                        throw new ApiException(
                            $data['status']['cndesc'] ?? 'Unknown API error',
                            $data['status']['infocode']
                        );
                    }
                }
                // 对于其他API (地理编码、逆地理编码)
                elseif (is_string($data['status']) && $data['status'] !== '0') {
                    throw new ApiException(
                        isset($data['msg']) ? $data['msg'] : 'Unknown API error',
                        (int) (isset($data['status']) ? $data['status'] : 0)
                    );
                }
            }

            return $data;
        } catch (GuzzleException $e) {
            // 尝试从异常响应中提取API错误信息
            $errorMessage = $this->extractApiErrorMessage($e);
            throw new NetworkException($errorMessage, 0, $e);
        }
    }

    /**
     * 发送 POST 请求
     *
     * @param string $endpoint API 端点
     * @param array $data 请求数据
     * @return array 响应数据
     * @throws TianDiTuException
     */
    protected function post($endpoint, array $data = [])
    {
        $data['tk'] = $this->apiKey;
        $url = $this->baseUrl . $endpoint;

        try {
            $response = $this->httpClient->request('POST', $url, [
                'form_params' => $data,
                'headers' => [
                    'Accept' => 'application/json',
                    'User-Agent' => 'TianDiTu-PHP-SDK/1.0',
                ],
            ]);

            $body = $response->getBody()->getContents();
            $responseData = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new ApiException('Invalid JSON response: ' . json_last_error_msg());
            }

            // 检查 API 响应状态
            if (isset($responseData['status']) && $responseData['status'] !== '0') {
                throw new ApiException(
                    isset($responseData['msg']) ? $responseData['msg'] : 'Unknown API error',
                    (int) (isset($responseData['status']) ? $responseData['status'] : 0)
                );
            }

            return $responseData;
        } catch (GuzzleException $e) {
            // 尝试从异常响应中提取API错误信息
            $errorMessage = $this->extractApiErrorMessage($e);
            throw new NetworkException($errorMessage, 0, $e);
        }
    }

    /**
     * 验证必需参数
     *
     * @param array $params 参数数组
     * @param array $required 必需参数列表
     * @throws TianDiTuException
     */
    protected function validateRequiredParams(array $params, array $required)
    {
        foreach ($required as $param) {
            if (!isset($params[$param]) || $params[$param] === '') {
                throw new TianDiTuException("Missing required parameter: {$param}");
            }
        }
    }

    /**
     * 从 Guzzle 异常中提取 API 错误信息
     *
     * @param GuzzleException $exception Guzzle 异常
     * @return string 提取的错误信息
     */
    private function extractApiErrorMessage(GuzzleException $exception): string
    {
        // 如果是 RequestException，尝试获取响应内容
        if ($exception instanceof \GuzzleHttp\Exception\RequestException && $exception->hasResponse()) {
            $response = $exception->getResponse();
            $statusCode = $response->getStatusCode();

            try {
                $body = $response->getBody()->getContents();
                $data = json_decode($body, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                    // 检查不同的错误格式，按优先级顺序

                    // 格式1: {"msg": "非法Key", "resolve": "...", "code": 1}
                    if (isset($data['msg']) && !empty($data['msg']) && is_string($data['msg'])) {
                        return $data['msg'];
                    }

                    // 格式2: {"status": {"cndesc": "错误描述", "infocode": 1001}}
                    if (isset($data['status']['cndesc']) && !empty($data['status']['cndesc'])) {
                        return $data['status']['cndesc'];
                    }

                    // 格式3: {"message": "错误信息"}
                    if (isset($data['message']) && !empty($data['message']) && is_string($data['message'])) {
                        return $data['message'];
                    }

                    // 格式4: {"error": "错误信息"}
                    if (isset($data['error']) && !empty($data['error']) && is_string($data['error'])) {
                        return $data['error'];
                    }

                    // 格式5: {"desc": "错误描述"}
                    if (isset($data['desc']) && !empty($data['desc']) && is_string($data['desc'])) {
                        return $data['desc'];
                    }
                }

                // 如果无法解析JSON或没有找到合适的错误字段，根据状态码返回简化信息
                if ($statusCode == 403) {
                    return '访问被拒绝';
                } elseif ($statusCode == 404) {
                    return '请求的资源不存在';
                } elseif ($statusCode == 401) {
                    return '身份验证失败';
                } elseif ($statusCode == 500) {
                    return '服务器内部错误';
                } elseif ($statusCode >= 400 && $statusCode < 500) {
                    return '客户端请求错误';
                } elseif ($statusCode >= 500) {
                    return '服务器错误';
                }
            } catch (\Exception $e) {
                // 解析失败，根据状态码返回简化错误
                if ($statusCode == 403) {
                    return '访问被拒绝';
                } elseif ($statusCode == 404) {
                    return '请求的资源不存在';
                }
            }
        }

        // 其他网络错误的简化处理
        $message = $exception->getMessage();

        // 连接超时
        if (strpos($message, 'timeout') !== false || strpos($message, 'timed out') !== false) {
            return '网络连接超时';
        }

        // DNS解析失败
        if (strpos($message, 'resolve') !== false || strpos($message, 'dns') !== false) {
            return 'DNS解析失败';
        }

        // 连接被拒绝
        if (strpos($message, 'Connection refused') !== false) {
            return '连接被拒绝';
        }

        // 网络不可达
        if (strpos($message, 'unreachable') !== false) {
            return '网络不可达';
        }

        // 默认网络错误
        return '网络连接错误';
    }
    /**
     * 执行请求并返回统一格式响应
     *
     * @param callable $requestCallback 请求回调函数
     * @param string $successMessage 成功消息
     * @return array 统一格式响应
     */
    protected function executeRequest(callable $requestCallback, string $successMessage = '请求成功'): array
    {
        try {
            $data = $requestCallback();
            return ResponseFormatter::success($data, $successMessage);
        } catch (TianDiTuException $e) {
            return ResponseFormatter::exception($e);
        } catch (\Exception $e) {
            return ResponseFormatter::exception($e);
        }
    }

    /**
     * 安全执行方法（用于向后兼容）
     *
     * @param callable $callback 回调函数
     * @param mixed $defaultValue 默认值
     * @return mixed
     */
    protected function safeExecute(callable $callback, $defaultValue = null)
    {
        try {
            return $callback();
        } catch (\Exception $e) {
            return $defaultValue;
        }
    }
}
