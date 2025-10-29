<?php

namespace plugin\admin\app\model;

use plugin\admin\app\model\Base;

/**
 * @property integer $id (主键)
 * @property string $trade_id USDT支付系统订单号
 * @property string $order_id 客户交易ID
 * @property string $block_transaction_id 区块链交易哈希
 * @property string $actual_amount 实际需要支付的USDT金额，保留4位小数
 * @property string $amount 订单金额(CNY)，保留4位小数
 * @property string $token 收款钱包地址
 * @property integer $status 订单状态：1=等待支付，2=支付成功，3=已过期，4=已取消
 * @property string $notify_url 异步回调地址
 * @property string $redirect_url 同步跳转地址
 * @property integer $callback_num 回调次数
 * @property integer $callback_confirm 回调确认状态：1=已确认，2=未确认
 * @property string $usdt_rate 创建订单时的USDT汇率
 * @property string $client_ip 客户端IP地址
 * @property string $user_agent 客户端User-Agent
 * @property string $remark 订单备注
 * @property mixed $created_at 创建时间
 * @property mixed $updated_at 更新时间
 * @property mixed $expired_at 过期时间
 * @property mixed $paid_at 支付完成时间
 */
class Order extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'orders';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';
    
    
    
}
