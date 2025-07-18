<?php

declare(strict_types=1);

namespace OephpOpen\TianDiTu;

use OephpOpen\TianDiTu\Services\GeocodingService;
use OephpOpen\TianDiTu\Services\ReverseGeocodingService;
use OephpOpen\TianDiTu\Services\PoiService;
use OephpOpen\TianDiTu\Services\RouteService;
use OephpOpen\TianDiTu\Services\CoordinateService;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;

/**
 * 天地图 PHP SDK 主客户端类
 */
class TianDiTuClient
{
    /**
     * API 密钥
     *
     * @var string
     */
    private $apiKey;

    /**
     * HTTP 客户端
     *
     * @var ClientInterface
     */
    private $httpClient;

    /**
     * 基础 API URL
     *
     * @var string
     */
    private $baseUrl = 'http://api.tianditu.gov.cn';

    /**
     * 构造函数
     *
     * @param string $apiKey API密钥
     * @param ClientInterface|null $httpClient HTTP客户端
     */
    public function __construct($apiKey, $httpClient = null)
    {
        $this->apiKey = $apiKey;
        $this->httpClient = $httpClient ?: new Client([
            'timeout' => 30,
            'connect_timeout' => 10,
        ]);
    }

    /**
     * 获取地理编码服务
     *
     * @return GeocodingService
     */
    public function geocoding()
    {
        return new GeocodingService($this->httpClient, $this->baseUrl, $this->apiKey);
    }

    /**
     * 获取逆地理编码服务
     *
     * @return ReverseGeocodingService
     */
    public function reverseGeocoding()
    {
        return new ReverseGeocodingService($this->httpClient, $this->baseUrl, $this->apiKey);
    }

    /**
     * 获取POI搜索服务
     *
     * @return PoiService
     */
    public function poi()
    {
        return new PoiService($this->httpClient, $this->baseUrl, $this->apiKey);
    }

    /**
     * 获取路径规划服务
     *
     * @return RouteService
     */
    public function route()
    {
        return new RouteService($this->httpClient, $this->baseUrl, $this->apiKey);
    }

    /**
     * 获取坐标转换服务
     *
     * @return CoordinateService
     */
    public function coordinate()
    {
        return new CoordinateService($this->httpClient, $this->baseUrl, $this->apiKey);
    }

    /**
     * 获取 API 密钥
     *
     * @return string
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * 获取基础 URL
     *
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * 设置基础 URL
     *
     * @param string $baseUrl 基础URL
     * @return self
     */
    public function setBaseUrl($baseUrl)
    {
        $this->baseUrl = $baseUrl;
        return $this;
    }
}
