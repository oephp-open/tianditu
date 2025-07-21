<?php

declare(strict_types=1);

namespace OephpOpen\TianDiTu\Services;

use OephpOpen\TianDiTu\Exceptions\TianDiTuException;

/**
 * 坐标转换服务类
 * 
 * 支持WGS84、GCJ02、BD09坐标系之间的相互转换
 * 
 * @package OephpOpen\TianDiTu\Services
 */
class CoordinateService extends BaseService
{
    // 地球半径(米)
    private const EARTH_RADIUS = 6378137;

    // 椭球偏心率平方
    private const ECCENTRICITY_SQUARED = '0.00669342162296594323';

    // 中国范围边界
    private const CHINA_MIN_LNG = '72.004';
    private const CHINA_MAX_LNG = '137.8347';
    private const CHINA_MIN_LAT = '0.8293';
    private const CHINA_MAX_LAT = '55.8271';

    // BD09转换常量
    private const BD09_X_PI = '3.14159265358979324';
    private const BD09_OFFSET_X = '0.0065';
    private const BD09_OFFSET_Y = '0.006';
    private const BD09_FACTOR = '3000.0';

    // 迭代转换参数
    private const ITERATION_THRESHOLD = '0.000000001';
    private const MAX_ITERATIONS = 1000;

    /**
     * 验证坐标参数
     * 
     * @param mixed $lat 纬度
     * @param mixed $lng 经度
     * @throws TianDiTuException 当坐标参数无效时
     */
    private function validateCoordinate($lat, $lng): void
    {
        if (!is_numeric($lat) || !is_numeric($lng)) {
            throw new TianDiTuException('坐标参数必须是数值类型');
        }

        $latFloat = (float) $lat;
        $lngFloat = (float) $lng;

        if ($latFloat < -90 || $latFloat > 90) {
            throw new TianDiTuException('纬度必须在-90到90之间');
        }

        if ($lngFloat < -180 || $lngFloat > 180) {
            throw new TianDiTuException('经度必须在-180到180之间');
        }
    }

    /**
     * 判断坐标是否在中国范围内
     * 
     * @param string|float $lat 纬度
     * @param string|float $lng 经度
     * @return array 统一格式响应 [ret, msg, data]
     */
    public function outOfChina($lat, $lng): array
    {
        return $this->executeRequest(function () use ($lat, $lng) {
            $this->validateCoordinate($lat, $lng);

            $latFloat = (float) $lat;
            $lngFloat = (float) $lng;

            $isOutOfChina = ($lngFloat < (float) self::CHINA_MIN_LNG || $lngFloat > (float) self::CHINA_MAX_LNG)
                || ($latFloat < (float) self::CHINA_MIN_LAT || $latFloat > (float) self::CHINA_MAX_LAT);

            return [
                'is_out_of_china' => $isOutOfChina,
                'location' => [
                    'lat' => $latFloat,
                    'lng' => $lngFloat
                ]
            ];
        }, '坐标范围检查完成');
    }

    /**
     * 内部判断坐标是否在中国范围内（用于其他方法调用）
     * 
     * @param float $lat 纬度
     * @param float $lng 经度
     * @return bool true表示不在中国范围内
     */
    private function isOutOfChina(float $lat, float $lng): bool
    {
        return ($lng < (float) self::CHINA_MIN_LNG || $lng > (float) self::CHINA_MAX_LNG)
            || ($lat < (float) self::CHINA_MIN_LAT || $lat > (float) self::CHINA_MAX_LAT);
    }

    /**
     * 转换X坐标的辅助函数
     * 
     * @param float $x X坐标偏移量
     * @param float $y Y坐标偏移量
     * @return float 转换后的纬度偏移量
     */
    private function transformLat(float $x, float $y): float
    {
        $ret = -100.0 + 2.0 * $x + 3.0 * $y + 0.2 * $y * $y + 0.1 * $x * $y + 0.2 * sqrt(abs($x));
        $ret += (20.0 * sin(6.0 * $x * M_PI) + 20.0 * sin(2.0 * $x * M_PI)) * 2.0 / 3.0;
        $ret += (20.0 * sin($y * M_PI) + 40.0 * sin($y / 3.0 * M_PI)) * 2.0 / 3.0;
        $ret += (160.0 * sin($y / 12.0 * M_PI) + 320 * sin($y * M_PI / 30.0)) * 2.0 / 3.0;
        return $ret;
    }

