<?php

namespace app\process;

use app\service\TronService;
use app\model\Order;
use app\model\SystemConfig;
use Workerman\Timer;

/**
 * 区块链监控进程
 */
class BlockchainMonitor
{
    /**
     * 进程启动时执行
     */
    public function onWorkerStart()
    {
        echo "区块链监控进程启动...\n";
        
        $tronService = new TronService();
        
        // 每30秒检查一次新交易
        Timer::add(30, function() use ($tronService) {
            try {
                $count = $tronService->checkAllWalletTransactions();
                if ($count > 0) {
                    echo "[" . date('Y-m-d H:i:s') . "] 处理了 {$count} 笔新交易\n";
                }
            } catch (\Exception $e) {
                echo "[" . date('Y-m-d H:i:s') . "] 检查交易时发生错误: " . $e->getMessage() . "\n";
            }
        });
        
        // 每5分钟更新一次钱包余额
        Timer::add(300, function() use ($tronService) {
            try {
                $count = $tronService->updateAllWalletBalances();
                echo "[" . date('Y-m-d H:i:s') . "] 更新了 {$count} 个钱包余额\n";
            } catch (\Exception $e) {
                echo "[" . date('Y-m-d H:i:s') . "] 更新余额时发生错误: " . $e->getMessage() . "\n";
            }
        });
        
        // 每分钟检查过期订单
        Timer::add(60, function() {
            try {
                $expiredOrders = Order::where('status', Order::STATUS_PENDING)
                    ->where('expired_at', '<', date('Y-m-d H:i:s'))
                    ->get();
                
                $count = 0;
                foreach ($expiredOrders as $order) {
                    $order->markAsExpired();
                    $count++;
                }
                
                if ($count > 0) {
                    echo "[" . date('Y-m-d H:i:s') . "] 标记了 {$count} 个过期订单\n";
                }
            } catch (\Exception $e) {
                echo "[" . date('Y-m-d H:i:s') . "] 检查过期订单时发生错误: " . $e->getMessage() . "\n";
            }
        });
        
        // 每10分钟重试失败的回调
        Timer::add(600, function() {
            try {
                $failedCallbacks = Order::where('status', Order::STATUS_PAID)
                    ->where('callback_confirm', Order::CALLBACK_UNCONFIRMED)
                    ->where('callback_num', '<', SystemConfig::getCallbackRetryTimes())
                    ->get();
                
                $count = 0;
                foreach ($failedCallbacks as $order) {
                    $this->retryCallback($order);
                    $count++;
                }
                
                if ($count > 0) {
                    echo "[" . date('Y-m-d H:i:s') . "] 重试了 {$count} 个回调\n";
                }
            } catch (\Exception $e) {
                echo "[" . date('Y-m-d H:i:s') . "] 重试回调时发生错误: " . $e->getMessage() . "\n";
            }
        });
    }
    
    /**
     * 重试回调
     *
     * @param Order $order
     */
    private function retryCallback(Order $order): void
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
        
        // 生成签名
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
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $order->notify_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($callbackData),
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'User-Agent: USDT-Payment-Callback/1.0'
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // 更新回调次数
        $order->incrementCallbackNum();
        
        // 检查回调是否成功
        if ($httpCode === 200 && trim(strtolower($response)) === 'ok') {
            $order->markCallbackConfirmed();
            echo "[" . date('Y-m-d H:i:s') . "] 订单 {$order->trade_id} 回调成功\n";
        } else {
            echo "[" . date('Y-m-d H:i:s') . "] 订单 {$order->trade_id} 回调失败 (HTTP: {$httpCode})\n";
        }
    }
}
