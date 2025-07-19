# 天地图 PHP SDK

[![PHP Version](https://img.shields.io/badge/php-%3E%3D7.2-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Latest Version](https://img.shields.io/badge/version-1.1.2-orange.svg)](https://github.com/oephp-open/tianditu)

一个用于天地图（TianDiTu）Web服务API的PHP SDK，提供地理编码、逆地理编码、POI搜索、路径规划、坐标转换等功能。

## 功能特性

- 🌍 **地理编码** - 将地址转换为地理坐标
- 🌍 **逆地理编码** - 将地理坐标转换为地址信息
- 🔍 **POI搜索** - 普通搜索、周边搜索、视野内搜索、行政区域搜索
- 🚗 **路径规划** - 驾车、步行、公交路径规划
- 🔄 **坐标转换** - 多种坐标系统间的相互转换
- ⚡ **高性能** - 基于Guzzle HTTP客户端
- 🛡️ **异常处理** - 完善的错误处理机制
- 📦 **PSR标准** - 遵循PSR-4自动加载和PSR-12编码规范
- 🎯 **统一格式** - 所有方法返回统一格式 `[ret, msg, data]`

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
if ($result['ret'] === 1) {
    $location = $result['data']['location'];
    echo "坐标: 经度 {$location['lon']}, 纬度 {$location['lat']}";
} else {
    echo "错误: " . $result['msg'];
}
```

## 统一返回格式

SDK 2.0 版本统一了所有方法的返回格式，提供更好的开发体验：

```php
[
    'ret' => 1|0,           // 1: 成功, 0: 失败
    'msg' => '消息内容',     // 成功或失败的描述信息  
    'data' => mixed         // 具体的业务数据或null
]
```

### 成功示例
```php
[
    'ret' => 1,
    'msg' => '地理编码查询成功',
    'data' => [
        'formatted_address' => '北京市海淀区中关村',
        'location' => [
            'lon' => 116.31471,
            'lat' => 39.96663
        ],
        'level' => '道路',
        'score' => 95
    ]
]
```

### 失败示例
```php
[
    'ret' => 0,
    'msg' => 'Missing required parameter: address',
    'data' => null
]
```

## API 接口详解

### 地理编码服务

地理编码服务将地址描述转换为地理坐标。

```php
// 单个地址查询
$result = $client->geocoding()->search('北京市海淀区中关村');

// 批量地址查询（最多10个）
$addresses = ['北京市', '上海市', '广州市'];
$result = $client->geocoding()->batchSearch($addresses);
```

**参数说明：**
- `$address` (string): 要查询的地址描述
- `$addresses` (array): 地址列表，最多10个
- `$options` (array): 可选参数

**返回数据结构：**
```php
[
    'formatted_address' => '格式化地址',
    'location' => [
        'lon' => 116.31471,  // 经度
        'lat' => 39.96663    // 纬度
    ],
    'level' => '精确度级别',
    'score' => 95  // 匹配分数
]
```

### 逆地理编码服务

逆地理编码服务将地理坐标转换为地址信息。

```php
// 单个坐标查询
$result = $client->reverseGeocoding()->search(116.3974, 39.9093);

// 批量坐标查询（最多100个）
$coordinates = [
    ['lon' => 116.3974, 'lat' => 39.9093],
    ['lon' => 121.4737, 'lat' => 31.2304]
];
$result = $client->reverseGeocoding()->batchSearch($coordinates);
```

**参数说明：**
- `$lon` (float): 经度
- `$lat` (float): 纬度
- `$coordinates` (array): 坐标列表，格式 `[['lon' => float, 'lat' => float], ...]`
- `$options` (array): 可选参数

**返回数据结构：**
```php
[
    'formatted_address' => '北京市海淀区中关村街道',
    'address_component' => [
        'address' => '具体地址',
        'city' => '北京市',
        'county' => '海淀区',
        'province' => '北京市',
        'road' => '中关村大街',
        // ... 更多地址组件
    ]
]
```

### POI搜索服务

POI（Point of Interest）搜索服务提供兴趣点搜索功能。

```php
// 普通搜索
$result = $client->poi()->search('餐厅');

// 周边搜索
$result = $client->poi()->searchNearby('银行', 116.3974, 39.9093, 1000);

// 视野内搜索
$result = $client->poi()->searchInBounds('酒店', 116.3, 39.8, 116.5, 40.0);

// 行政区域搜索
$result = $client->poi()->searchInAdmin('学校', '110000');
```

**方法说明：**

#### search() - 普通搜索
- `$keyword` (string): 搜索关键词
- `$options` (array): 可选参数
  - `level`: 搜索级别 (默认: '12')
  - `start`: 起始索引 (默认: '0')
  - `count`: 返回数量 (默认: '10')

#### searchNearby() - 周边搜索
- `$keyword` (string): 搜索关键词
- `$lon` (float): 中心点经度
- `$lat` (float): 中心点纬度
- `$radius` (int): 搜索半径（米，默认: 1000）
- `$options` (array): 可选参数

#### searchInBounds() - 视野内搜索
- `$keyword` (string): 搜索关键词
- `$minLon` (float): 西南角经度
- `$minLat` (float): 西南角纬度
- `$maxLon` (float): 东北角经度
- `$maxLat` (float): 东北角纬度
- `$options` (array): 可选参数

#### searchInAdmin() - 行政区域搜索
- `$keyword` (string): 搜索关键词
- `$adminCode` (string): 行政区编码
- `$options` (array): 可选参数

**返回数据结构：**
```php
[
    'total' => 100,  // 总数量
    'pois' => [
        [
            'name' => 'POI名称',
            'address' => '详细地址',
            'location' => [
                'lon' => 116.3974,
                'lat' => 39.9093
            ],
            'category' => '类别',
            'phone' => '电话号码'
        ],
        // ... 更多POI
    ]
]
```

### 路径规划服务

路径规划服务提供驾车、步行、公交路径规划功能。

```php
// 驾车路径规划
$result = $client->route()->driving(116.3974, 39.9093, 116.4074, 39.9193);

// 步行路径规划
$result = $client->route()->walking(116.3974, 39.9093, 116.4074, 39.9193);

// 公交路径规划
$result = $client->route()->transit('中关村', '天安门', '北京市');
```

**方法说明：**

#### driving() / walking() - 驾车/步行路径规划
- `$startLon` (float): 起点经度
- `$startLat` (float): 起点纬度
- `$endLon` (float): 终点经度
- `$endLat` (float): 终点纬度
- `$options` (array): 可选参数

#### transit() - 公交路径规划
- `$startAddress` (string): 起点地址
- `$endAddress` (string): 终点地址
- `$city` (string): 城市名称
- `$options` (array): 可选参数

**返回数据结构：**
```php
[
    'distance' => 15200.5,  // 总距离（米）
    'duration' => 1800,     // 总时长（秒）
    'routes' => [
        [
            'distance' => 15200.5,
            'duration' => 1800,
            'steps' => [
                [
                    'instruction' => '向北行驶100米',
                    'distance' => 100.0,
                    'duration' => 60
                ],
                // ... 更多步骤
            ]
        ]
    ]
]
```

### 坐标转换服务

坐标转换服务提供不同坐标系之间的转换功能。

```php
// 单个坐标转换
$result = $client->coordinate()->transform(116.3974, 39.9093, 1, 2);

// 批量坐标转换（最多100个）
$coordinates = [
    ['lon' => 116.3974, 'lat' => 39.9093],
    ['lon' => 121.4737, 'lat' => 31.2304]
];
$result = $client->coordinate()->batchTransform($coordinates, 1, 2);
```

**参数说明：**
- `$lon` (float): 经度
- `$lat` (float): 纬度
- `$coordinates` (array): 坐标列表
- `$fromType` (int): 源坐标系类型
- `$toType` (int): 目标坐标系类型
- `$options` (array): 可选参数

**坐标系类型：**
- `1`: WGS84坐标系
- `2`: GCJ02坐标系（火星坐标系）
- `3`: BD09坐标系（百度坐标系）

**返回数据结构：**
```php
// 单个坐标转换
[
    'lon' => 116.40395,
    'lat' => 39.91640
]

// 批量坐标转换
[
    ['lon' => 116.40395, 'lat' => 39.91640],
    ['lon' => 121.48654, 'lat' => 31.24527],
    // ... 更多坐标
]
```

## 错误处理

SDK 自动处理所有异常并转换为统一格式返回，无需手动 try-catch：

```php
// 自动异常处理
$result = $client->geocoding()->search(''); // 传入空地址

// 返回格式：
// [
//     'ret' => 0,
//     'msg' => 'Missing required parameter: address',
//     'data' => null
// ]

// 业务逻辑处理
if ($result['ret'] === 1) {
    // 成功处理
    $data = $result['data'];
} else {
    // 错误处理
    echo "操作失败: " . $result['msg'];
}
```

## 完整示例

```php
<?php
require_once 'vendor/autoload.php';

use OephpOpen\TianDiTu\TianDiTuClient;

$client = new TianDiTuClient('your-api-key');

// 1. 地理编码查询
echo "=== 地理编码查询 ===\n";
$result = $client->geocoding()->search('北京市海淀区中关村');
if ($result['ret'] === 1) {
    $data = $result['data'];
    echo "地址: {$data['formatted_address']}\n";
    echo "坐标: 经度 {$data['location']['lon']}, 纬度 {$data['location']['lat']}\n";
} else {
    echo "查询失败: {$result['msg']}\n";
}

// 2. POI搜索
echo "\n=== POI搜索 ===\n";
$result = $client->poi()->searchNearby('餐厅', 116.3974, 39.9093, 1000);
if ($result['ret'] === 1) {
    $data = $result['data'];
    echo "找到 {$data['total']} 个餐厅:\n";
    foreach (array_slice($data['pois'], 0, 3) as $poi) {
        echo "- {$poi['name']}: {$poi['address']}\n";
    }
} else {
    echo "搜索失败: {$result['msg']}\n";
}

// 3. 路径规划
echo "\n=== 路径规划 ===\n";
$result = $client->route()->driving(116.3974, 39.9093, 116.4074, 39.9193);
if ($result['ret'] === 1) {
    $data = $result['data'];
    echo "距离: " . round($data['distance'] / 1000, 2) . " 公里\n";
    echo "时长: " . round($data['duration'] / 60, 1) . " 分钟\n";
} else {
    echo "规划失败: {$result['msg']}\n";
}
```

## 系统要求

- PHP >= 7.2
- ext-json
- ext-curl
- Guzzle HTTP >= 6.0

## 开发调试

### 运行测试

```bash
# 运行所有测试
composer test

# 或直接使用 PHP
php tests/refactored_test.php
```

### 错误排查

1. **API密钥错误**
   - 检查密钥是否正确
   - 确认密钥权限是否包含所需服务

2. **网络连接问题**
   - 检查网络连接
   - 确认防火墙设置

3. **参数错误**
   - 检查必需参数是否提供
   - 确认参数格式是否正确

## 更新日志

### v1.1.2 (2025-07-19)

- 🎯 **重大重构** - 移除所有 `WithFormat` 方法，原方法直接返回统一格式
- ✨ **API简化** - 外部调用更加简洁，无需区分不同的方法名
- 🛡️ **增强异常处理** - 自动捕获和格式化所有异常
- 📚 **完全向后兼容** - 保持所有原有方法名，只是返回格式统一
- 📖 **文档整合** - 将所有文档整合到 README.md 中
- 🗂️ **项目结构优化** - 删除 docs 目录，简化项目结构

### v1.1 (2024-07-19)

- 🎯 **统一返回格式** - 新增 `WithFormat` 方法，提供统一的 `[ret, msg, data]` 返回格式
- ✨ **错误信息优化** - 智能提取 API 错误信息，返回简洁明了的错误描述
- 🛡️ **增强异常处理** - 自动捕获和格式化所有异常
- 📚 **向后兼容** - 保留所有原有方法，确保现有代码正常运行

## 许可证

本项目使用 [MIT 许可证](LICENSE)。

## 贡献

欢迎提交 Pull Request 或报告 Issue！

## 支持

如果您在使用过程中遇到问题，请：

1. 查看本文档的错误排查部分
2. 在 GitHub 上提交 Issue
3. 发送邮件至项目维护者

---

**关键字**: 天地图, TianDiTu, PHP SDK, 地理编码, 逆地理编码, POI搜索, 路径规划, 坐标转换

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
