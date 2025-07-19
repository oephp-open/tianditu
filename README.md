# 天地图 PHP SDK

[![PHP Version](https://img.shiel# 地理编码 - 地址转坐标
$result = $client->geocoding()->search('北京市海淀区中关村');
echo "坐标: " . $result['location']['lon'] . ", " . $result['location']['lat'];

# 统一格式版本 (推荐)
$result = $client->geocoding()->searchWithFormat('北京市海淀区中关村');
if ($result['ret'] === 1) {
    echo "坐标: " . $result['data']['location']['lon'] . ", " . $result['data']['location']['lat'];
} else {
    echo "错误: " . $result['msg'];
}io/badge/php-%3E%3D7.2-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Latest Version](https://img.shields.io/badge/version-1.1-orange.svg)](https://github.com/oephp-open/tianditu)

一个用于天地图（TianDiTu）Web服务API的PHP SDK，提供地理编码、逆地理编码、POI搜索等功能。

## 功能特性

- 🌍 **地理编码** - 将地址转换为地理坐标
- 🌍 **逆地理编码** - 将地理坐标转换为地址信息
- 🔍 **POI搜索** - 普通搜索、周边搜索、视野内搜索
- 🏛️ **行政区域搜索** - 指定行政区内的POI搜索
- 🚗 **路径规划** - 驾车、步行、公交路径规划
- 🔄 **坐标转换** - 多种坐标系统间的相互转换
- ⚡ **高性能** - 基于Guzzle HTTP客户端
- 🛡️ **异常处理** - 完善的错误处理机制
- 📦 **PSR标准** - 遵循PSR-4自动加载和PSR-12编码规范
- 🎯 **统一格式** - 提供统一的返回格式 `[ret, msg, data]`
- 🧪 **完整测试** - 提供单元测试和集成测试

## 安装

使用 Composer 安装：

```bash
composer require oephp-open/tianditu
```

## 快速开始

### 1. 获取API密钥

访问 [天地图开放平台](https://console.tianditu.gov.cn/) 申请API密钥。

### 2. 基本使用

```php
<?php

require_once 'vendor/autoload.php';

use OephpOpen\TianDiTu\TianDiTuClient;

// 初始化客户端
$client = new TianDiTuClient('your-api-key');

// 地理编码 - 地址转坐标
$result = $client->geocoding()->search('北京市海淀区中关村');
echo "坐标: " . $result['location']['lon'] . ", " . $result['location']['lat'];

// 逆地理编码 - 坐标转地址
$result = $client->reverseGeocoding()->search(116.3974, 39.9093);
echo "地址: " . $result['formatted_address'];

// POI搜索
$result = $client->poi()->search('星巴克', [
    'mapBound' => '116.2,39.8,116.6,40.1',
    'count' => '10'
]);
echo "找到 " . count($result['pois']) . " 个POI";
```

## 详细使用说明

### 地理编码服务

将地址转换为地理坐标：

```php
// 基本地理编码
$result = $client->geocoding()->search('北京大学');

// 批量地理编码
$addresses = ['北京大学', '清华大学', '中科院'];
$results = $client->geocoding()->batchSearch($addresses);
```

### 逆地理编码服务

将坐标转换为地址信息：

```php
// 基本逆地理编码
$result = $client->reverseGeocoding()->search(116.3974, 39.9093);

// 批量逆地理编码
$coordinates = [
    [116.3974, 39.9093],
    [121.4737, 31.2304]
];
$results = $client->reverseGeocoding()->batchSearch($coordinates);
```

### POI搜索服务

#### 普通搜索

```php
$result = $client->poi()->search('星巴克', [
    'mapBound' => '116.2,39.8,116.6,40.1', // 搜索范围
    'count' => '10', // 返回数量
    'start' => '0'   // 起始位置
]);
```

#### 周边搜索

```php
$result = $client->poi()->searchNearby(
    '餐厅',        // 搜索关键词
    116.3974,     // 中心点经度
    39.9093,      // 中心点纬度
    1000,         // 搜索半径（米）
    ['count' => '5']
);
```

#### 视野内搜索

```php
$result = $client->poi()->searchInBounds(
    '学校',                    // 搜索关键词
    116.2, 39.8,              // 西南角坐标
    116.6, 40.1,              // 东北角坐标
    ['count' => '10']
);
```

#### 行政区域搜索

```php
$result = $client->poi()->searchInAdmin(
    '医院',        // 搜索关键词
    '156110000',   // 行政区编码（北京市）
    ['count' => '10']
);
```

## 异常处理

SDK提供三种类型的异常：

```php
use OephpOpen\TianDiTu\Exceptions\TianDiTuException;
use OephpOpen\TianDiTu\Exceptions\ApiException;
use OephpOpen\TianDiTu\Exceptions\NetworkException;

try {
    $result = $client->geocoding()->search('');
} catch (TianDiTuException $e) {
    // 参数验证错误
    echo "参数错误: " . $e->getMessage();
} catch (ApiException $e) {
    // API响应错误
    echo "API错误: " . $e->getMessage();
} catch (NetworkException $e) {
    // 网络错误
    echo "网络错误: " . $e->getMessage();
}
```

## 配置选项

### 自定义HTTP客户端

```php
use GuzzleHttp\Client;

$httpClient = new Client([
    'timeout' => 30,
    'connect_timeout' => 10
]);

$client = new TianDiTuClient('your-api-key', [
    'http_client' => $httpClient
]);
```

### 设置基础URL

```php
$client = new TianDiTuClient('your-api-key');
$client->setBaseUrl('https://api.tianditu.gov.cn');
```

## 系统要求

- PHP >= 7.2
- Guzzle HTTP 客户端 (^6.5 || ^7.0)
- JSON 扩展

## 开发

### 安装开发依赖

```bash
composer install --dev
```

### 运行测试

```bash
vendor/bin/phpunit
```

### 代码风格检查

```bash
vendor/bin/phpcs
```

## 更新日志

### v1.1 (2024-07-19)

- 🎯 **统一返回格式** - 新增 `WithFormat` 方法，提供统一的 `[ret, msg, data]` 返回格式
- ✨ **错误信息优化** - 智能提取 API 错误信息，返回简洁明了的错误描述
- 🛡️ **增强异常处理** - 自动捕获和格式化所有异常，无需手动 try-catch
- 📚 **向后兼容** - 保留所有原有方法，确保现有代码正常运行
- 🔧 **新增方法** - 所有服务类都新增了对应的 `WithFormat` 方法
- 📖 **完善文档** - 新增统一格式使用文档和示例

### v1.0.0 (2024-07-18)

- 🎉 初始版本发布
- ✅ 地理编码和逆地理编码服务
- ✅ POI搜索服务（普通、周边、视野内、行政区域）
- ✅ 完善的异常处理机制
- ✅ PSR-4自动加载和PSR-12编码规范
- ✅ 完整的单元测试和文档

## 许可证

本项目基于 MIT 许可证开源。查看 [LICENSE](LICENSE) 文件了解详细信息。

## 支持

如果您在使用过程中遇到问题：

1. 查看文档和示例代码
2. 提交 [Issue](https://github.com/oephp-open/tianditu/issues)
3. 参考天地图官方API文档

## 致谢

感谢 [天地图](https://www.tianditu.gov.cn/) 提供优秀的地理信息服务API。
