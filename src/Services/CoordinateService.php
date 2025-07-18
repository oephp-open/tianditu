<?php

declare(strict_types=1);

namespace OephpOpen\TianDiTu\Services;

use OephpOpen\TianDiTu\Exceptions\TianDiTuException;

/**
 * 坐标转换服务类
 */
class CoordinateService extends BaseService
{
    /**
     * 坐标转换
     *
     * @param float $lon 经度
     * @param float $lat 纬度
     * @param int $fromType 源坐标系类型
     * @param int $toType 目标坐标系类型
     * @param array $options 可选参数
     * @return array 转换结果
     * @throws TianDiTuException
     */
    public function transform(
        $lon,
        $lat,
        $fromType = 1,
        $toType = 2,
        array $options = []
    ) {
        $this->validateRequiredParams([
            'lon' => $lon,
            'lat' => $lat,
        ], ['lon', 'lat']);

        $params = array_merge([
            'postStr' => json_encode([
                'points' => [
                    ['x' => $lon, 'y' => $lat]
                ],
                'type' => "{$fromType},{$toType}",
            ]),
            'type' => 'transform',
        ], $options);

        $response = $this->get('/coord', $params);

        return $this->formatCoordinateResponse($response);
    }

    /**
     * 批量坐标转换
     *
     * @param array $coordinates 坐标点列表 [['lon' => 116.3974, 'lat' => 39.9093], ...]
     * @param int $fromType 源坐标系类型
     * @param int $toType 目标坐标系类型
     * @param array $options 可选参数
     * @return array 转换结果
     * @throws TianDiTuException
     */
    public function batchTransform(
        array $coordinates,
        $fromType = 1,
        $toType = 2,
        array $options = []
    ) {
        if (empty($coordinates)) {
            throw new TianDiTuException('Coordinates array cannot be empty');
        }

        if (count($coordinates) > 100) {
            throw new TianDiTuException('Maximum 100 coordinates allowed per batch request');
        }

        $points = [];
        foreach ($coordinates as $coord) {
            if (!isset($coord['lon']) || !isset($coord['lat'])) {
                throw new TianDiTuException('Each coordinate must have lon and lat keys');
            }
            $points[] = [
                'x' => (float) $coord['lon'],
                'y' => (float) $coord['lat'],
            ];
        }

        $params = array_merge([
            'postStr' => json_encode([
                'points' => $points,
                'type' => "{$fromType},{$toType}",
            ]),
            'type' => 'transform',
        ], $options);

        $response = $this->get('/coord', $params);

        return $this->formatBatchCoordinateResponse($response);
    }

    /**
     * 获取支持的坐标系类型
     *
     * @return array 坐标系类型列表
     */
    public function getSupportedCoordinateSystems()
    {
        return [
            1 => 'WGS84经纬度坐标',
            2 => 'GCJ02经纬度坐标',
            3 => 'BD09经纬度坐标',
            4 => 'BD09米制坐标',
            5 => 'CGCS2000经纬度坐标',
            6 => 'CGCS2000米制坐标',
        ];
    }

    /**
     * 格式化坐标转换响应
     *
     * @param array $response 原始响应
     * @return array 格式化后的结果
     */
    private function formatCoordinateResponse(array $response)
    {
        if (!isset($response['result'])) {
            return [];
        }

        $result = $response['result'];

        if (isset($result[0])) {
            return [
                'lon' => (float) $result[0]['x'],
                'lat' => (float) $result[0]['y'],
            ];
        }

        return [];
    }

    /**
     * 格式化批量坐标转换响应
     *
     * @param array $response 原始响应
     * @return array 格式化后的结果
     */
    private function formatBatchCoordinateResponse(array $response)
    {
        if (!isset($response['result'])) {
            return [];
        }

        $results = [];
        foreach ($response['result'] as $point) {
            $results[] = [
                'lon' => (float) $point['x'],
                'lat' => (float) $point['y'],
            ];
        }

        return $results;
    }
}
