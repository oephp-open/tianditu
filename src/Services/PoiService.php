<?php

declare(strict_types=1);

namespace OephpOpen\TianDiTu\Services;

use OephpOpen\TianDiTu\Exceptions\TianDiTuException;
use OephpOpen\TianDiTu\Response\ResponseFormatter;

/**
 * POI 搜索服务类
 */
class PoiService extends BaseService
{
    /**
     * 普通POI搜索
     *
     * @param string $keyword 搜索关键词
     * @param array $options 可选参数
     * @return array 统一格式响应 [ret, msg, data]
     */
    public function search(string $keyword, array $options = []): array
    {
        return $this->executeRequest(function () use ($keyword, $options) {
            if (empty($keyword)) {
                throw new TianDiTuException('Missing required parameter: keyword');
            }

            $params = [
                'keyWord' => $keyword,
                'level' => $options['level'] ?? '12',
                'mapBound' => $options['mapBound'] ?? '-180,-90,180,90',
                'queryType' => '1', // 普通搜索
                'start' => $options['start'] ?? '0',
                'count' => $options['count'] ?? '10'
            ];

            // 添加可选参数
            if (isset($options['specify'])) {
                $params['specify'] = $options['specify'];
            }
            if (isset($options['dataTypes'])) {
                $params['dataTypes'] = $options['dataTypes'];
            }
            if (isset($options['show'])) {
                $params['show'] = $options['show'];
            }

            $response = $this->get('/v2/search', [
                'postStr' => json_encode($params),
                'type' => 'query'
            ]);

            return $this->formatPoiResponse($response);
        }, 'POI搜索成功');
    }

    /**
     * 周边搜索
     *
     * @param string $keyword 搜索关键词
     * @param float $lon 中心点经度
     * @param float $lat 中心点纬度
     * @param int $radius 搜索半径（米）
     * @param array $options 可选参数
     * @return array 统一格式响应 [ret, msg, data]
     */
    public function searchNearby(
        string $keyword,
        float $lon,
        float $lat,
        int $radius = 1000,
        array $options = []
    ): array {
        return $this->executeRequest(function () use ($keyword, $lon, $lat, $radius, $options) {
            if (empty($keyword)) {
                throw new TianDiTuException('Missing required parameter: keyword');
            }

            $params = [
                'keyWord' => $keyword,
                'queryRadius' => (string)$radius,
                'pointLonlat' => "{$lon},{$lat}",
                'queryType' => '3', // 周边搜索
                'start' => $options['start'] ?? '0',
                'count' => $options['count'] ?? '10'
            ];

            // 添加可选参数
            if (isset($options['dataTypes'])) {
                $params['dataTypes'] = $options['dataTypes'];
            }
            if (isset($options['show'])) {
                $params['show'] = $options['show'];
            }

            $response = $this->get('/v2/search', [
                'postStr' => json_encode($params),
                'type' => 'query'
            ]);

            return $this->formatPoiResponse($response);
        }, '周边搜索成功');
    }

    /**
     * 视野内搜索
     *
     * @param string $keyword 搜索关键词
     * @param float $minLon 最小经度
     * @param float $minLat 最小纬度
     * @param float $maxLon 最大经度
     * @param float $maxLat 最大纬度
     * @param array $options 可选参数
     * @return array 统一格式响应 [ret, msg, data]
     */
    public function searchInBounds(
        string $keyword,
        float $minLon,
        float $minLat,
        float $maxLon,
        float $maxLat,
        array $options = []
    ): array {
        return $this->executeRequest(function () use ($keyword, $minLon, $minLat, $maxLon, $maxLat, $options) {
            if (empty($keyword)) {
                throw new TianDiTuException('Missing required parameter: keyword');
            }

            $params = [
                'keyWord' => $keyword,
                'mapBound' => "{$minLon},{$minLat},{$maxLon},{$maxLat}",
                'level' => $options['level'] ?? '12',
                'queryType' => '2', // 视野内搜索
                'start' => $options['start'] ?? '0',
                'count' => $options['count'] ?? '10'
            ];

            // 添加可选参数
            if (isset($options['dataTypes'])) {
                $params['dataTypes'] = $options['dataTypes'];
            }
            if (isset($options['show'])) {
                $params['show'] = $options['show'];
            }

            $response = $this->get('/v2/search', [
                'postStr' => json_encode($params),
                'type' => 'query'
            ]);

            return $this->formatPoiResponse($response);
        }, 'POI视野内搜索成功');
    }
    /**
     * 行政区域搜索
     *
     * @param string $keyword 搜索关键词
     * @param string $adminCode 行政区编码
     * @param array $options 可选参数
     * @return array 统一格式响应 [ret, msg, data]
     */
    public function searchInAdmin(
        string $keyword,
        string $adminCode,
        array $options = []
    ): array {
        return $this->executeRequest(function () use ($keyword, $adminCode, $options) {
            if (empty($keyword)) {
                throw new TianDiTuException('Missing required parameter: keyword');
            }
            if (empty($adminCode)) {
                throw new TianDiTuException('Missing required parameter: adminCode');
            }

            $params = [
                'keyWord' => $keyword,
                'specify' => $adminCode,
                'queryType' => '12', // 行政区域搜索
                'start' => $options['start'] ?? '0',
                'count' => $options['count'] ?? '10'
            ];

            // 添加可选参数
            if (isset($options['dataTypes'])) {
                $params['dataTypes'] = $options['dataTypes'];
            }
            if (isset($options['show'])) {
                $params['show'] = $options['show'];
            }

            $response = $this->get('/v2/search', [
                'postStr' => json_encode($params),
                'type' => 'query'
            ]);

            return $this->formatPoiResponse($response);
        }, 'POI行政区域搜索成功');
    }

    /**
     * 格式化POI响应
     *
     * @param array $response
     * @return array
     */
    private function formatPoiResponse(array $response): array
    {
        if (
            !isset($response['status']) || !isset($response['status']['infocode']) ||
            $response['status']['infocode'] !== 1000
        ) {
            return [
                'total' => 0,
                'pois' => [],
                'status' => $response['status'] ?? ['infocode' => -1, 'cndesc' => '未知错误']
            ];
        }

        $pois = [];
        if (isset($response['pois']) && is_array($response['pois'])) {
            foreach ($response['pois'] as $poi) {
                $lonlatArray = isset($poi['lonlat']) ? explode(',', $poi['lonlat']) : [0, 0];

                $pois[] = [
                    'name' => $poi['name'] ?? '',
                    'address' => $poi['address'] ?? '',
                    'location' => [
                        'lon' => (float)(count($lonlatArray) > 0 ? $lonlatArray[0] : 0),
                        'lat' => (float)(count($lonlatArray) > 1 ? $lonlatArray[1] : 0)
                    ],
                    'phone' => $poi['phone'] ?? '',
                    'hotPointID' => $poi['hotPointID'] ?? '',
                    'poiType' => $poi['poiType'] ?? 0,
                    'province' => $poi['province'] ?? '',
                    'city' => $poi['city'] ?? '',
                    'county' => $poi['county'] ?? '',
                    'typeCode' => $poi['typeCode'] ?? '',
                    'typeName' => $poi['typeName'] ?? '',
                    'source' => $poi['source'] ?? ''
                ];
            }
        }

        return [
            'total' => (int)($response['count'] ?? 0),
            'pois' => $pois,
            'keyword' => $response['keyword'] ?? '',
            'resultType' => $response['resultType'] ?? 0,
            'status' => $response['status']
        ];
    }
}
