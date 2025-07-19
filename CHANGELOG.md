# 更新日志

所有的重要变更都将记录在此文件中。

本项目遵循 [语义化版本](https://semver.org/lang/zh-CN/)。

## [1.1.2] - 2025-07-19

### 重大重构
- 🎯 **API简化** - 移除所有 `WithFormat` 方法，原方法直接返回统一格式 `[ret, msg, data]`
- ✨ **调用简化** - 外部调用更加简洁，无需区分不同的方法名
- 🛡️ **异常处理增强** - 自动捕获和格式化所有异常，无需手动 try-catch
- 📚 **完全向后兼容** - 保持所有原有方法名，只是返回格式统一

### 项目结构优化
- 📖 **文档整合** - 将所有 docs/ 目录文档整合到 README.md 中
- 🗂️ **结构简化** - 删除 docs 目录，简化项目结构
- 📝 **文档完善** - 提供完整的API接口说明和使用示例

### 修改的文件
- `src/Services/GeocodingService.php` - 重构地理编码服务
- `src/Services/ReverseGeocodingService.php` - 重构逆地理编码服务
- `src/Services/PoiService.php` - 重构POI搜索服务
- `src/Services/RouteService.php` - 重构路径规划服务
- `src/Services/CoordinateService.php` - 重构坐标转换服务
- `README.md` - 整合所有文档内容
- 删除 `docs/` 目录及其所有文件

### 测试验证
- ✅ 所有重构后的方法都返回统一格式
- ✅ 异常处理正常工作
- ✅ 100% 测试通过率

## [1.0.0] - 2024-07-18

### 新增
- 🎉 初始版本发布
- ✅ 地理编码服务 - 将地址转换为地理坐标
- ✅ 逆地理编码服务 - 将地理坐标转换为地址信息
- ✅ POI搜索服务 - 普通搜索、周边搜索、视野内搜索、行政区域搜索
- ✅ 完善的异常处理机制 - TianDiTuException、ApiException、NetworkException
- ✅ PSR-4自动加载和PSR-12编码规范
- ✅ 完整的单元测试和集成测试
- ✅ 详细的API文档和使用示例
- ✅ PHP 7.2+ 兼容性支持

### 技术特性
- 基于Guzzle HTTP客户端的高性能请求
- 符合PSR标准的代码结构
- 完整的错误处理和异常管理
- 批量操作支持
- 可配置的HTTP客户端选项
- 单元测试覆盖
- 详细的文档说明
