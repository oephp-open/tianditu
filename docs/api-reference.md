# API 参考

## TianDiTuClient

主客户端类，提供访问各种天地图服务的入口。

### 构造函数

```php
public function __construct(string $apiKey, ?ClientInterface $httpClient = null)
```

**参数:**
- `$apiKey`: 天地图 API 密钥
- `$httpClient`: 可选的 HTTP 客户端实例

### 方法

#### geocoding()

获取地理编码服务实例。

```php
public function geocoding(): GeocodingService
```

#### reverseGeocoding()

获取逆地理编码服务实例。

```php
public function reverseGeocoding(): ReverseGeocodingService
```

#### poi()

获取 POI 搜索服务实例。

```php
public function poi(): PoiService
```

#### route()

获取路径规划服务实例。

```php
public function route(): RouteService
```

#### coordinate()

获取坐标转换服务实例。

```php
public function coordinate(): CoordinateService
```

---

## GeocodingService

地理编码服务，将地址转换为坐标。

### search()

单个地址地理编码。

```php
public function search(string $address, array $options = []): array
```

**参数:**
- `$address`: 地址字符串
- `$options`: 可选参数

**返回值:**
```php
[
    'location' => [
        'lon' => 116.3974,
        'lat' => 39.9093
    ],
    'formatted_address' => '北京市海淀区中关村大街',
    'level' => '道路',
    'confidence' => 80,
    'comprehension' => 100
]
```

### batchSearch()

批量地址地理编码。

```php
public function batchSearch(array $addresses, array $options = []): array
```

**参数:**
- `$addresses`: 地址数组，最多100个
- `$options`: 可选参数

---

## ReverseGeocodingService

逆地理编码服务，将坐标转换为地址。

### search()

单个坐标逆地理编码。

```php
public function search(float $lon, float $lat, array $options = []): array
```

**参数:**
- `$lon`: 经度
- `$lat`: 纬度
- `$options`: 可选参数

**返回值:**
```php
[
    'formatted_address' => '北京市海淀区中关村大街1号',
    'address_component' => [
        'address' => '中关村大街1号',
        'city' => '北京市',
        'county' => '海淀区',
        'province' => '北京市',
        'road' => '中关村大街',
        'poi' => '某个POI',
        'poi_position' => 'near'
    ]
]
```

### batchSearch()

批量坐标逆地理编码。

```php
public function batchSearch(array $coordinates, array $options = []): array
```

**参数:**
- `$coordinates`: 坐标数组，格式：`[['lon' => 116.3974, 'lat' => 39.9093], ...]`

---

## PoiService

POI（兴趣点）搜索服务。

### search()

区域搜索POI。

```php
public function search(string $keyword, string $region = '', array $options = []): array
```

**参数:**
- `$keyword`: 搜索关键词
- `$region`: 搜索区域
- `$options`: 可选参数

**返回值:**
```php
[
    'total' => 10,
    'pois' => [
        [
            'name' => 'POI名称',
            'address' => '详细地址',
            'category' => '分类',
            'location' => [
                'lon' => 116.3974,
                'lat' => 39.9093
            ],
            'phone' => '电话号码',
            'uid' => '唯一标识',
            'cityname' => '城市名称'
        ]
    ]
]
```

### searchNearby()

周边搜索POI。

```php
public function searchNearby(string $keyword, float $lon, float $lat, int $radius = 1000, array $options = []): array
```

**参数:**
- `$keyword`: 搜索关键词
- `$lon`: 中心点经度
- `$lat`: 中心点纬度
- `$radius`: 搜索半径（米），默认1000

### searchInBounds()

矩形区域搜索POI。

```php
public function searchInBounds(string $keyword, float $minLon, float $minLat, float $maxLon, float $maxLat, array $options = []): array
```

**参数:**
- `$keyword`: 搜索关键词
- `$minLon`: 西南角经度
- `$minLat`: 西南角纬度
- `$maxLon`: 东北角经度
- `$maxLat`: 东北角纬度

---

## RouteService

路径规划服务。

### driving()

驾车路径规划。

```php
public function driving(float $startLon, float $startLat, float $endLon, float $endLat, array $options = []): array
```

**参数:**
- `$startLon`: 起点经度
- `$startLat`: 起点纬度
- `$endLon`: 终点经度
- `$endLat`: 终点纬度
- `$options`: 可选参数

**返回值:**
```php
[
    'distance' => 5000,
    'duration' => 1200,
    'routes' => [
        [
            'distance' => 5000,
            'duration' => 1200,
            'steps' => [
                [
                    'instruction' => '向北行驶100米',
                    'distance' => 100,
                    'duration' => 30,
                    'polyline' => '编码的路径点'
                ]
            ],
            'polyline' => '整条路线的编码路径点'
        ]
    ]
]
```

### walking()

步行路径规划。

```php
public function walking(float $startLon, float $startLat, float $endLon, float $endLat, array $options = []): array
```

### transit()

公交路径规划。

```php
public function transit(string $startAddress, string $endAddress, string $city, array $options = []): array
```

**参数:**
- `$startAddress`: 起点地址
- `$endAddress`: 终点地址
- `$city`: 城市名称

---

## CoordinateService

坐标转换服务。

### transform()

单个坐标转换。

```php
public function transform(float $lon, float $lat, int $fromType = 1, int $toType = 2, array $options = []): array
```

**参数:**
- `$lon`: 经度
- `$lat`: 纬度
- `$fromType`: 源坐标系类型
- `$toType`: 目标坐标系类型

**坐标系类型:**
- `1`: WGS84经纬度坐标
- `2`: GCJ02经纬度坐标
- `3`: BD09经纬度坐标
- `4`: BD09米制坐标
- `5`: CGCS2000经纬度坐标
- `6`: CGCS2000米制坐标

### batchTransform()

批量坐标转换。

```php
public function batchTransform(array $coordinates, int $fromType = 1, int $toType = 2, array $options = []): array
```

### getSupportedCoordinateSystems()

获取支持的坐标系类型。

```php
public function getSupportedCoordinateSystems(): array
```

---

## 异常类

### TianDiTuException

基础异常类，所有 SDK 异常的父类。

### ApiException

API 相关异常，继承自 `TianDiTuException`。

常见情况：
- 无效的 API 密钥
- 超出配额限制
- API 服务不可用

### NetworkException

网络相关异常，继承自 `TianDiTuException`。

常见情况：
- 网络连接超时
- DNS 解析失败
- 服务器无响应
