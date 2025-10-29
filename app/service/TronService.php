<?php

namespace app\service;

use app\model\SystemConfig;
use app\model\WalletAddress;
use app\model\Order;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * TRC20 USDT监听服务类
 * 参考原Epusdt系统实现，使用TronScan API
 */
class TronService
{
    private $client;
    private $tronScanApiUrl;

    public function __construct()
    {
        // 使用TronScan API，与原系统保持一致，无需配置API密钥
        $this->tronScanApiUrl = 'https://apilist.tronscanapi.com';
        
        $this->client = new Client([
            'timeout' => 30,
            'headers' => [
                'Content-Type' => 'application/json',
                'User-Agent' => 'USDT-Payment-Monitor/1.0'
            ]
        ]);
    }

    /**
     * 获取TRC20 USDT转账记录（使用TronScan API，与原系统一致）
     *
     * @param string $address 钱包地址
     * @return array
     */
    public function getTrc20Transfers(string $address): array
    {
        try {
            // 获取最近24小时的交易记录
            $startTime = (time() - 24 * 3600) * 1000; // 24小时前的毫秒时间戳
            $endTime = time() * 1000; // 当前毫秒时间戳
            
            $params = [
                'sort' => '-timestamp',
                'limit' => '50',
                'start' => '0',
                'direction' => '2', // 2表示转入
                'db_version' => '1',
                'trc20Id' => 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t', // USDT合约地址
                'address' => $address,
                'start_timestamp' => (string)$startTime,
                'end_timestamp' => (string)$endTime,
            ];

            $response = $this->client->get('/api/transfer/trc20', [
                'query' => $params,
                'base_uri' => $this->tronScanApiUrl
            ]);

            if ($response->getStatusCode() !== 200) {
                error_log("TronScan API error: HTTP " . $response->getStatusCode());
                return [];
            }

            $data = json_decode($response->getBody(), true);
            
            if (!$data || $data['code'] !== 0 || !isset($data['data'])) {
                return [];
            }

            return $data['data'];
        } catch (RequestException $e) {
            error_log("TronService::getTrc20Transfers error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 检查所有钱包地址的新交易（参考原系统实现）
     *
     * @return int 处理的交易数量
     */
    public function checkAllWalletTransactions(): int
    {
        $wallets = WalletAddress::getAvailableWallets();
        $processedCount = 0;

        foreach ($wallets as $wallet) {
            try {
                $count = $this->trc20CallBack($wallet->token);
                $processedCount += $count;
                
                // 更新最后检查时间
                $wallet->updateLastCheckTime();
            } catch (\Exception $e) {
                error_log("检查钱包 {$wallet->token} 时发生错误: " . $e->getMessage());
            }
        }

        return $processedCount;
    }

    /**
     * TRC20回调处理（完全参考原系统实现）
     *
     * @param string $token 钱包地址
     * @return int 处理的交易数量
     */
    public function trc20CallBack(string $token): int
    {
        $transfers = $this->getTrc20Transfers($token);
        $processedCount = 0;

        foreach ($transfers as $transfer) {
            try {
                // 只处理转入到指定地址且交易成功的记录
                if ($transfer['to'] !== $token || $transfer['contract_ret'] !== 'SUCCESS') {
                    continue;
                }

                // 检查交易是否已处理（通过订单的block_transaction_id字段）
                if (Order::where('block_transaction_id', $transfer['hash'])->exists()) {
                    continue;
                }

                // 计算USDT金额（原系统逻辑：amount除以1000000）
                $amount = floatval($transfer['amount']) / 1000000;

                // 根据钱包地址和金额查找匹配的订单
                $order = $this->findMatchingOrder($token, $amount);
                if (!$order) {
                    continue;
                }

                // 验证区块确认时间必须在订单创建时间之后
                $createTime = strtotime($order->created_at) * 1000; // 转为毫秒
                if ($transfer['block_timestamp'] < $createTime) {
                    error_log("订单 {$order->trade_id} 时间验证失败：交易时间早于订单创建时间");
                    continue;
                }

                // 标记订单为已支付
                $order->markAsPaid($transfer['hash']);

                // 更新钱包统计
                $wallet = WalletAddress::findByToken($token);
                if ($wallet) {
                    $wallet->addReceivedAmount($amount);
                }

                // 发送支付成功回调
                $this->sendPaymentCallback($order);

                $processedCount++;
                
                echo "[" . date('Y-m-d H:i:s') . "] 订单 {$order->trade_id} 支付成功，金额：{$amount} USDT\n";

            } catch (\Exception $e) {
                error_log("处理交易 {$transfer['hash']} 时发生错误: " . $e->getMessage());
            }
        }

        return $processedCount;
    }

    /**
     * 查找匹配的订单
     *
     * @param string $token 钱包地址
     * @param float $amount 支付金额
     * @return Order|null
     */
    private function findMatchingOrder(string $token, float $amount): ?Order
    {
        // 查找匹配的待支付订单（钱包地址和金额都要匹配）
        return Order::where('token', $token)
            ->where('status', Order::STATUS_PENDING)
            ->where('actual_amount', $amount)
            ->where('expired_at', '>', date('Y-m-d H:i:s'))
            ->orderBy('created_at', 'asc')
            ->first();
    }

    /**
     * 获取USDT汇率（参考原系统实现）
     *
     * @return float
     */
    public function getUsdtRate(): float
    {
        try {
            $response = $this->client->get('https://api.coinmarketcap.com/data-api/v3/cryptocurrency/detail/chart', [
                'query' => [
                    'id' => '825',
                    'range' => '1H',
                    'convertId' => '2787'
                ],
                'headers' => [
                    'Accept' => 'application/json'
                ]
            ]);

            $data = json_decode($response->getBody(), true);
            
            if (!$data || $data['status']['error_code'] !== '0') {
                return 0.0;
            }

            foreach ($data['data']['points'] as $points) {
                if (!empty($points['c']) && $points['c'][0] > 0) {
                    return round($points['c'][0], 2);
                }
            }

            return 0.0;
        } catch (RequestException $e) {
            error_log("获取USDT汇率失败: " . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * 发送支付成功回调
     *
     * @param Order $order
     */
    private function sendPaymentCallback(Order $order): void
    {
        if (empty($order->notify_url)) {
            return;
        }

        $callbackData = [
            'trade_id' => $order->trade_id,
            'order_id' => $order->order_id,
            'amount' => $order->amount,
            'actual_amount' => $order->actual_amount,
            'token' => $order->token,
            'block_transaction_id' => $order->block_transaction_id,
            'status' => $order->status
        ];

        // 生成签名（与原系统保持一致）
        $apiToken = SystemConfig::getApiAuthToken();
        ksort($callbackData);
        $signString = '';
        foreach ($callbackData as $key => $value) {
            if ($value !== '' && $value !== null) {
                if ($signString !== '') {
                    $signString .= '&';
                }
                $signString .= "{$key}={$value}";
            }
        }
        $signString .= $apiToken;
        $callbackData['signature'] = md5($signString);

        // 发送回调
        try {
            $response = $this->client->post($order->notify_url, [
                'json' => $callbackData,
                'timeout' => 10,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'USDT-Payment-Callback/1.0'
                ]
            ]);

            $responseBody = $response->getBody()->getContents();
            
            // 检查回调是否成功
            if (trim(strtolower($responseBody)) === 'ok') {
                $order->markCallbackConfirmed();
                echo "[" . date('Y-m-d H:i:s') . "] 订单 {$order->trade_id} 回调成功\n";
            } else {
                $order->incrementCallbackNum();
                echo "[" . date('Y-m-d H:i:s') . "] 订单 {$order->trade_id} 回调失败\n";
            }
        } catch (RequestException $e) {
            error_log("回调失败 订单 {$order->id}: " . $e->getMessage());
            $order->incrementCallbackNum();
        }
    }

    /**
     * 更新所有钱包余额
     */
    public function updateAllWalletBalances(): int
    {
        $wallets = WalletAddress::where('status', 1)->get();
        $count = 0;

        foreach ($wallets as $wallet) {
            try {
                // 使用TronScan API获取余额
                $response = $this->client->get('/api/account', [
                    'query' => ['address' => $wallet->token],
                    'base_uri' => $this->tronScanApiUrl
                ]);

                $data = json_decode($response->getBody()->getContents(), true);
                
                if (isset($data['trc20token_balances'])) {
                    foreach ($data['trc20token_balances'] as $token) {
                        // USDT合约地址 (TRC20)
                        if ($token['tokenId'] === 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t') {
                            $balance = $token['balance'] / 1000000; // USDT精度为6位
                            $wallet->balance = $balance;
                            $wallet->last_check_time = date('Y-m-d H:i:s');
                            $wallet->save();
                            $count++;
                            break;
                        }
                    }
                }
            } catch (\Exception $e) {
                error_log("更新钱包 {$wallet->token} 余额失败: " . $e->getMessage());
            }
        }

        return $count;
    }
}






