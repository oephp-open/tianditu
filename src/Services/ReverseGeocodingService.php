<?php

declare(strict_types=1);

namespace OephpOpen\TianDiTu\Services;

use OephpOpen\TianDiTu\Exceptions\TianDiTuException;
use OephpOpen\TianDiTu\Response\ResponseFormatter;

/**
 * 逆地理编码服务类
 */
class ReverseGeocodingService extends BaseService
{
    /**
     * 逆地理编码查询
     *
     * @param float $lon 经度
     * @param float $lat 纬度
     * @param array $options 可选参数
     * @return array 统一格式响应 [ret, msg, data]
     */
    public function search($lon, $lat, array $options = []): array
    {
        return $this->executeRequest(function () use ($lon, $lat, $options) {
            $params = array_merge([
                'postStr' => json_encode([
                    'lon' => $lon,
                    'lat' => $lat,
                    'ver' => 1,
                ]),
                'type' => 'geocode',
            ], $options);

            $response = $this->get('/geocoder', $params);

            return $this->formatReverseGeocodingResponse($response);
        }, '逆地理编码查询成功');
    }

    /**
     * 批量逆地理编码查询
     *
     * @param array $coordinates 坐标点列表 [['lon' => 116.3974, 'lat' => 39.9093], ...]
     * @param array $options 可选参数
     * @return array 统一格式响应 [ret, msg, data]
     */
    public function batchSearch(array $coordinates, array $options = []): array
    {
        return $this->executeRequest(function () use ($coordinates, $options) {
            if (empty($coordinates)) {
                throw new TianDiTuException('Coordinates array cannot be empty');
            }

            if (count($coordinates) > 100) {
                throw new TianDiTuException('Maximum 100 coordinates allowed per batch request');
            }

            $postData = [];
            foreach ($coordinates as $coord) {
                if (!isset($coord['lon']) || !isset($coord['lat'])) {
                    throw new TianDiTuException('Each coordinate must have lon and lat keys');
                }
                $postData[] = [
                    'lon' => (float) $coord['lon'],
                    'lat' => (float) $coord['lat'],
                    'ver' => 1,
                ];
            }

            $params = array_merge([
                'postStr' => json_encode($postData),
                'type' => 'geocode',
            ], $options);

            $response = $this->get('/geocoder', $params);

            return $this->formatBatchReverseGeocodingResponse($response);
        }, '批量逆地理编码查询成功');
    }

    /**
     * 格式化逆地理编码响应
     *
     * @param array $response 原始响应
     * @return array 格式化后的结果
     */
    private function formatReverseGeocodingResponse(array $response)
    {
        if (!isset($response['result'])) {
            return [];
        }

        $result = $response['result'];

        return [
            'formatted_address' => isset($result['formatted_address'])
                ? $result['formatted_address'] : '',
            'address_component' => [
                'address' => isset($result['addressComponent']['address'])
                    ? $result['addressComponent']['address'] : '',
                'address_detail' => isset($result['addressComponent']['address_detail'])
                    ? $result['addressComponent']['address_detail'] : '',
                'city' => isset($result['addressComponent']['city'])
                    ? $result['addressComponent']['city'] : '',
                'county' => isset($result['addressComponent']['county'])
                    ? $result['addressComponent']['county'] : '',
                'province' => isset($result['addressComponent']['province'])
                    ? $result['addressComponent']['province'] : '',
                'road' => isset($result['addressComponent']['road'])
                    ? $result['addressComponent']['road'] : '',
                'poi' => isset($result['addressComponent']['poi'])
                    ? $result['addressComponent']['poi'] : '',
                'poi_position' => isset($result['addressComponent']['poi_position'])
                    ? $result['addressComponent']['poi_position'] : '',
            ],
        ];
    }

    /**
     * 格式化批量逆地理编码响应
     *
     * @param array $response 原始响应
     * @return array 格式化后的结果
     */
    private function formatBatchReverseGeocodingResponse(array $response)
    {
        if (!isset($response['result'])) {
            return [];
        }

        $results = [];
        foreach ($response['result'] as $item) {
            $results[] = [
                'formatted_address' => isset($item['formatted_address'])
                    ? $item['formatted_address'] : '',
                'address_component' => [
                    'address' => isset($item['addressComponent']['address'])
                        ? $item['addressComponent']['address'] : '',
                    'address_detail' => isset($item['addressComponent']['address_detail'])
                        ? $item['addressComponent']['address_detail'] : '',
                    'city' => isset($item['addressComponent']['city'])
                        ? $item['addressComponent']['city'] : '',
                    'county' => isset($item['addressComponent']['county'])
                        ? $item['addressComponent']['county'] : '',
                    'province' => isset($item['addressComponent']['province'])
                        ? $item['addressComponent']['province'] : '',
                    'road' => isset($item['addressComponent']['road'])
                        ? $item['addressComponent']['road'] : '',
                    'poi' => isset($item['addressComponent']['poi'])
                        ? $item['addressComponent']['poi'] : '',
                    'poi_position' => isset($item['addressComponent']['poi_position'])
                        ? $item['addressComponent']['poi_position'] : '',
                ],
            ];
        }

        return $results;
    }
}
