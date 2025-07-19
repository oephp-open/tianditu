# 天地图 PHP SDK - 统一返回格式

本文档介绍天地图 PHP SDK 的统一返回格式改进。

## 统一返回格式说明

为了提供更好的开发体验和错误处理，SDK 现在提供统一的返回格式：

```php
[
    'ret' => 1|0,           // 1: 成功, 0: 失败
    'msg' => '消息内容',     // 成功或失败的描述信息  
    'data' => mixed         // 具体的业务数据或null
]
```

### 字段说明

- **ret**: 操作结果状态码
  - `1`: 操作成功
  - `0`: 操作失败
- **msg**: 描述信息
  - 成功时：显示成功信息，如"地理编码查询成功"
  - 失败时：显示错误信息，如具体的错误描述
- **data**: 业务数据
  - 成功时：包含具体的返回数据
  - 失败时：通常为 `null` 或包含错误相关信息

## 新增方法列表

所有服务类都新增了带 `WithFormat` 后缀的方法，提供统一返回格式：

### 地理编码服务 (GeocodingService)

```php
// 原方法（保持向后兼容）
$result = $client->geocoding()->search('北京市海淀区中关村');

// 统一格式方法
$result = $client->geocoding()->searchWithFormat('北京市海淀区中关村');
$batchResult = $client->geocoding()->batchSearchWithFormat($addresses);
```

### 逆地理编码服务 (ReverseGeocodingService)

```php
// 原方法（保持向后兼容）
$result = $client->reverseGeocoding()->search(116.3974, 39.9093);

// 统一格式方法
$result = $client->reverseGeocoding()->searchWithFormat(116.3974, 39.9093);
$batchResult = $client->reverseGeocoding()->batchSearchWithFormat($coordinates);
```

### POI搜索服务 (PoiService)

```php
// 原方法（保持向后兼容）
$result = $client->poi()->search('餐厅');
$nearbyResult = $client->poi()->searchNearby('银行', 116.3974, 39.9093, 1000);

// 统一格式方法
$result = $client->poi()->searchWithFormat('餐厅');
$nearbyResult = $client->poi()->searchNearbyWithFormat('银行', 116.3974, 39.9093, 1000);
```

### 路径规划服务 (RouteService)

```php
// 原方法（保持向后兼容）
$result = $client->route()->driving(116.3974, 39.9093, 116.4074, 39.9193);

// 统一格式方法
$result = $client->route()->drivingWithFormat(116.3974, 39.9093, 116.4074, 39.9193);
```

### 坐标转换服务 (CoordinateService)

```php
// 原方法（保持向后兼容）
$result = $client->coordinate()->transform(116.3974, 39.9093, 1, 2);

// 统一格式方法
$result = $client->coordinate()->transformWithFormat(116.3974, 39.9093, 1, 2);
```

## 使用示例

### 1. 成功案例

```php
<?php
require_once 'vendor/autoload.php';

use OephpOpen\TianDiTu\TianDiTuClient;

$client = new TianDiTuClient('your-api-key');

// 地理编码查询
$result = $client->geocoding()->searchWithFormat('北京市海淀区中关村');

if ($result['ret'] === 1) {
    echo "✅ 查询成功：{$result['msg']}\n";
    $data = $result['data'];
    echo "地址：{$data['formatted_address']}\n";
    echo "坐标：经度 {$data['location']['lon']}, 纬度 {$data['location']['lat']}\n";
} else {
    echo "❌ 查询失败：{$result['msg']}\n";
}
```

### 2. 错误处理

```php
// POI搜索
$result = $client->poi()->searchWithFormat('');  // 空关键词

if ($result['ret'] === 0) {
    echo "❌ 搜索失败：{$result['msg']}\n";
    // 输出：❌ 搜索失败：Missing required parameter: keyword
}
```

### 3. 批量操作

```php
// 批量地理编码
$addresses = ['北京市', '上海市', '广州市'];
$result = $client->geocoding()->batchSearchWithFormat($addresses);

if ($result['ret'] === 1) {
    echo "✅ 批量查询成功：{$result['msg']}\n";
    foreach ($result['data'] as $index => $item) {
        echo "地址 {$addresses[$index]}: 经度 {$item['location']['lon']}, 纬度 {$item['location']['lat']}\n";
    }
} else {
    echo "❌ 批量查询失败：{$result['msg']}\n";
}
```

## 异常处理

统一格式方法会自动捕获所有异常并转换为统一格式返回，无需额外的 try-catch：

```php
// 自动异常处理
$result = $client->geocoding()->searchWithFormat(''); // 传入空地址

// 返回格式：
// [
//     'ret' => 0,
//     'msg' => 'Missing required parameter: address',
//     'data' => null
// ]

// 手动异常处理（如果需要）
try {
    $result = $client->geocoding()->search(''); // 原方法
} catch (Exception $e) {
    echo "异常：{$e->getMessage()}\n";
}
```

## 向后兼容性

- **原方法保持不变**：所有原有的方法都保持原来的行为，确保现有代码不受影响
- **新方法命名规则**：在原方法名后添加 `WithFormat` 后缀
- **可以混合使用**：可以在同一个项目中混合使用原方法和新方法

## 最佳实践

1. **新项目推荐使用统一格式方法**，获得更好的错误处理体验
2. **现有项目可以逐步迁移**，先在新功能中使用统一格式方法
3. **统一的错误处理逻辑**，简化业务代码中的异常处理
4. **使用 ret 字段判断操作结果**，而不是检查异常或数据结构

## 完整示例

查看 `examples/unified_format_example.php` 文件获取完整的使用示例。
