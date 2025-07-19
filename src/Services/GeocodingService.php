<?php

declare(strict_types=1);

namespace OephpOpen\TianDiTu\Services;

use OephpOpen\TianDiTu\Exceptions\TianDiTuException;
use OephpOpen\TianDiTu\Response\ResponseFormatter;

/**
 * 地理编码服务类
 */
class GeocodingService extends BaseService
{
    /**
     * 地理编码：根据地址描述获取地理坐标
     *
     * @param string $address 地址
     * @param array $options 可选参数
     * @return array 统一格式响应 [ret, msg, data]
     */
    public function search(string $address, array $options = []): array
    {
        return $this->executeRequest(function () use ($address, $options) {
            if (empty($address)) {
                throw new TianDiTuException('Missing required parameter: address');
            }

            // 天地图地理编码API正确格式
            $params = [
                'ds' => json_encode([
                    'keyWord' => $address
                ])
            ];

            $params = array_merge($params, $options);

            $response = $this->get('/geocoder', $params);

            return $this->formatGeocodingResponse($response);
        }, '地理编码查询成功');
    }

    /**
     * 批量地理编码查询
     *
     * @param array $addresses 地址列表
     * @param array $options 可选参数
     * @return array 统一格式响应 [ret, msg, data]
     */
    public function batchSearch(array $addresses, array $options = []): array
    {
        return $this->executeRequest(function () use ($addresses, $options) {
            if (empty($addresses)) {
                throw new TianDiTuException('Addresses array cannot be empty');
            }

            if (count($addresses) > 10) {
                throw new TianDiTuException('Maximum 10 addresses allowed per batch request');
            }

            $results = [];
            foreach ($addresses as $address) {
                // 调用原始内部方法获取数据
                if (empty($address)) {
                    throw new TianDiTuException('Missing required parameter: address');
                }

                $params = [
                    'ds' => json_encode([
                        'keyWord' => $address
                    ])
                ];

                $params = array_merge($params, $options);
                $response = $this->get('/geocoder', $params);
                $results[] = $this->formatGeocodingResponse($response);
            }

            return $results;
        }, '批量地理编码查询成功');
    }

    /**
     * 格式化地理编码响应
     *
     * @param array $response
     * @return array
     */
    private function formatGeocodingResponse(array $response): array
    {
        if (!isset($response['status']) || $response['status'] !== '0') {
            return [];
        }

        if (!isset($response['location'])) {
            return [];
        }

        $location = $response['location'];

        return [
            'formatted_address' => $location['keyWord'] ?? '',
            'location' => [
                'lon' => (float)($location['lon'] ?? 0),
                'lat' => (float)($location['lat'] ?? 0)
            ],
            'level' => $location['level'] ?? '',
            'score' => $location['score'] ?? 0
        ];
    }
    /**
     * 格式化批量地理编码响应
     *
     * @param array $response 原始响应
     * @return array 格式化后的结果
     */
    private function formatBatchGeocodingResponse(array $response)
    {
        if (!isset($response['result'])) {
            return [];
        }

        $results = [];
        foreach ($response['result'] as $item) {
            $results[] = [
                'location' => [
                    'lon' => (float) $item['location']['lon'],
                    'lat' => (float) $item['location']['lat'],
                ],
                'formatted_address' => isset($item['formatted_address']) ? $item['formatted_address'] : '',
                'level' => isset($item['level']) ? $item['level'] : '',
                'confidence' => (int) (isset($item['confidence']) ? $item['confidence'] : 0),
                'comprehension' => (int) (isset($item['comprehension']) ? $item['comprehension'] : 0),
            ];
        }

        return $results;
    }
}
