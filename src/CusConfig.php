<?php


namespace H6play\LaravelCusConfig;



use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

/**
 * 配置服务类
 * Class ConfigService
 * @package App\Services
 */
class CusConfig
{
    /** @var static */
    protected static $instance = [];

    /**
     * @var Cache|null
     */
    protected $cacheSer = null;

    protected $mch       = 0;         // ID
    protected $provider = "default"; // 配置类型
    protected $path     = "config";  // 配置缓存目录
    protected $fullPath = "";        // 配置缓全路径
    protected $version  = 1;         // 版本
    protected $file     = "";        // 文件名
    protected $tables   = [];        // 表名

    public function __construct($provider = "default", $mch = 0)
    {
        $this->provider = $provider;
        $this->mch = $mch;

        // 设置缓存驱动
        $this->cacheSer = Cache::store(config("config.cache.dirve", "file"));

        // 获取版本
        $this->version = (int) $this->cacheSer->get($this->versionCacheKey());

        // 获取本地缓存文件路径
        $path = config("config.cache.local.store_path", "app/framework/config");
        $this->path = "{$path}/{$this->provider}/{$this->mch}/";
        $this->fullPath = storage_path($this->path);

        // 获取本地缓存文件名
        $this->file = "{$this->version}" . config("config.cache.local.store_suffix", ".json");

        $this->tables = [
            "config" => config("config.database.tables.config", "config"),
        ];
    }

    /**
     * @param string $provider
     * @return static
     */
    public static function build($provider = "default", $mch = 0) {
        if(!isset(self::$instance[$provider])) {
            self::$instance[$provider] = [];
        }
        if(!isset(self::$instance[$provider][$mch])) {
            self::$instance[$provider][$mch] = new static($provider, $mch);
        }
        return self::$instance[$provider][$mch];
    }

    /**
     * 版本缓存Key
     * @return string
     */
    protected function versionCacheKey() {
        return config("config.cache.prefix", "h6play_cus_config") . "_{$this->provider}_{$this->mch}";
    }

    /**
     * 版本缓存增加
     */
    protected function versionCacheInc() {
        $this->version = $this->version + 1;
        $this->cacheSer->forever($this->versionCacheKey(), $this->version);
    }

    /**
     * 获取全路径
     * @return string
     */
    protected function getRealFilePath() {
        return storage_path("{$this->path}/{$this->file}");
    }

    /**
     * 删除缓存目录
     */
    protected function delDirCache() {
        // 创建目录
        if(File::isDirectory($this->fullPath)) {
            File::deleteDirectory($this->fullPath);
        }
    }

    /**
     * 添加缓存目录
     */
    protected function addDirCache() {
        !File::isDirectory($this->fullPath) && File::makeDirectory($this->fullPath, 0777, true);
    }

    /**
     * @return bool
     */
    protected function hasTable() {
        $res = DB::selectOne("SHOW TABLES LIKE '{$this->tables['config']}'");
        return $res != null;
    }

    /**
     * 获取缓存
     * @return array|null
     */
    protected function getCache() {
        $path = $this->getRealFilePath();
        if(File::exists($path)) {
            $data = File::get($path);
        } else {
            if($this->hasTable())
            {
                $data = DB::table($this->tables['config'])
                    ->where("provider", $this->provider)
                    ->where("mch", $this->mch)
                    ->value("data");
                // 同步缓存到本地
                if(!is_null($data)) {
                    $this->delDirCache();
                    $this->addDirCache();
                    File::put($path, $data);
                }
            } else {
                $data = null;
            }
        }
        return json_decode($data, true);
    }