    /**
     * 转换Y坐标的辅助函数
     * 
     * @param float $x X坐标偏移量
     * @param float $y Y坐标偏移量
     * @return float 转换后的经度偏移量
     */
    private function transformLng(float $x, float $y): float
    {
        $ret = 300.0 + $x + 2.0 * $y + 0.1 * $x * $x + 0.1 * $x * $y + 0.1 * sqrt(abs($x));
        $ret += (20.0 * sin(6.0 * $x * M_PI) + 20.0 * sin(2.0 * $x * M_PI)) * 2.0 / 3.0;
        $ret += (20.0 * sin($x * M_PI) + 40.0 * sin($x / 3.0 * M_PI)) * 2.0 / 3.0;
        $ret += (150.0 * sin($x / 12.0 * M_PI) + 300.0 * sin($x / 30.0 * M_PI)) * 2.0 / 3.0;
        return $ret;
    }

    /**
     * WGS84坐标转换为GCJ02坐标
     * 
     * @param string|float $wgsLat WGS84纬度
     * @param string|float $wgsLng WGS84经度
     * @return array 统一格式响应 [ret, msg, data]
     */
    public function wgs84ToGcj02($wgsLat, $wgsLng): array
    {
        return $this->executeRequest(function () use ($wgsLat, $wgsLng) {
            $this->validateCoordinate($wgsLat, $wgsLng);

            $latFloat = (float) $wgsLat;
            $lngFloat = (float) $wgsLng;

            if ($this->isOutOfChina($latFloat, $lngFloat)) {
                return $this->formatCoordinateResponse(
                    $latFloat,
                    $lngFloat,
                    'WGS84',
                    'GCJ02',
                    '坐标不在中国范围内，无需转换'
                );
            }

            $result = $this->performWgs84ToGcj02Conversion($latFloat, $lngFloat);

            return $this->formatCoordinateResponse(
                $result['lat'],
                $result['lng'],
                'WGS84',
                'GCJ02'
            );
        }, 'WGS84坐标转GCJ02成功');
    }

    /**
     * GCJ02坐标转换为WGS84坐标（精确反算）
     * 
     * @param string|float $gcjLat GCJ02纬度
     * @param string|float $gcjLng GCJ02经度
     * @return array 统一格式响应 [ret, msg, data]
     */
    public function gcj02ToWgs84($gcjLat, $gcjLng): array
    {
        return $this->executeRequest(function () use ($gcjLat, $gcjLng) {
            $this->validateCoordinate($gcjLat, $gcjLng);

            $latFloat = (float) $gcjLat;
            $lngFloat = (float) $gcjLng;

            if ($this->isOutOfChina($latFloat, $lngFloat)) {
                return $this->formatCoordinateResponse(
                    $latFloat,
                    $lngFloat,
                    'GCJ02',
                    'WGS84',
                    '坐标不在中国范围内，无需转换'
                );
            }

            $result = $this->performGcj02ToWgs84Conversion($latFloat, $lngFloat);

            return $this->formatCoordinateResponse(
                $result['lat'],
                $result['lng'],
                'GCJ02',
                'WGS84'
            );
        }, 'GCJ02坐标转WGS84成功');
    }

    /**
     * GCJ02坐标转换为BD09坐标
     * 
     * @param string|float $gcjLat GCJ02纬度
     * @param string|float $gcjLng GCJ02经度
     * @return array 统一格式响应 [ret, msg, data]
     */
    public function gcj02ToBd09($gcjLat, $gcjLng): array
    {
        return $this->executeRequest(function () use ($gcjLat, $gcjLng) {
            $result = $this->performGcj02ToBd09Conversion($gcjLat, $gcjLng);

            return $this->formatCoordinateResponse(
                $result['lat'],
                $result['lng'],
                'GCJ02',
                'BD09'
            );
        }, 'GCJ02坐标转BD09成功');
    }

    /**
     * BD09坐标转换为GCJ02坐标
     * 
     * @param string|float $bdLat BD09纬度
     * @param string|float $bdLng BD09经度
     * @return array 统一格式响应 [ret, msg, data]
     */
    public function bd09ToGcj02($bdLat, $bdLng): array
    {
        return $this->executeRequest(function () use ($bdLat, $bdLng) {
            $result = $this->performBd09ToGcj02Conversion($bdLat, $bdLng);

            return $this->formatCoordinateResponse(
                $result['lat'],
                $result['lng'],
                'BD09',
                'GCJ02'
            );
        }, 'BD09坐标转GCJ02成功');
    }

