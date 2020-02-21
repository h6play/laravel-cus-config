<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConfigTables extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function getConnection()
    {
        return config('config.database.connection') ?: config('database.default');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(config("config.database.tables.config", "config"), function (Blueprint $table) {
            $table->increments('id');
            $table->string("provider")->nullable()->comment("提供者")->index();
            $table->integer("mch")->default("0")->comment("提供者ID")->index();
            $table->longText("data")->nullable()->comment("配置数据");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(config("config.database.tables.config", "config"));
    }
}
