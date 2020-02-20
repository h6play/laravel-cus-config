<?php

namespace H6play\LaravelCusConfig;


use Illuminate\Support\ServiceProvider;

class CusConfigServiceProvider extends ServiceProvider
{

    public function boot() {

        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__ . '/../Config' => app_path("Config")]);
            $this->publishes([__DIR__ . '/../config' => config_path()]);
            $this->publishes([__DIR__ . '/../database/migrations' => database_path('migrations')]);
        }

        // 启动加载配置
        CusConfig::build()->load();
    }
}
