# Laravel 自定义配置插件

    这是一款能够适合自定义配置，且支持多商户配置的插件，搭配 laravel-admin 会更爽哦！

## 介绍

* 很多时候我们都会迷茫，到底配置是写死好
* 例如 `config/xxx.php`
* 还是可以在 `想要更改的时候随时进行保存更改`
* 如果你选择　`写死`　再见！
* 哈哈！　玩笑开到这，话不多说先介绍！
* 如何寄刀片给作者:
    * `QQ: 3407706358`

## 功能支持

* 系统默认配置
* 多商户配置　`例如常用的Sass开发`
* 支持分布式部署　`采用redis同步配置版本|数据库存储配置信息`

## 安装方式

1. Composer安装
    * `composer require h6play/laravel-cus-config`
2. 发布资源
    * `php artisan vendor:publish --provider="H6play\LaravelCusConfig\CusConfigServiceProvider"`
3. 执行数据库迁移
    * `php artisan migrate`

## 使用方法

```php
// [加载指定配置] <提供者>, <多商户标识:int>
CusConfig::build("default", 0)->load();

// [获取配置]
config("system.app.name");

// [设置配置]
// 正常调用 config() 函数进行设置运行内存中的配置数组
// 然后调用 CusConfig::build("default", 0)->put(); 进行持久存储
config([
    "system.app.name" => "h6play",
]);
CusConfig::build("default", 0)->put();

// [更新缓存结构]
// 每次修改了配置文件的结构需要使用该函数进行结构更新和版本更替
CusConfig::build("default", 0)->update();

// [清空缓存]
CusConfig::build("default", 0)->clear();

// [重置缓存]
CusConfig::build("default", 0)->reset();
```

## 配置说明

```php
return [
    // 配置提供者 (支持全局系统配置，多商户配置)
    "providers" => [
        "default" => [
            "files" => ["system"], // 配置模板文件 [xxx,xxx, ...] 指在　app/Config/xxx.php 下的文件名　不带.php后缀
            "customs" => [
                // 更新配置不需要校验格式的配置　像一些配置不需要更新保存的时候进行数据格式强校验的配置到这里，免得数据丢失，例如数组
                "system.app.list",
                // 更多　... 配置全指向索引
            ],
        ],
        // 添加更多的配置提供者
    ],
    // 缓存配置
    "cache" => [
        "dirve"  => env("CACHE_DRIVER", "file"), // 缓存驱动 file|redis 建议使用 redis 能够部署在分布式服务器 redis 只缓存版本
        "prefix" => "h6play_cus_config", // 缓存Key前缀

        // 本地缓存
        "local" => [
            "store_path" => "app/framework/config", // 缓存路径
            "store_suffix" => ".json", // 缓存后缀
        ]
    ],
    // 数据库配置
    "database" => [
        "connection" => env("DB_CONNECTION", "mysql"), // 数据库连接
        "tables" => [
            "config" => "config", // 配置表名 table=>当前名称
        ],
    ],
];
```
