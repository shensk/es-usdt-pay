<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateTransactionsTable extends AbstractMigration
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
        $table = $this->table('transactions', [
            'id' => true,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => '区块链交易记录表'
        ]);
        
        $table->addColumn('transaction_hash', 'string', [
                'limit' => 128,
                'null' => false,
                'comment' => '区块链交易哈希'
            ])
            ->addColumn('from_address', 'string', [
                'limit' => 50,
                'null' => false,
                'comment' => '发送方地址'
            ])
            ->addColumn('to_address', 'string', [
                'limit' => 50,
                'null' => false,
                'comment' => '接收方地址'
            ])
            ->addColumn('amount', 'decimal', [
                'precision' => 19,
                'scale' => 4,
                'null' => false,
                'comment' => '交易金额(USDT)'
            ])
            ->addColumn('block_number', 'biginteger', [
                'null' => true,
                'comment' => '区块高度'
            ])
            ->addColumn('block_timestamp', 'timestamp', [
                'null' => true,
                'comment' => '区块时间'
            ])
            ->addColumn('confirmations', 'integer', [
                'null' => false,
                'default' => 0,
                'comment' => '确认数'
            ])
            ->addColumn('status', 'integer', [
                'limit' => 4,
                'null' => false,
                'default' => 1,
                'comment' => '状态：1=待确认，2=已确认，3=失败'
            ])
            ->addColumn('order_id', 'integer', [
                'null' => true,
                'comment' => '关联订单ID'
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
            ->addIndex(['transaction_hash'], ['unique' => true, 'name' => 'uk_transaction_hash'])
            ->addIndex(['to_address'], ['name' => 'idx_to_address'])
            ->addIndex(['from_address'], ['name' => 'idx_from_address'])
            ->addIndex(['amount'], ['name' => 'idx_amount'])
            ->addIndex(['block_timestamp'], ['name' => 'idx_block_timestamp'])
            ->addIndex(['order_id'], ['name' => 'idx_order_id'])
            ->create();
    }
}
