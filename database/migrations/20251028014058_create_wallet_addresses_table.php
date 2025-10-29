<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateWalletAddressesTable extends AbstractMigration
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
        $table = $this->table('wallet_addresses', [
            'id' => true,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'USDT钱包地址表'
        ]);
        
        $table->addColumn('token', 'string', [
                'limit' => 50,
                'null' => false,
                'comment' => '钱包地址'
            ])
            ->addColumn('name', 'string', [
                'limit' => 100,
                'null' => true,
                'comment' => '钱包名称'
            ])
            ->addColumn('status', 'integer', [
                'limit' => 4,
                'null' => false,
                'default' => 1,
                'comment' => '状态：1=启用，2=禁用'
            ])
            ->addColumn('balance', 'decimal', [
                'precision' => 19,
                'scale' => 4,
                'null' => false,
                'default' => '0.0000',
                'comment' => '钱包余额(USDT)'
            ])
            ->addColumn('total_received', 'decimal', [
                'precision' => 19,
                'scale' => 4,
                'null' => false,
                'default' => '0.0000',
                'comment' => '累计收款金额'
            ])
            ->addColumn('order_count', 'integer', [
                'null' => false,
                'default' => 0,
                'comment' => '处理订单数量'
            ])
            ->addColumn('last_check_time', 'timestamp', [
                'null' => true,
                'comment' => '最后检查时间'
            ])
            ->addColumn('last_transaction_time', 'timestamp', [
                'null' => true,
                'comment' => '最后交易时间'
            ])
            ->addColumn('remark', 'text', [
                'null' => true,
                'comment' => '备注'
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
            ->addIndex(['token'], ['unique' => true, 'name' => 'uk_token'])
            ->addIndex(['status'], ['name' => 'idx_status'])
            ->addIndex(['last_check_time'], ['name' => 'idx_last_check_time'])
            ->create();
    }
}
