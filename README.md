# group-verify-service

## 简介

一个基于 ThinkPHP 框架的 进群验证后端项目，提供了丰富的功能模块和灵活的扩展能力。

## 项目结构
app/ 应用目录
  controller/ 控制器目录
  middleware/ 中间件目录
  model/ 模型目录
  validate/ 验证器目录
config/ 配置文件目录
extend/ 扩展目录
public/ 公共入口目录
route/ 路由目录
runtime/ 运行时目录
vendor/ 第三方依赖项
view/ 模板目录


## 安装1. 克隆项目到本地：

```bash
git clone https://github.com/yjwmidc/group-verify-service.git

安装依赖：

```bash
composer install

配置环境：
根据 config/ 目录下的配置文件，修改相关配置以适配您的环境。

使用
启动开发服务器：

```bash
php think run 或者在public目录使用 php -S localhost:8000

访问项目：
在浏览器中打开 http://localhost:8000。

欢迎提交 Issue 或 pr 来帮助改进项目。
