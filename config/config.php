<?php
return [
    // 配置提供者 (支持全局系统配置，多商户配置)
    "providers" => [
        "default" => [
            "files" => ["system"], // 配置模板文件
            "customs" => [
                // 更新配置不需要校验格式的配置
                "system.app.list",
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