    /**
     * 保存缓存
     * @param array $data
     */
    protected function setCache($data = []) {
        $data = json_encode($data);
        if($this->hasTable()) {

            // 保存到数据库
            $result = DB::table($this->tables['config'])
                ->where("provider", $this->provider)
                ->where("mch", $this->mch)
                ->exists();
            if($result) {
                $result = DB::table($this->tables['config'])
                    ->where("provider", $this->provider)
                    ->where("mch", $this->mch)
                    ->update([
                        "data" => $data,
                        "updated_at" => now(),
                    ]);
            } else {
                $result = DB::table($this->tables['config'])
                    ->insert([
                        "provider" => $this->provider,
                        "mch" => $this->mch,
                        "data" => $data,
                        "updated_at" => now(),
                        "created_at" => now(),
                    ]);
            }
            if($result) {
                // 保存到版本
                $this->versionCacheInc();
            }
        }

        // 保存到本地缓存
        $this->delDirCache();
        $this->addDirCache();
        File::put($this->getRealFilePath(), $data);
    }

    /**
     * 加载配置 请在程序启动引入
     */
    public function load() {
        $data = $this->getCache();
        if(is_null($data)) {
            $data = $this->reset();
        }
        config($data);
    }

    /**
     * 将当前配置写到缓存
     */
    public function put() {
        $data = [];

        // 读取自定义类型
        $cusArray =  [];
        foreach (config("config.providers.{$this->provider}.customs", []) as $k) {
            $cusArray[$k] = config($k);
        }

        foreach (config("config.providers.{$this->provider}.files", []) as $key) {
            // 读取原配置
            if($this->existsConfig($key)) {
                $data[$key] = $this->requireConfig($key);
            }

            // 更新缓存配置
            $dataCache = config($key);
            $dataCache = is_array($dataCache) ? $dataCache : [];

            // 规范合并数据
            $data[$key] = $this->arrayMerge($data[$key], $dataCache);
        }

        // 保存自定义类型
        foreach ($cusArray as $k => $v) {
            Arr::set($data, $k, $v);
        }

        $this->setCache($data); // 保存到缓存
        return $this;
    }

    /**
     * 更新配置缓存 - 以新的结构
     */
    public function update() {
        $data = [];

        // 读取缓存配置
        $dataCache = $this->getCache();
        $dataCache = is_array($dataCache)?$dataCache:[];

        // 读取自定义类型
        $cusArray =  [];
        foreach (config("config.providers.{$this->provider}.customs", []) as $k) {
            $cusArray[$k] = Arr::get($dataCache, $k);
        }

        foreach (config("config.providers.{$this->provider}.files", []) as $key) {
            // 读取原配置
            if($this->existsConfig($key)) {
                $data[$key] = $this->requireConfig($key);
            }
            // 更新缓存配置
            if(isset($dataCache[$key])) {
                $data[$key] = $this->arrayMerge($data[$key], $dataCache[$key]);
            }
        }

        // 保存自定义类型
        foreach ($cusArray as $k => $v) {
            Arr::set($data, $k, $v);
        }

        $this->setCache($data);
        return $this;
    }

    /**
     * 清空缓存
     */
    public function clear() {
        $this->incVersion();
    }

    /**
     * 重置配置缓存
     * @return array
     */
    public function reset() {
        $data = [];

        foreach (config("config.providers.{$this->provider}.files", []) as $key) {
            // 读取原配置
            if($this->existsConfig($key)) {
                $data[$key] = $this->requireConfig($key);
            }
        }

        $this->setCache($data);
        return $data;
    }

    /**
     * 读取配置文件
     * @param $key
     * @return mixed
     */
    protected function requireConfig($key) {
        return include app_path("Config/{$key}.php");
    }

    /**
     * @param $key
     * @return bool
     */
    public function existsConfig($key) {
        return File::exists(app_path("Config/{$key}.php") );
    }

    /**
     * 合并数组
     * @param array $a
     * @param array $b
     * @return array
     */
    protected function arrayMerge(array $a,array $b) {
        foreach ($a as $k => $v) {
            if(isset($b[$k])) {
                if(is_array($a[$k])) {
                    $a[$k] = $this->arrayMerge($a[$k], $b[$k]);
                } else if(!is_array($b[$k])) {
                    $a[$k] = $b[$k];
                }
            }
        }
        return $a;
    }
}
