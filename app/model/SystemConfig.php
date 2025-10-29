<?php

namespace app\model;

use support\Model;

/**
 * 系统配置模型
 * 
 * @property int $id
 * @property string $key 配置键
 * @property string|null $value 配置值
 * @property string $type 数据类型
 * @property string $group 配置分组
 * @property string|null $title 配置标题
 * @property string|null $description 配置描述
 * @property int $sort 排序
 * @property string|null $created_at 创建时间
 * @property string|null $updated_at 更新时间
 */
class SystemConfig extends Model
{
    /**
     * 与模型关联的表名
     *
     * @var string
     */
    protected $table = 'system_configs';

    /**
     * 重定义主键，默认是id
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * 指示是否自动维护时间戳
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * 模型的日期字段保存格式
     *
     * @var string
     */
    protected $dateFormat = 'Y-m-d H:i:s';

    /**
     * 可批量赋值的属性
     *
     * @var array
     */
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'title',
        'description',
        'sort'
    ];

    /**
     * 属性类型转换
     *
     * @var array
     */
    protected $casts = [
        'sort' => 'integer'
    ];

    /**
     * 配置缓存
     *
     * @var array
     */
    private static $configCache = [];

    /**
     * 获取配置值
     *
     * @param string $key 配置键
     * @param mixed $default 默认值
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        // 先从缓存中获取
        if (isset(self::$configCache[$key])) {
            return self::$configCache[$key];
        }

        $config = static::where('key', $key)->first();
        
        if (!$config) {
            return $default;
        }

        $value = self::convertValue($config->value, $config->type);
        
        // 缓存配置值
        self::$configCache[$key] = $value;
        
        return $value;
    }

    /**
     * 设置配置值
     *
     * @param string $key 配置键
     * @param mixed $value 配置值
     * @param string $type 数据类型
     * @return bool
     */
    public static function set(string $key, $value, string $type = 'string'): bool
    {
        $config = static::where('key', $key)->first();
        
        if ($config) {
            $config->value = (string)$value;
            $config->type = $type;
            $result = $config->save();
        } else {
            $result = static::create([
                'key' => $key,
                'value' => (string)$value,
                'type' => $type,
                'group' => 'default'
            ]);
        }

        // 更新缓存
        if ($result) {
            self::$configCache[$key] = self::convertValue((string)$value, $type);
        }

        return (bool)$result;
    }

    /**
     * 根据类型转换配置值
     *
     * @param string $value
     * @param string $type
     * @return mixed
     */
    private static function convertValue(string $value, string $type)
    {
        switch ($type) {
            case 'int':
            case 'integer':
                return (int)$value;
            case 'float':
            case 'double':
                return (float)$value;
            case 'bool':
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'json':
                return json_decode($value, true);
            case 'array':
                return json_decode($value, true) ?: [];
            default:
                return $value;
        }
    }

    /**
     * 获取分组配置
     *
     * @param string $group
     * @return array
     */
    public static function getByGroup(string $group): array
    {
        $configs = static::where('group', $group)
            ->orderBy('sort', 'asc')
            ->get();

        $result = [];
        foreach ($configs as $config) {
            $result[$config->key] = self::convertValue($config->value, $config->type);
        }

        return $result;
    }

    /**
     * 清除配置缓存
     *
     * @param string|null $key 指定键，为空则清除所有缓存
     */
    public static function clearCache(string $key = null): void
    {
        if ($key) {
            unset(self::$configCache[$key]);
        } else {
            self::$configCache = [];
        }
    }

    /**
     * 获取API认证令牌
     *
     * @return string
     */
    public static function getApiAuthToken(): string
    {
        return self::get('api_auth_token', 'your-api-auth-token-here');
    }

    /**
     * 获取USDT汇率
     *
     * @return float
     */
    public static function getUsdtRate(): float
    {
        $forcedRate = self::get('forced_usdt_rate', 0);
        if ($forcedRate > 0) {
            return $forcedRate;
        }
        
        return self::get('usdt_rate', 6.4);
    }

    /**
     * 获取订单过期时间（分钟）
     *
     * @return int
     */
    public static function getOrderExpirationTime(): int
    {
        return self::get('order_expiration_time', 10);
    }

    /**
     * 获取最小支付金额
     *
     * @return float
     */
    public static function getMinPaymentAmount(): float
    {
        return self::get('min_payment_amount', 0.01);
    }

    /**
     * 获取最大支付金额
     *
     * @return float
     */
    public static function getMaxPaymentAmount(): float
    {
        return self::get('max_payment_amount', 50000);
    }

    /**
     * 获取回调重试次数
     *
     * @return int
     */
    public static function getCallbackRetryTimes(): int
    {
        return self::get('callback_retry_times', 5);
    }

    /**
     * 获取区块确认数
     *
     * @return int
     */
    public static function getBlockConfirmations(): int
    {
        return self::get('block_confirmations', 19);
    }
}
