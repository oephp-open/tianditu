<?php

declare(strict_types=1);

namespace OephpOpen\TianDiTu\Services;

use OephpOpen\TianDiTu\Exceptions\TianDiTuException;

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
     * @return array
     * @throws TianDiTuException
     */
    public function search(string $address, array $options = []): array
    {
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
    }

    /**
     * 批量地理编码查询
     *
     * @param array $addresses 地址列表
     * @param array $options 可选参数
     * @return array 查询结果
     * @throws TianDiTuException
     */
    public function batchSearch(array $addresses, array $options = [])
    {
        if (empty($addresses)) {
            throw new TianDiTuException('Addresses array cannot be empty');
        }

        if (count($addresses) > 10) {
            throw new TianDiTuException('Maximum 10 addresses allowed per batch request');
        }

        $results = [];
        foreach ($addresses as $address) {
            $results[] = $this->search($address, $options);
        }

        return $results;
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