    /**
     * WGS84坐标转换为BD09坐标
     * 
     * @param string|float $wgsLat WGS84纬度
     * @param string|float $wgsLng WGS84经度
     * @return array 统一格式响应 [ret, msg, data]
     */
    public function wgs84ToBd09($wgsLat, $wgsLng): array
    {
        return $this->executeRequest(function () use ($wgsLat, $wgsLng) {
            $this->validateCoordinate($wgsLat, $wgsLng);

            $latFloat = (float) $wgsLat;
            $lngFloat = (float) $wgsLng;

            if ($this->isOutOfChina($latFloat, $lngFloat)) {
                return $this->formatCoordinateResponse(
                    $latFloat,
                    $lngFloat,
                    'WGS84',
                    'BD09',
                    '坐标不在中国范围内，无需转换'
                );
            }

            $gcj = $this->performWgs84ToGcj02Conversion($latFloat, $lngFloat);
            $result = $this->performGcj02ToBd09Conversion($gcj['lat'], $gcj['lng']);

            return $this->formatCoordinateResponse(
                $result['lat'],
                $result['lng'],
                'WGS84',
                'BD09'
            );
        }, 'WGS84坐标转BD09成功');
    }

    /**
     * BD09坐标转换为WGS84坐标
     * 
     * @param string|float $bdLat BD09纬度
     * @param string|float $bdLng BD09经度
     * @return array 统一格式响应 [ret, msg, data]
     */
    public function bd09ToWgs84($bdLat, $bdLng): array
    {
        return $this->executeRequest(function () use ($bdLat, $bdLng) {
            $gcj = $this->performBd09ToGcj02Conversion($bdLat, $bdLng);
            $result = $this->performGcj02ToWgs84Conversion($gcj['lat'], $gcj['lng']);

            return $this->formatCoordinateResponse(
                $result['lat'],
                $result['lng'],
                'BD09',
                'WGS84'
            );
        }, 'BD09坐标转WGS84成功');
    }

    /**
     * 批量坐标转换
     * 
     * @param array $coordinates 坐标列表，每项包含 ['lat' => float, 'lng' => float]
     * @param string $sourceSystem 源坐标系 (WGS84|GCJ02|BD09)
     * @param string $targetSystem 目标坐标系 (WGS84|GCJ02|BD09)
     * @return array 统一格式响应 [ret, msg, data]
     */
    public function batchConvert(array $coordinates, string $sourceSystem, string $targetSystem): array
    {
        return $this->executeRequest(function () use ($coordinates, $sourceSystem, $targetSystem) {
            if (empty($coordinates)) {
                throw new TianDiTuException('坐标列表不能为空');
            }

            if (count($coordinates) > 100) {
                throw new TianDiTuException('单次批量转换最多支持100个坐标点');
            }

            $sourceSystem = strtoupper($sourceSystem);
            $targetSystem = strtoupper($targetSystem);

            $supportedSystems = ['WGS84', 'GCJ02', 'BD09'];
            if (!in_array($sourceSystem, $supportedSystems) || !in_array($targetSystem, $supportedSystems)) {
                throw new TianDiTuException('不支持的坐标系类型，支持: WGS84, GCJ02, BD09');
            }

            if ($sourceSystem === $targetSystem) {
                throw new TianDiTuException('源坐标系与目标坐标系不能相同');
            }

            $results = [];
            foreach ($coordinates as $index => $coordinate) {
                if (!isset($coordinate['lat']) || !isset($coordinate['lng'])) {
                    throw new TianDiTuException("坐标点 {$index} 缺少必要的 lat 或 lng 字段");
                }

                $result = $this->performCoordinateConversion(
                    $coordinate['lat'],
                    $coordinate['lng'],
                    $sourceSystem,
                    $targetSystem
                );

                $results[] = [
                    'index' => $index,
                    'source_location' => [
                        'lat' => (float) $coordinate['lat'],
                        'lng' => (float) $coordinate['lng']
                    ],
                    'converted_location' => [
                        'lat' => $result['lat'],
                        'lng' => $result['lng']
                    ]
                ];
            }

            return [
                'source_coordinate_system' => $sourceSystem,
                'target_coordinate_system' => $targetSystem,
                'total_count' => count($results),
                'coordinates' => $results
            ];
        }, "批量坐标转换成功，{$sourceSystem} -> {$targetSystem}");
    }

