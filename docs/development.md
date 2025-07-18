# 天地图 PHP SDK 开发指南

## 开发环境要求

- PHP >= 7.2
- Composer
- ext-json（JSON 扩展）

## 项目结构

```
tianditu-php-sdk/
├── src/                    # 源代码目录
│   ├── Exceptions/         # 异常类
│   ├── Services/          # 服务类
│   └── TianDiTuClient.php # 主客户端类
├── tests/                 # 测试文件
├── docs/                  # 文档
├── examples/              # 示例代码
├── composer.json          # Composer 配置
├── phpunit.xml           # PHPUnit 配置
├── phpcs.xml            # PHP CodeSniffer 配置
├── phpstan.neon         # PHPStan 配置
└── README.md           # 项目说明
```

## 开发流程

### 1. 安装依赖

```bash
composer install
```

### 2. 运行测试

```bash
# 运行所有测试
composer test

# 或者直接使用 PHPUnit
./vendor/bin/phpunit
```

### 3. 代码风格检查

```bash
# 检查代码风格
composer phpcs

# 或者直接使用 PHP CodeSniffer
./vendor/bin/phpcs src tests --standard=PSR12
```

### 4. 静态代码分析

```bash
# 运行 PHPStan
composer phpstan

# 或者直接使用 PHPStan
./vendor/bin/phpstan analyse src --level=5
```

### 5. 运行所有检查

```bash
composer check
```

## 发布到 Composer

### 1. 准备发布

确保所有测试通过，代码风格符合标准：

```bash
composer check
```

### 2. 更新版本信息

更新以下文件：
- `composer.json` 中的版本信息（如果需要）
- `CHANGELOG.md` 添加新版本的更新日志
- `README.md` 确保信息准确

### 3. 创建 Git 标签

```bash
git add .
git commit -m "Release version 1.0.0"
git tag v1.0.0
git push origin main
git push origin v1.0.0
```

### 4. 提交到 Packagist

1. 在 [Packagist.org](https://packagist.org/) 创建账号
2. 点击 "Submit" 按钮
3. 输入你的 GitHub 仓库 URL
4. 点击 "Check" 验证
5. 如果验证通过，点击 "Submit" 提交

### 5. 设置自动更新

在 Packagist 项目页面：
1. 点击 "Settings" 
2. 在 GitHub Integration 部分配置 webhook
3. 这样每次推送新标签时会自动更新 Packagist

## API 密钥配置

开发和测试时，你可以通过以下方式配置 API 密钥：

### 1. 环境变量（推荐）

```bash
export TIANDITU_API_KEY="your-api-key-here"
```

### 2. .env 文件

在项目根目录创建 `.env` 文件：

```
TIANDITU_API_KEY=your-api-key-here
```

### 3. 直接在代码中指定

```php
$client = new TianDiTuClient('your-api-key-here');
```

## 测试 API 连接

运行示例代码测试 API 连接：

```bash
php examples/basic_usage.php
```

## 兼容性说明

### PHP 版本兼容性

- **PHP 7.2+**: 完全支持
- **PHP 8.0+**: 完全支持
- **PHP 8.1+**: 完全支持

### Guzzle 版本兼容性

- **Guzzle 6.5+**: 支持（用于 PHP 7.2 兼容性）
- **Guzzle 7.0+**: 支持

## 开发注意事项

### 1. 代码规范

- 遵循 PSR-12 代码风格标准
- 使用有意义的变量名和方法名
- 添加适当的注释和文档

### 2. 错误处理

- 为所有 API 调用提供适当的错误处理
- 使用特定的异常类型
- 提供有用的错误信息

### 3. 测试覆盖

- 为所有公共方法编写单元测试
- 使用 Mock 对象测试外部依赖
- 确保测试覆盖率达到 80% 以上

### 4. 文档

- 为所有公共 API 提供完整的文档
- 提供使用示例
- 保持文档与代码同步更新

## 贡献指南

1. Fork 项目
2. 创建特性分支
3. 进行修改
4. 添加测试
5. 确保所有检查通过
6. 提交 Pull Request

## 问题排查

### 常见问题

1. **API 密钥无效**
   - 检查 API 密钥是否正确
   - 确认 API 密钥未过期

2. **网络连接问题**
   - 检查网络连接
   - 确认防火墙设置

3. **依赖冲突**
   - 运行 `composer update` 更新依赖
   - 检查 PHP 版本兼容性

### 调试模式

启用 Guzzle 调试模式查看详细的 HTTP 请求：

```php
use GuzzleHttp\Client;

$httpClient = new Client([
    'debug' => true, // 启用调试模式
]);

$client = new TianDiTuClient('your-api-key', $httpClient);
```
