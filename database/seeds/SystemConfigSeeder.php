<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class SystemConfigSeeder extends AbstractSeed
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
                'key' => 'api_auth_token',
                'value' => 'your-api-auth-token-here',
                'type' => 'string',
                'group' => 'api',
                'title' => 'API认证令牌',
                'description' => 'API接口认证使用的令牌',
                'sort' => 1
            ],
            [
                'key' => 'usdt_rate',
                'value' => '6.4',
                'type' => 'float',
                'group' => 'payment',
                'title' => 'USDT汇率',
                'description' => '人民币兑USDT汇率',
                'sort' => 2
            ],
            [
                'key' => 'forced_usdt_rate',
                'value' => '0',
                'type' => 'float',
                'group' => 'payment',
                'title' => '强制USDT汇率',
                'description' => '强制使用的USDT汇率，0表示使用实时汇率',
                'sort' => 3
            ],
            [
                'key' => 'order_expiration_time',
                'value' => '10',
                'type' => 'int',
                'group' => 'payment',
                'title' => '订单过期时间',
                'description' => '订单过期时间（分钟）',
                'sort' => 4
            ],
            [
                'key' => 'min_payment_amount',
                'value' => '0.01',
                'type' => 'float',
                'group' => 'payment',
                'title' => '最小支付金额',
                'description' => '最小支付金额（CNY）',
                'sort' => 7
            ],
            [
                'key' => 'max_payment_amount',
                'value' => '50000',
                'type' => 'float',
                'group' => 'payment',
                'title' => '最大支付金额',
                'description' => '最大支付金额（CNY）',
                'sort' => 8
            ],
            [
                'key' => 'callback_retry_times',
                'value' => '5',
                'type' => 'int',
                'group' => 'payment',
                'title' => '回调重试次数',
                'description' => '支付成功后回调重试次数',
                'sort' => 9
            ],
            [
                'key' => 'block_confirmations',
                'value' => '19',
                'type' => 'int',
                'group' => 'blockchain',
                'title' => '区块确认数',
                'description' => '交易确认所需的区块确认数',
                'sort' => 10
            ]
        ];

        $table = $this->table('system_configs');
        $table->insert($data)->saveData();
    }
}
