<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class WalletAddressSeeder extends AbstractSeed
{
    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeders is available here:
     * https://book.cakephp.org/phinx/0/en/seeding.html
     */
    public function run(): void
    {
        $data = [
            [
                'token' => 'TExample1111111111111111111111111111',
                'name' => '示例钱包1',
                'status' => 1,
                'balance' => 0.0000,
                'total_received' => 0.0000,
                'order_count' => 0,
                'remark' => '这是一个示例钱包地址，请替换为实际地址',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'token' => 'TExample2222222222222222222222222222',
                'name' => '示例钱包2',
                'status' => 1,
                'balance' => 0.0000,
                'total_received' => 0.0000,
                'order_count' => 0,
                'remark' => '这是一个示例钱包地址，请替换为实际地址',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'token' => 'TExample3333333333333333333333333333',
                'name' => '示例钱包3',
                'status' => 2,
                'balance' => 0.0000,
                'total_received' => 0.0000,
                'order_count' => 0,
                'remark' => '这是一个示例钱包地址，已禁用',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ];

        $table = $this->table('wallet_addresses');
        $table->insert($data)->saveData();
    }
}
