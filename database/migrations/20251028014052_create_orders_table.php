<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateOrdersTable extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change(): void
    {
        $table = $this->table('orders', [
            'id' => true,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'USDT支付订单表'
        ]);
        
        $table->addColumn('trade_id', 'string', [
                'limit' => 32,
                'null' => false,
                'comment' => 'USDT支付系统订单号'
            ])
            ->addColumn('order_id', 'string', [
                'limit' => 32,
                'null' => false,
                'comment' => '客户交易ID'
            ])
            ->addColumn('block_transaction_id', 'string', [
                'limit' => 128,
                'null' => true,
                'comment' => '区块链交易哈希'
            ])
            ->addColumn('actual_amount', 'decimal', [
                'precision' => 19,
                'scale' => 4,
                'null' => false,
                'comment' => '实际需要支付的USDT金额，保留4位小数'
            ])
            ->addColumn('amount', 'decimal', [
                'precision' => 19,
                'scale' => 4,
                'null' => false,
                'comment' => '订单金额(CNY)，保留4位小数'
            ])
            ->addColumn('token', 'string', [
                'limit' => 50,
                'null' => false,
                'comment' => '收款钱包地址'
            ])
            ->addColumn('status', 'integer', [
                'limit' => 4,
                'null' => false,
                'default' => 1,
                'comment' => '订单状态：1=等待支付，2=支付成功，3=已过期，4=已取消'
            ])
            ->addColumn('notify_url', 'string', [
                'limit' => 255,
                'null' => false,
                'comment' => '异步回调地址'
            ])
            ->addColumn('redirect_url', 'string', [
                'limit' => 255,
                'null' => true,
                'comment' => '同步跳转地址'
            ])
            ->addColumn('callback_num', 'integer', [
                'null' => false,
                'default' => 0,
                'comment' => '回调次数'
            ])
            ->addColumn('callback_confirm', 'integer', [
                'limit' => 4,
                'null' => false,
                'default' => 2,
                'comment' => '回调确认状态：1=已确认，2=未确认'
            ])
            ->addColumn('usdt_rate', 'decimal', [
                'precision' => 10,
                'scale' => 4,
                'null' => false,
                'default' => '6.4000',
                'comment' => '创建订单时的USDT汇率'
            ])
            ->addColumn('client_ip', 'string', [
                'limit' => 45,
                'null' => true,
                'comment' => '客户端IP地址'
            ])
            ->addColumn('user_agent', 'string', [
                'limit' => 500,
                'null' => true,
                'comment' => '客户端User-Agent'
            ])
            ->addColumn('remark', 'text', [
                'null' => true,
                'comment' => '订单备注'
            ])
            ->addColumn('created_at', 'timestamp', [
                'null' => true,
                'default' => 'CURRENT_TIMESTAMP',
                'comment' => '创建时间'
            ])
            ->addColumn('updated_at', 'timestamp', [
                'null' => true,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'comment' => '更新时间'
            ])
            ->addColumn('expired_at', 'timestamp', [
                'null' => true,
                'comment' => '过期时间'
            ])
            ->addColumn('paid_at', 'timestamp', [
                'null' => true,
                'comment' => '支付完成时间'
            ])
            ->addIndex(['trade_id'], ['unique' => true, 'name' => 'uk_trade_id'])
            ->addIndex(['order_id'], ['unique' => true, 'name' => 'uk_order_id'])
            ->addIndex(['block_transaction_id'], ['name' => 'idx_block_transaction_id'])
            ->addIndex(['token'], ['name' => 'idx_token'])
            ->addIndex(['status'], ['name' => 'idx_status'])
            ->addIndex(['created_at'], ['name' => 'idx_created_at'])
            ->addIndex(['expired_at'], ['name' => 'idx_expired_at'])
            ->create();
    }
}
