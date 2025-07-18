# 使用指南

## 安装

通过 Composer 安装天地图 PHP SDK：

```bash
composer require yourname/tianditu-php-sdk
```

## 基础用法

### 初始化客户端

```php
<?php

require_once 'vendor/autoload.php';

use Tianditu\TianDiTuClient;

$client = new TianDiTuClient('your-api-key');
```

### API 密钥

在使用前，你需要从[天地图开放平台](http://lbs.tianditu.gov.cn/)获取 API 密钥：

1. 注册账号并登录
2. 创建应用
3. 获取 API Key (tk)

## 服务详解

### 地理编码服务

将地址转换为经纬度坐标：

```php
// 单个地址编码
$result = $client->geocoding()->search('北京市海淀区中关村');

echo "经度: " . $result['location']['lon'] . "\n";
echo "纬度: " . $result['location']['lat'] . "\n";
echo "格式化地址: " . $result['formatted_address'] . "\n";

// 批量地址编码
$addresses = [
    '北京市海淀区中关村',
    '上海市浦东新区陆家嘴',
    '广州市天河区珠江新城'
];

$results = $client->geocoding()->batchSearch($addresses);

foreach ($results as $result) {
    echo "地址: " . $result['formatted_address'] . "\n";
    echo "坐标: " . $result['location']['lon'] . "," . $result['location']['lat'] . "\n\n";
}
```

### 逆地理编码服务

将经纬度坐标转换为地址：

```php
// 单个坐标逆编码
$result = $client->reverseGeocoding()->search(116.3974, 39.9093);

echo "地址: " . $result['formatted_address'] . "\n";
echo "省份: " . $result['address_component']['province'] . "\n";
echo "城市: " . $result['address_component']['city'] . "\n";
echo "区县: " . $result['address_component']['county'] . "\n";

// 批量坐标逆编码
$coordinates = [
    ['lon' => 116.3974, 'lat' => 39.9093],
    ['lon' => 121.4737, 'lat' => 31.2304],
    ['lon' => 113.2644, 'lat' => 23.1291]
];

$results = $client->reverseGeocoding()->batchSearch($coordinates);

foreach ($results as $result) {
    echo "地址: " . $result['formatted_address'] . "\n";
}
```

### POI 搜索服务

搜索兴趣点信息：

```php
// 区域搜索
$result = $client->poi()->search('餐厅', '北京市');

echo "总数: " . $result['total'] . "\n";
foreach ($result['pois'] as $poi) {
    echo "名称: " . $poi['name'] . "\n";
    echo "地址: " . $poi['address'] . "\n";
    echo "经度: " . $poi['location']['lon'] . "\n";
    echo "纬度: " . $poi['location']['lat'] . "\n";
    echo "电话: " . $poi['phone'] . "\n\n";
}

// 周边搜索
$result = $client->poi()->searchNearby('银行', 116.3974, 39.9093, 1000);

// 矩形区域搜索
$result = $client->poi()->searchInBounds(
    '加油站',
    116.3, 39.8,  // 西南角
    116.5, 40.0   // 东北角
);
```

### 路径规划服务

计算路径和导航信息：

```php
// 驾车路径规划
$result = $client->route()->driving(
    116.3974, 39.9093,  // 起点
    116.4074, 39.9193   // 终点
);

echo "总距离: " . $result['distance'] . " 米\n";
echo "总时间: " . $result['duration'] . " 秒\n";

foreach ($result['routes'] as $route) {
    echo "路线距离: " . $route['distance'] . " 米\n";
    echo "路线时间: " . $route['duration'] . " 秒\n";
    
    foreach ($route['steps'] as $step) {
        echo "  - " . $step['instruction'] . "\n";
    }
}

// 步行路径规划
$result = $client->route()->walking(
    116.3974, 39.9093,  // 起点
    116.4074, 39.9193   // 终点
);

// 公交路径规划
$result = $client->route()->transit(
    '中关村',
    '天安门',
    '北京市'
);
```

### 坐标转换服务

不同坐标系之间的转换：

```php
// 单个坐标转换
$result = $client->coordinate()->transform(
    116.3974, 39.9093,
    1, // WGS84经纬度坐标
    2  // GCJ02经纬度坐标
);

echo "转换后经度: " . $result['lon'] . "\n";
echo "转换后纬度: " . $result['lat'] . "\n";

// 批量坐标转换
$coordinates = [
    ['lon' => 116.3974, 'lat' => 39.9093],
    ['lon' => 121.4737, 'lat' => 31.2304]
];

$results = $client->coordinate()->batchTransform($coordinates, 1, 2);

// 查看支持的坐标系
$systems = $client->coordinate()->getSupportedCoordinateSystems();
foreach ($systems as $id => $name) {
    echo "{$id}: {$name}\n";
}
```

## 错误处理

SDK 提供了完善的错误处理机制：

```php
use Tianditu\Exceptions\TianDiTuException;
use Tianditu\Exceptions\ApiException;
use Tianditu\Exceptions\NetworkException;

try {
    $result = $client->geocoding()->search('北京市海淀区中关村');
} catch (ApiException $e) {
    // API 错误（如无效的 API 密钥、超出配额等）
    echo "API 错误: " . $e->getMessage() . "\n";
    echo "错误代码: " . $e->getCode() . "\n";
} catch (NetworkException $e) {
    // 网络错误
    echo "网络错误: " . $e->getMessage() . "\n";
} catch (TianDiTuException $e) {
    // 其他 SDK 错误
    echo "SDK 错误: " . $e->getMessage() . "\n";
}
```

## 高级配置

### 自定义 HTTP 客户端

```php
use GuzzleHttp\Client;

$httpClient = new Client([
    'timeout' => 60,
    'connect_timeout' => 30,
    'headers' => [
        'User-Agent' => 'My-App/1.0'
    ]
]);

$client = new TianDiTuClient('your-api-key', $httpClient);
```

### 设置自定义基础 URL

```php
$client = new TianDiTuClient('your-api-key');
$client->setBaseUrl('https://your-proxy-server.com');
```

## 注意事项

1. **API 限制**: 天地图 API 有调用频率和配额限制，请注意控制调用频率
2. **坐标系**: 注意不同坐标系的使用场景和转换需求
3. **错误处理**: 建议在生产环境中实现完善的错误处理机制
4. **缓存**: 对于不经常变化的数据（如地理编码结果），建议实现缓存机制
