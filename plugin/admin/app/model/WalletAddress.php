<?php

namespace plugin\admin\app\model;

use plugin\admin\app\model\Base;

/**
 * @property integer $id (主键)
 * @property string $token 钱包地址
 * @property string $name 钱包名称
 * @property integer $status 状态：1=启用，2=禁用
 * @property string $balance 钱包余额(USDT)
 * @property string $total_received 累计收款金额
 * @property integer $order_count 处理订单数量
 * @property mixed $last_check_time 最后检查时间
 * @property mixed $last_transaction_time 最后交易时间
 * @property string $remark 备注
 * @property mixed $created_at 创建时间
 * @property mixed $updated_at 更新时间
 */
class WalletAddress extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wallet_addresses';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';
    
    
    
}