    /**
     * 智能坐标转换（自动检测最优转换路径）
     * 
     * @param string|float $lat 纬度
     * @param string|float $lng 经度
     * @param string $sourceSystem 源坐标系
     * @param string $targetSystem 目标坐标系
     * @return array 统一格式响应 [ret, msg, data]
     */
    public function convert($lat, $lng, string $sourceSystem, string $targetSystem): array
    {
        return $this->executeRequest(function () use ($lat, $lng, $sourceSystem, $targetSystem) {
            $result = $this->performCoordinateConversion($lat, $lng, $sourceSystem, $targetSystem);

            return $this->formatCoordinateResponse(
                $result['lat'],
                $result['lng'],
                strtoupper($sourceSystem),
                strtoupper($targetSystem)
            );
        }, "坐标转换成功，{$sourceSystem} -> {$targetSystem}");
    }

    /**
     * 格式化坐标转换响应
     * 
     * @param float $lat 转换后的纬度
     * @param float $lng 转换后的经度
     * @param string $sourceCoordSystem 源坐标系
     * @param string $targetCoordSystem 目标坐标系
     * @param string|null $note 备注信息
     * @return array 格式化后的响应数据
     */
    private function formatCoordinateResponse(
        float $lat,
        float $lng,
        string $sourceCoordSystem,
        string $targetCoordSystem,
        ?string $note = null
    ): array {
        $result = [
            'source_coordinate_system' => $sourceCoordSystem,
            'target_coordinate_system' => $targetCoordSystem,
            'location' => [
                'lat' => $lat,
                'lng' => $lng
            ]
        ];

        if ($note !== null) {
            $result['note'] = $note;
        }

        return $result;
    }

    /**
     * 执行WGS84到GCJ02的实际转换
     * 
     * @param float $wgsLat WGS84纬度
     * @param float $wgsLng WGS84经度
     * @return array{lat: float, lng: float}
     */
    private function performWgs84ToGcj02Conversion(float $wgsLat, float $wgsLng): array
    {
        $dLat = $this->transformLat($wgsLng - 105.0, $wgsLat - 35.0);
        $dLng = $this->transformLng($wgsLng - 105.0, $wgsLat - 35.0);
        $radLat = $wgsLat / 180.0 * M_PI;
        $magic = sin($radLat);
        $magic = 1 - (float) self::ECCENTRICITY_SQUARED * $magic * $magic;
        $sqrtMagic = sqrt($magic);
        $dLat = ($dLat * 180.0) / ((self::EARTH_RADIUS * (1 - (float) self::ECCENTRICITY_SQUARED)) / ($magic * $sqrtMagic) * M_PI);
        $dLng = ($dLng * 180.0) / (self::EARTH_RADIUS / $sqrtMagic * cos($radLat) * M_PI);

        $mgLat = $wgsLat + $dLat;
        $mgLng = $wgsLng + $dLng;

        return ['lat' => $mgLat, 'lng' => $mgLng];
    }

    /**
     * 执行GCJ02到WGS84的实际转换
     * 
     * @param float $gcjLat GCJ02纬度
     * @param float $gcjLng GCJ02经度
     * @return array{lat: float, lng: float}
     */
    private function performGcj02ToWgs84Conversion(float $gcjLat, float $gcjLng): array
    {
        if ($this->isOutOfChina($gcjLat, $gcjLng)) {
            return ['lat' => $gcjLat, 'lng' => $gcjLng];
        }

        // 迭代求解，提高反算精度
        $initDelta = 0.01;
        $threshold = (float) self::ITERATION_THRESHOLD;

        $dLat = $initDelta;
        $dLng = $initDelta;
        $mLat = $gcjLat - $dLat;
        $mLng = $gcjLng - $dLng;

        $wgsLat = 0.0;
        $wgsLng = 0.0;
        $i = 0;

        while (true) {
            $tmp = $this->performWgs84ToGcj02Conversion($mLat, $mLng);
            $dLat = $tmp['lat'] - $gcjLat;
            $dLng = $tmp['lng'] - $gcjLng;

            if ((abs($dLat) < $threshold) && (abs($dLng) < $threshold)) {
                $wgsLat = $mLat;
                $wgsLng = $mLng;
                break;
            }

            $mLat -= $dLat;
            $mLng -= $dLng;

            if (++$i > self::MAX_ITERATIONS) {
                throw new TianDiTuException('坐标转换迭代超过最大次数');
            }
        }

        return ['lat' => $wgsLat, 'lng' => $wgsLng];
    }

