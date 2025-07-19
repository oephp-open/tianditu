<?php

declare(strict_types=1);

namespace OephpOpen\TianDiTu\Services;

use OephpOpen\TianDiTu\Exceptions\TianDiTuException;
use OephpOpen\TianDiTu\Response\ResponseFormatter;

/**
 * 路径规划服务类
 */
class RouteService extends BaseService
{
    /**
     * 驾车路径规划
     *
     * @param float $startLon 起点经度
     * @param float $startLat 起点纬度
     * @param float $endLon 终点经度
     * @param float $endLat 终点纬度
     * @param array $options 可选参数
     * @return array 统一格式响应 [ret, msg, data]
     */
    public function driving(
        $startLon,
        $startLat,
        $endLon,
        $endLat,
        array $options = []
    ): array {
        return $this->executeRequest(function () use ($startLon, $startLat, $endLon, $endLat, $options) {
            $this->validateRequiredParams([
                'startLon' => $startLon,
                'startLat' => $startLat,
                'endLon' => $endLon,
                'endLat' => $endLat,
            ], ['startLon', 'startLat', 'endLon', 'endLat']);

            $params = array_merge([
                'postStr' => json_encode([
                    'orig' => "{$startLon},{$startLat}",
                    'dest' => "{$endLon},{$endLat}",
                    'style' => '0', // 默认推荐路线
                ]),
                'type' => 'search',
            ], $options);

            $response = $this->get('/drive', $params);

            return $this->formatRouteResponse($response);
        }, '驾车路径规划成功');
    }

    /**
     * 步行路径规划
     *
     * @param float $startLon 起点经度
     * @param float $startLat 起点纬度
     * @param float $endLon 终点经度
     * @param float $endLat 终点纬度
     * @param array $options 可选参数
     * @return array 统一格式响应 [ret, msg, data]
     */
    public function walking(
        $startLon,
        $startLat,
        $endLon,
        $endLat,
        array $options = []
    ): array {
        return $this->executeRequest(function () use ($startLon, $startLat, $endLon, $endLat, $options) {
            $this->validateRequiredParams([
                'startLon' => $startLon,
                'startLat' => $startLat,
                'endLon' => $endLon,
                'endLat' => $endLat,
            ], ['startLon', 'startLat', 'endLon', 'endLat']);

            $params = array_merge([
                'postStr' => json_encode([
                    'orig' => "{$startLon},{$startLat}",
                    'dest' => "{$endLon},{$endLat}",
                ]),
                'type' => 'search',
            ], $options);

            $response = $this->get('/walk', $params);

            return $this->formatRouteResponse($response);
        }, '步行路径规划成功');
    }

    /**
     * 公交路径规划
     *
     * @param string $startAddress 起点地址
     * @param string $endAddress 终点地址
     * @param string $city 城市名称
     * @param array $options 可选参数
     * @return array 统一格式响应 [ret, msg, data]
     */
    public function transit($startAddress, $endAddress, $city, array $options = []): array
    {
        return $this->executeRequest(function () use ($startAddress, $endAddress, $city, $options) {
            $this->validateRequiredParams([
                'startAddress' => $startAddress,
                'endAddress' => $endAddress,
                'city' => $city,
            ], ['startAddress', 'endAddress', 'city']);

            $params = array_merge([
                'postStr' => json_encode([
                    'startPosition' => $startAddress,
                    'endPosition' => $endAddress,
                    'city' => $city,
                ]),
                'type' => 'search',
            ], $options);

            $response = $this->get('/transit', $params);

            return $this->formatTransitResponse($response);
        }, '公交路径规划成功');
    }

    /**
     * 格式化路径规划响应（驾车、步行）
     *
     * @param array $response 原始响应
     * @return array 格式化后的结果
     */
    private function formatRouteResponse(array $response)
    {
        if (!isset($response['result'])) {
            return [];
        }

        $result = $response['result'];

        return [
            'distance' => (float) (isset($result['distance']) ? $result['distance'] : 0),
            'duration' => (int) (isset($result['duration']) ? $result['duration'] : 0),
            'routes' => $this->formatRoutes(isset($result['routes']) ? $result['routes'] : []),
        ];
    }

    /**
     * 格式化公交路径规划响应
     *
     * @param array $response 原始响应
     * @return array 格式化后的结果
     */
    private function formatTransitResponse(array $response)
    {
        if (!isset($response['result'])) {
            return [];
        }

        $result = $response['result'];

        return [
            'lines' => $this->formatTransitLines(isset($result['lines']) ? $result['lines'] : []),
        ];
    }

    /**
     * 格式化路线信息
     *
     * @param array $routes 原始路线数据
     * @return array 格式化后的路线
     */
    private function formatRoutes(array $routes)
    {
        $formattedRoutes = [];

        foreach ($routes as $route) {
            $formattedRoutes[] = [
                'distance' => (float) (isset($route['distance']) ? $route['distance'] : 0),
                'duration' => (int) (isset($route['duration']) ? $route['duration'] : 0),
                'steps' => $this->formatSteps(isset($route['steps']) ? $route['steps'] : []),
                'polyline' => isset($route['polyline']) ? $route['polyline'] : '',
            ];
        }

        return $formattedRoutes;
    }

    /**
     * 格式化步骤信息
     *
     * @param array $steps 原始步骤数据
     * @return array 格式化后的步骤
     */
    private function formatSteps(array $steps)
    {
        $formattedSteps = [];

        foreach ($steps as $step) {
            $formattedSteps[] = [
                'instruction' => isset($step['instruction']) ? $step['instruction'] : '',
                'distance' => (float) (isset($step['distance']) ? $step['distance'] : 0),
                'duration' => (int) (isset($step['duration']) ? $step['duration'] : 0),
                'polyline' => isset($step['polyline']) ? $step['polyline'] : '',
            ];
        }

        return $formattedSteps;
    }

    /**
     * 格式化公交线路信息
     *
     * @param array $lines 原始线路数据
     * @return array 格式化后的线路
     */
    private function formatTransitLines(array $lines)
    {
        $formattedLines = [];

        foreach ($lines as $line) {
            $formattedLines[] = [
                'distance' => (float) (isset($line['distance']) ? $line['distance'] : 0),
                'duration' => (int) (isset($line['duration']) ? $line['duration'] : 0),
                'price' => (float) (isset($line['price']) ? $line['price'] : 0),
                'segments' => $this->formatTransitSegments(isset($line['segments']) ? $line['segments'] : []),
            ];
        }

        return $formattedLines;
    }

    /**
     * 格式化公交段落信息
     *
     * @param array $segments 原始段落数据
     * @return array 格式化后的段落
     */
    private function formatTransitSegments(array $segments)
    {
        $formattedSegments = [];

        foreach ($segments as $segment) {
            $formattedSegments[] = [
                'instruction' => isset($segment['instruction']) ? $segment['instruction'] : '',
                'line_name' => isset($segment['line_name']) ? $segment['line_name'] : '',
                'start_stop' => isset($segment['start_stop']) ? $segment['start_stop'] : '',
                'end_stop' => isset($segment['end_stop']) ? $segment['end_stop'] : '',
                'distance' => (float) (isset($segment['distance']) ? $segment['distance'] : 0),
                'duration' => (int) (isset($segment['duration']) ? $segment['duration'] : 0),
            ];
        }

        return $formattedSegments;
    }
}
