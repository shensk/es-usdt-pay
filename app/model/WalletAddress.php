<?php

namespace app\model;

use support\Model;

/**
 * USDT钱包地址模型
 * 
 * @property int $id
 * @property string $token 钱包地址
 * @property string|null $name 钱包名称
 * @property int $status 状态：1=启用，2=禁用
 * @property float $balance 钱包余额(USDT)
 * @property float $total_received 累计收款金额
 * @property int $order_count 处理订单数量
 * @property string|null $last_check_time 最后检查时间
 * @property string|null $last_transaction_time 最后交易时间
 * @property string|null $remark 备注
 * @property string|null $created_at 创建时间
 * @property string|null $updated_at 更新时间
 */
class WalletAddress extends Model
{
    /**
     * 与模型关联的表名
     *
     * @var string
     */
    protected $table = 'wallet_addresses';

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
     * 钱包状态常量
     */
    const STATUS_ENABLED = 1;   // 启用
    const STATUS_DISABLED = 2;  // 禁用

    /**
     * 可批量赋值的属性
     *
     * @var array
     */
    protected $fillable = [
        'token',
        'name',
        'status',
        'balance',
        'total_received',
        'order_count',
        'last_check_time',
        'last_transaction_time',
        'remark'
    ];

    /**
     * 应该被转换为日期的属性
     *
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'last_check_time',
        'last_transaction_time'
    ];

    /**
     * 属性类型转换
     *
     * @var array
     */
    protected $casts = [
        'balance' => 'decimal:4',
        'total_received' => 'decimal:4',
        'status' => 'integer',
        'order_count' => 'integer'
    ];

    /**
     * 获取状态文本
     *
     * @return string
     */
    public function getStatusTextAttribute(): string
    {
        $statusMap = [
            self::STATUS_ENABLED => '启用',
            self::STATUS_DISABLED => '禁用'
        ];

        return $statusMap[$this->status] ?? '未知状态';
    }

    /**
     * 检查钱包是否可用
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_ENABLED;
    }

    /**
     * 更新最后检查时间
     *
     * @return bool
     */
    public function updateLastCheckTime(): bool
    {
        $this->last_check_time = date('Y-m-d H:i:s');
        
        return $this->save();
    }

    /**
     * 更新余额
     *
     * @param float $balance
     * @return bool
     */
    public function updateBalance(float $balance): bool
    {
        $this->balance = $balance;
        
        return $this->save();
    }

    /**
     * 增加收款金额
     *
     * @param float $amount
     * @return bool
     */
    public function addReceivedAmount(float $amount): bool
    {
        $this->total_received += $amount;
        $this->order_count += 1;
        $this->last_transaction_time = date('Y-m-d H:i:s');
        
        return $this->save();
    }

    /**
     * 获取所有可用的钱包地址
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getAvailableWallets()
    {
        return static::where('status', self::STATUS_ENABLED)->get();
    }

    /**
     * 根据钱包地址查找
     *
     * @param string $token
     * @return WalletAddress|null
     */
    public static function findByToken(string $token): ?WalletAddress
    {
        return static::where('token', $token)->first();
    }

    /**
     * 获取负载最少的可用钱包（按订单数量排序）
     *
     * @return WalletAddress|null
     */
    public static function getLeastLoadedWallet(): ?WalletAddress
    {
        return static::where('status', self::STATUS_ENABLED)
            ->orderBy('order_count', 'asc')
            ->first();
    }
}
