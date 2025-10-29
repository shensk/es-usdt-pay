<?php

namespace app\service;

use app\model\Order;
use app\model\WalletAddress;
use app\model\SystemConfig;
use support\Redis;
use Ramsey\Uuid\Uuid;

/**
 * 支付服务类
 */
class PaymentService
{
    /**
     * 创建支付订单
     *
     * @param array $params
     * @return array
     * @throws \Exception
     */
    public static function createOrder(array $params): array
    {
        // 验证参数
        self::validateOrderParams($params);

        // 检查订单是否已存在
        if (Order::findByOrderId($params['order_id'])) {
            throw new \Exception('支付交易已存在，请勿重复创建', 10002);
        }

        // 获取可用钱包地址
        $walletAddress = self::getAvailableWallet($params['amount']);
        if (!$walletAddress) {
            throw new \Exception('无可用钱包地址，无法发起支付', 10003);
        }

        // 计算实际支付金额
        $actualAmount = self::calculateActualAmount($params['amount'], $walletAddress->token);

        // 生成交易ID
        $tradeId = Order::generateTradeId();

        // 计算过期时间
        $expirationTime = date('Y-m-d H:i:s', time() + SystemConfig::getOrderExpirationTime() * 60);

        // 创建订单
        $order = Order::create([
            'trade_id' => $tradeId,
            'order_id' => $params['order_id'],
            'amount' => $params['amount'],
            'actual_amount' => $actualAmount,
            'token' => $walletAddress->token,
            'status' => Order::STATUS_PENDING,
            'notify_url' => $params['notify_url'],
            'redirect_url' => $params['redirect_url'] ?? null,
            'usdt_rate' => SystemConfig::getUsdtRate(),
            'client_ip' => self::getClientIp(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'expired_at' => $expirationTime
        ]);

        // 锁定金额（使用Redis防止并发）
        self::lockAmount($walletAddress->token, $actualAmount, $tradeId);

        // 构建支付URL
        $paymentUrl = self::buildPaymentUrl($tradeId);

        return [
            'trade_id' => $tradeId,
            'order_id' => $params['order_id'],
            'amount' => $params['amount'],
            'actual_amount' => $actualAmount,
            'token' => $walletAddress->token,
            'expiration_time' => strtotime($expirationTime),
            'payment_url' => $paymentUrl
        ];
    }

    /**
     * 验证订单参数
     *
     * @param array $params
     * @throws \Exception
     */
    private static function validateOrderParams(array $params): void
    {
        $required = ['order_id', 'amount', 'notify_url'];
        
        foreach ($required as $field) {
            if (empty($params[$field])) {
                throw new \Exception("参数 {$field} 不能为空", 10009);
            }
        }

        // 验证金额
        $amount = (float)$params['amount'];
        $minAmount = SystemConfig::getMinPaymentAmount();
        $maxAmount = SystemConfig::getMaxPaymentAmount();

        if ($amount < $minAmount) {
            throw new \Exception("支付金额不能小于 {$minAmount} 元", 10004);
        }

        if ($amount > $maxAmount) {
            throw new \Exception("支付金额不能大于 {$maxAmount} 元", 10004);
        }

        // 验证回调URL
        if (!filter_var($params['notify_url'], FILTER_VALIDATE_URL)) {
            throw new \Exception('回调地址格式不正确', 10009);
        }

        if (!empty($params['redirect_url']) && !filter_var($params['redirect_url'], FILTER_VALIDATE_URL)) {
            throw new \Exception('跳转地址格式不正确', 10009);
        }
    }

    /**
     * 获取可用的钱包地址
     *
     * @param float $amount
     * @return WalletAddress|null
     * @throws \Exception
     */
    private static function getAvailableWallet(float $amount): ?WalletAddress
    {
        $wallets = WalletAddress::getAvailableWallets();
        
        if ($wallets->isEmpty()) {
            return null;
        }

        // 计算基础USDT金额
        $usdtRate = SystemConfig::getUsdtRate();
        $baseUsdtAmount = round($amount / $usdtRate, 4);

        // 尝试找到可用的金额通道
        foreach ($wallets as $wallet) {
            for ($i = 0; $i < 100; $i++) {
                $actualAmount = $baseUsdtAmount + ($i * 0.0001);
                $lockKey = "wallet_lock:{$wallet->token}:{$actualAmount}";
                
                // 检查金额是否被锁定
                if (!Redis::exists($lockKey)) {
                    return $wallet;
                }
            }
        }

        throw new \Exception('无可用金额通道', 10005);
    }

    /**
     * 计算实际支付金额
     *
     * @param float $amount CNY金额
     * @param string $walletToken 钱包地址
     * @return float USDT金额
     * @throws \Exception
     */
    private static function calculateActualAmount(float $amount, string $walletToken): float
    {
        $usdtRate = SystemConfig::getUsdtRate();
        $baseUsdtAmount = round($amount / $usdtRate, 4);

        // 尝试找到可用的金额
        for ($i = 0; $i < 100; $i++) {
            $actualAmount = $baseUsdtAmount + ($i * 0.0001);
            $lockKey = "wallet_lock:{$walletToken}:{$actualAmount}";
            
            // 检查金额是否被锁定
            if (!Redis::exists($lockKey)) {
                return $actualAmount;
            }
        }

        throw new \Exception('汇率计算错误', 10006);
    }

    /**
     * 锁定金额
     *
     * @param string $walletToken
     * @param float $amount
     * @param string $tradeId
     */
    private static function lockAmount(string $walletToken, float $amount, string $tradeId): void
    {
        $lockKey = "wallet_lock:{$walletToken}:{$amount}";
        $expirationTime = SystemConfig::getOrderExpirationTime() * 60;
        
        Redis::setex($lockKey, $expirationTime, $tradeId);
    }

    /**
     * 构建支付URL
     *
     * @param string $tradeId
     * @return string
     */
    private static function buildPaymentUrl(string $tradeId): string
    {
        $baseUrl = rtrim($_ENV['APP_URL'] ?? 'http://localhost:8787', '/');
        return "{$baseUrl}/pay/checkout-counter/{$tradeId}";
    }

    /**
     * 获取客户端IP
     *
     * @return string|null
     */
    private static function getClientIp(): ?string
    {
        $headers = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR'
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? null;
    }

    /**
     * 检查订单状态
     *
     * @param string $tradeId
     * @return array
     * @throws \Exception
     */
    public static function checkOrderStatus(string $tradeId): array
    {
        $order = Order::findByTradeId($tradeId);
        
        if (!$order) {
            throw new \Exception('订单不存在', 10008);
        }

        // 检查订单是否过期
        if ($order->status === Order::STATUS_PENDING && $order->isExpired()) {
            $order->markAsExpired();
            // 释放锁定的金额
            self::unlockAmount($order->token, $order->actual_amount);
        }

        return [
            'trade_id' => $order->trade_id,
            'status' => $order->status
        ];
    }

    /**
     * 释放锁定的金额
     *
     * @param string $walletToken
     * @param float $amount
     */
    private static function unlockAmount(string $walletToken, float $amount): void
    {
        $lockKey = "wallet_lock:{$walletToken}:{$amount}";
        Redis::del($lockKey);
    }

    /**
     * 获取收银台数据
     *
     * @param string $tradeId
     * @return array
     * @throws \Exception
     */
    public static function getCheckoutData(string $tradeId): array
    {
        $order = Order::findByTradeId($tradeId);
        
        if (!$order) {
            throw new \Exception('订单不存在', 10008);
        }

        // 检查订单是否过期
        if ($order->status === Order::STATUS_PENDING && $order->isExpired()) {
            $order->markAsExpired();
            // 释放锁定的金额
            self::unlockAmount($order->token, $order->actual_amount);
        }

        return [
            'trade_id' => $order->trade_id,
            'order_id' => $order->order_id,
            'amount' => $order->amount,
            'actual_amount' => $order->actual_amount,
            'token' => $order->token,
            'status' => $order->status,
            'expired_at' => $order->expired_at,
            'redirect_url' => $order->redirect_url
        ];
    }

    /**
     * 验证签名
     *
     * @param array $params
     * @param string $signature
     * @return bool
     */
    public static function verifySignature(array $params, string $signature): bool
    {
        $apiToken = SystemConfig::getApiAuthToken();
        
        // 移除signature参数
        unset($params['signature']);
        
        // 按键名排序
        ksort($params);
        
        // 构建签名字符串
        $signString = '';
        foreach ($params as $key => $value) {
            if ($value !== '' && $value !== null) {
                if ($signString !== '') {
                    $signString .= '&';
                }
                $signString .= "{$key}={$value}";
            }
        }
        
        // 追加API密钥
        $signString .= '&key=' . $apiToken;
        
        // 计算MD5签名
        $calculatedSignature = md5($signString);
        
        return strtolower($calculatedSignature) === strtolower($signature);
    }
}






