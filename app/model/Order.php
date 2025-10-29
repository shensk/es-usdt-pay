<?php

namespace app\model;

use support\Model;

/**
 * USDT支付订单模型
 * 
 * @property int $id
 * @property string $trade_id USDT支付系统订单号
 * @property string $order_id 客户交易ID
 * @property string|null $block_transaction_id 区块链交易哈希
 * @property float $actual_amount 实际需要支付的USDT金额
 * @property float $amount 订单金额(CNY)
 * @property string $token 收款钱包地址
 * @property int $status 订单状态：1=等待支付，2=支付成功，3=已过期，4=已取消
 * @property string $notify_url 异步回调地址
 * @property string|null $redirect_url 同步跳转地址
 * @property int $callback_num 回调次数
 * @property int $callback_confirm 回调确认状态：1=已确认，2=未确认
 * @property float $usdt_rate 创建订单时的USDT汇率
 * @property string|null $client_ip 客户端IP地址
 * @property string|null $user_agent 客户端User-Agent
 * @property string|null $remark 订单备注
 * @property string|null $created_at 创建时间
 * @property string|null $updated_at 更新时间
 * @property string|null $expired_at 过期时间
 * @property string|null $paid_at 支付完成时间
 */
class Order extends Model
{
    /**
     * 与模型关联的表名
     *
     * @var string
     */
    protected $table = 'orders';

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
     * 订单状态常量
     */
    const STATUS_PENDING = 1;    // 等待支付
    const STATUS_PAID = 2;       // 支付成功
    const STATUS_EXPIRED = 3;    // 已过期
    const STATUS_CANCELLED = 4;  // 已取消

    /**
     * 回调确认状态常量
     */
    const CALLBACK_CONFIRMED = 1;    // 已确认
    const CALLBACK_UNCONFIRMED = 2;  // 未确认

    /**
     * 可批量赋值的属性
     *
     * @var array
     */
    protected $fillable = [
        'trade_id',
        'order_id',
        'block_transaction_id',
        'actual_amount',
        'amount',
        'token',
        'status',
        'notify_url',
        'redirect_url',
        'callback_num',
        'callback_confirm',
        'usdt_rate',
        'client_ip',
        'user_agent',
        'remark',
        'expired_at',
        'paid_at'
    ];

    /**
     * 应该被转换为日期的属性
     *
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'expired_at',
        'paid_at'
    ];

    /**
     * 属性类型转换
     *
     * @var array
     */
    protected $casts = [
        'actual_amount' => 'decimal:4',
        'amount' => 'decimal:4',
        'usdt_rate' => 'decimal:4',
        'status' => 'integer',
        'callback_num' => 'integer',
        'callback_confirm' => 'integer'
    ];

    /**
     * 获取订单状态文本
     *
     * @return string
     */
    public function getStatusTextAttribute(): string
    {
        $statusMap = [
            self::STATUS_PENDING => '等待支付',
            self::STATUS_PAID => '支付成功',
            self::STATUS_EXPIRED => '已过期',
            self::STATUS_CANCELLED => '已取消'
        ];

        return $statusMap[$this->status] ?? '未知状态';
    }

    /**
     * 检查订单是否已过期
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        if (!$this->expired_at) {
            return false;
        }

        return strtotime($this->expired_at) < time();
    }

    /**
     * 检查订单是否可以支付
     *
     * @return bool
     */
    public function canPay(): bool
    {
        return $this->status === self::STATUS_PENDING && !$this->isExpired();
    }

    /**
     * 标记订单为已支付
     *
     * @param string $blockTransactionId 区块链交易哈希
     * @return bool
     */
    public function markAsPaid(string $blockTransactionId): bool
    {
        $this->status = self::STATUS_PAID;
        $this->block_transaction_id = $blockTransactionId;
        $this->paid_at = date('Y-m-d H:i:s');
        
        return $this->save();
    }

    /**
     * 标记订单为已过期
     *
     * @return bool
     */
    public function markAsExpired(): bool
    {
        $this->status = self::STATUS_EXPIRED;
        
        return $this->save();
    }

    /**
     * 增加回调次数
     *
     * @return bool
     */
    public function incrementCallbackNum(): bool
    {
        $this->callback_num += 1;
        
        return $this->save();
    }

    /**
     * 标记回调已确认
     *
     * @return bool
     */
    public function markCallbackConfirmed(): bool
    {
        $this->callback_confirm = self::CALLBACK_CONFIRMED;
        
        return $this->save();
    }

    /**
     * 生成交易ID
     *
     * @return string
     */
    public static function generateTradeId(): string
    {
        return date('YmdHis') . mt_rand(100000, 999999);
    }

    /**
     * 根据交易ID查找订单
     *
     * @param string $tradeId
     * @return Order|null
     */
    public static function findByTradeId(string $tradeId): ?Order
    {
        return static::where('trade_id', $tradeId)->first();
    }

    /**
     * 根据客户订单ID查找订单
     *
     * @param string $orderId
     * @return Order|null
     */
    public static function findByOrderId(string $orderId): ?Order
    {
        return static::where('order_id', $orderId)->first();
    }
}