    /**
     * 执行GCJ02到BD09的实际转换
     * 
     * @param string|float $gcjLat GCJ02纬度
     * @param string|float $gcjLng GCJ02经度
     * @return array{lat: float, lng: float}
     */
    private function performGcj02ToBd09Conversion($gcjLat, $gcjLng): array
    {
        $this->validateCoordinate($gcjLat, $gcjLng);

        $x = (float) $gcjLng;
        $y = (float) $gcjLat;
        $z = sqrt($x * $x + $y * $y) + 0.00002 * sin($y * M_PI * (float) self::BD09_FACTOR / 180.0);
        $theta = atan2($y, $x) + 0.000003 * cos($x * M_PI * (float) self::BD09_FACTOR / 180.0);
        $bdLng = $z * cos($theta) + (float) self::BD09_OFFSET_X;
        $bdLat = $z * sin($theta) + (float) self::BD09_OFFSET_Y;

        return ['lat' => $bdLat, 'lng' => $bdLng];
    }

    /**
     * 执行BD09到GCJ02的实际转换
     * 
     * @param string|float $bdLat BD09纬度
     * @param string|float $bdLng BD09经度
     * @return array{lat: float, lng: float}
     */
    private function performBd09ToGcj02Conversion($bdLat, $bdLng): array
    {
        $this->validateCoordinate($bdLat, $bdLng);

        $x = (float) $bdLng - (float) self::BD09_OFFSET_X;
        $y = (float) $bdLat - (float) self::BD09_OFFSET_Y;
        $z = sqrt($x * $x + $y * $y) - 0.00002 * sin($y * M_PI * (float) self::BD09_FACTOR / 180.0);
        $theta = atan2($y, $x) - 0.000003 * cos($x * M_PI * (float) self::BD09_FACTOR / 180.0);
        $gcjLng = $z * cos($theta);
        $gcjLat = $z * sin($theta);

        return ['lat' => $gcjLat, 'lng' => $gcjLng];
    }

    /**
     * 核心坐标转换调度方法
     * 
     * @param string|float $lat 纬度
     * @param string|float $lng 经度
     * @param string $sourceSystem 源坐标系
     * @param string $targetSystem 目标坐标系
     * @return array{lat: float, lng: float}
     * @throws TianDiTuException
     */
    private function performCoordinateConversion($lat, $lng, string $sourceSystem, string $targetSystem): array
    {
        $this->validateCoordinate($lat, $lng);

        $sourceSystem = strtoupper($sourceSystem);
        $targetSystem = strtoupper($targetSystem);

        $supportedSystems = ['WGS84', 'GCJ02', 'BD09'];
        if (!in_array($sourceSystem, $supportedSystems) || !in_array($targetSystem, $supportedSystems)) {
            throw new TianDiTuException('不支持的坐标系类型，支持: WGS84, GCJ02, BD09');
        }

        if ($sourceSystem === $targetSystem) {
            return ['lat' => (float) $lat, 'lng' => (float) $lng];
        }

        $latFloat = (float) $lat;
        $lngFloat = (float) $lng;

        // 直接转换映射表
        $conversionMap = [
            'WGS84_GCJ02' => [$this, 'performWgs84ToGcj02Conversion'],
            'GCJ02_WGS84' => [$this, 'performGcj02ToWgs84Conversion'],
            'GCJ02_BD09' => [$this, 'performGcj02ToBd09Conversion'],
            'BD09_GCJ02' => [$this, 'performBd09ToGcj02Conversion'],
        ];

        $conversionKey = "{$sourceSystem}_{$targetSystem}";

        // 如果有直接转换方法，使用直接转换
        if (isset($conversionMap[$conversionKey])) {
            return call_user_func($conversionMap[$conversionKey], $latFloat, $lngFloat);
        }

        // 需要间接转换的情况
        switch ($conversionKey) {
            case 'WGS84_BD09':
                $gcj02 = $this->performWgs84ToGcj02Conversion($latFloat, $lngFloat);
                return $this->performGcj02ToBd09Conversion($gcj02['lat'], $gcj02['lng']);

            case 'BD09_WGS84':
                $gcj02 = $this->performBd09ToGcj02Conversion($latFloat, $lngFloat);
                return $this->performGcj02ToWgs84Conversion($gcj02['lat'], $gcj02['lng']);

            default:
                throw new TianDiTuException("不支持的坐标转换: {$sourceSystem} -> {$targetSystem}");
        }
    }
}
