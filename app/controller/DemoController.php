<?php

namespace app\controller;

use support\Request;
use support\Response;
use app\service\PaymentService;

/**
 * Demo控制器 - 用于测试所有API接口
 */
class DemoController
{
    /**
     * API测试页面
     */
    public function index(Request $request): Response
    {
        return view('demo/index');
    }

    /**
     * 创建支付订单测试
     */
    public function createOrder(Request $request): Response
    {
        try {
            $amount = $request->post('amount', '100.00');
            $orderId = 'DEMO_' . date('YmdHis') . '_' . rand(1000, 9999);
            $notifyUrl = $request->post('notify_url', 'http://localhost/callback');
            $redirectUrl = $request->post('redirect_url', 'http://localhost/success');
            
            // 生成签名
            $apiToken = 'usdt-payment-token-2024-secure-key';
            $signString = "amount={$amount}&notify_url={$notifyUrl}&order_id={$orderId}&redirect_url={$redirectUrl}&key={$apiToken}";
            $signature = md5($signString);
            
            $data = [
                'amount' => $amount,
                'order_id' => $orderId,
                'notify_url' => $notifyUrl,
                'redirect_url' => $redirectUrl,
                'signature' => $signature
            ];
            
            $result = PaymentService::createOrder($data);
            
            return json([
                'success' => true,
                'data' => $result,
                'request_data' => $data,
                'sign_string' => $signString
            ]);
            
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 查询订单状态测试
     */
    public function checkStatus(Request $request): Response
    {
        try {
            $orderId = $request->post('order_id');
            
            if (empty($orderId)) {
                return json([
                    'success' => false,
                    'error' => '请输入订单ID'
                ]);
            }
            
            // 通过订单ID查找订单
            $order = \app\model\Order::where('order_id', $orderId)->first();
            if (!$order) {
                return json([
                    'success' => false,
                    'error' => '订单不存在'
                ]);
            }
            
            $result = [
                'trade_id' => $order->trade_id,
                'order_id' => $order->order_id,
                'amount' => $order->amount,
                'actual_amount' => $order->actual_amount,
                'token' => $order->token,
                'status' => $order->status,
                'status_text' => $this->getStatusText($order->status),
                'block_transaction_id' => $order->block_transaction_id ?: '',
                'created_at' => $order->created_at,
                'paid_at' => $order->paid_at ?: '',
                'expired_at' => $order->expired_at
            ];
            
            return json([
                'success' => true,
                'data' => $result
            ]);
            
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 获取状态文本
     */
    private function getStatusText($status): string
    {
        switch ($status) {
            case 1: return '等待支付';
            case 2: return '支付成功';
            case 3: return '已过期';
            case 4: return '已取消';
            default: return '未知状态';
        }
    }

    /**
     * 获取支付页面数据测试
     */
    public function getCheckoutData(Request $request): Response
    {
        try {
            $tradeId = $request->post('trade_id');
            
            if (empty($tradeId)) {
                return json([
                    'success' => false,
                    'error' => '请输入交易ID'
                ]);
            }
            
            $result = PaymentService::getCheckoutData($tradeId);
            
            return json([
                'success' => true,
                'data' => $result
            ]);
            
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 系统配置测试
     */
    public function getConfig(Request $request): Response
    {
        try {
            $configs = [
                'api_auth_token' => \app\model\SystemConfig::get('api_auth_token'),
                'usdt_rate' => \app\model\SystemConfig::get('usdt_rate'),
                'order_expiration_time' => \app\model\SystemConfig::get('order_expiration_time'),
                'min_payment_amount' => \app\model\SystemConfig::get('min_payment_amount'),
                'max_payment_amount' => \app\model\SystemConfig::get('max_payment_amount'),
                'callback_retry_times' => \app\model\SystemConfig::get('callback_retry_times'),
                'block_confirmations' => \app\model\SystemConfig::get('block_confirmations'),
            ];
            
            return json([
                'success' => true,
                'data' => $configs
            ]);
            
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 钱包地址列表测试
     */
    public function getWallets(Request $request): Response
    {
        try {
            $wallets = \app\model\WalletAddress::where('status', 1)->get();
            
            return json([
                'success' => true,
                'data' => $wallets
            ]);
            
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 订单列表测试
     */
    public function getOrders(Request $request): Response
    {
        try {
            $page = $request->post('page', 1);
            $limit = $request->post('limit', 10);
            
            $orders = \app\model\Order::orderBy('created_at', 'desc')
                ->offset(($page - 1) * $limit)
                ->limit($limit)
                ->get();
            
            $total = \app\model\Order::count();
            
            return json([
                'success' => true,
                'data' => [
                    'orders' => $orders,
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit
                ]
            ]);
            
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 手动回调测试
     */
    public function manualCallback(Request $request): Response
    {
        try {
            $tradeId = $request->post('trade_id');
            $callbackUrl = $request->post('callback_url');
            
            if (empty($tradeId)) {
                return json([
                    'success' => false,
                    'error' => '请输入交易ID'
                ]);
            }
            
            if (empty($callbackUrl)) {
                return json([
                    'success' => false,
                    'error' => '请输入回调地址'
                ]);
            }
            
            // 查找订单
            $order = \app\model\Order::where('trade_id', $tradeId)->first();
            if (!$order) {
                return json([
                    'success' => false,
                    'error' => '订单不存在'
                ]);
            }
            
            // 构建回调数据
            $callbackData = [
                'trade_id' => $order->trade_id,
                'order_id' => $order->order_id,
                'amount' => $order->amount,
                'actual_amount' => $order->actual_amount,
                'token' => $order->token,
                'status' => $order->status,
                'block_transaction_id' => $order->block_transaction_id ?: '',
                'created_at' => $order->created_at,
                'paid_at' => $order->paid_at ?: ''
            ];
            
            // 生成签名
            $apiToken = \app\model\SystemConfig::get('api_auth_token');
            $signString = http_build_query($callbackData) . '&key=' . $apiToken;
            $callbackData['signature'] = md5($signString);
            
            // 如果是本地测试地址，直接模拟回调处理
            if (strpos($callbackUrl, 'localhost:8787/demo/receive-callback') !== false || 
                strpos($callbackUrl, '127.0.0.1:8787/demo/receive-callback') !== false) {
                
                // 直接模拟回调处理逻辑，不发送HTTP请求
                $receivedTime = date('Y-m-d H:i:s');
                
                // 验证签名
                $signature = $callbackData['signature'];
                $tempData = $callbackData;
                unset($tempData['signature']);
                
                $expectedSignature = md5(http_build_query($tempData) . '&key=' . $apiToken);
                $signatureValid = strtolower($signature) === strtolower($expectedSignature);
                
                // 模拟商户处理结果
                if ($signatureValid) {
                    $businessResult = '订单处理成功，商户系统已更新订单状态';
                    $responseBody = 'SUCCESS';
                    $statusCode = 200;
                    
                    // 记录成功日志
                    $logData = [
                        'timestamp' => $receivedTime,
                        'trade_id' => $callbackData['trade_id'],
                        'order_id' => $callbackData['order_id'],
                        'status' => $callbackData['status'],
                        'signature_valid' => true,
                        'callback_data' => $callbackData
                    ];
                    
                    $logFile = runtime_path() . '/logs/callback_test.log';
                    $logDir = dirname($logFile);
                    if (!is_dir($logDir)) {
                        mkdir($logDir, 0755, true);
                    }
                    file_put_contents($logFile, json_encode($logData, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);
                    
                } else {
                    $businessResult = '签名验证失败，拒绝处理';
                    $responseBody = 'FAIL - Invalid signature';
                    $statusCode = 400;
                }
                
                return json([
                    'success' => true,
                    'data' => [
                        'callback_data' => $callbackData,
                        'response_status' => $statusCode,
                        'response_body' => $responseBody,
                        'sign_string' => $signString,
                        'callback_type' => 'LOCAL_SIMULATION',
                        'callback_result' => [
                            'received_time' => $receivedTime,
                            'signature_valid' => $signatureValid,
                            'business_result' => $businessResult
                        ]
                    ]
                ]);
                
            } else {
                // 外部地址才发送HTTP请求
                try {
                    $client = new \GuzzleHttp\Client(['timeout' => 10]);
                    $response = $client->post($callbackUrl, [
                        'form_params' => $callbackData,
                        'headers' => [
                            'User-Agent' => 'USDT-Payment-Callback/1.0'
                        ]
                    ]);
                    
                    $responseBody = $response->getBody()->getContents();
                    $statusCode = $response->getStatusCode();
                    
                    return json([
                        'success' => true,
                        'data' => [
                            'callback_data' => $callbackData,
                            'response_status' => $statusCode,
                            'response_body' => $responseBody,
                            'sign_string' => $signString,
                            'callback_type' => 'HTTP_REQUEST'
                        ]
                    ]);
                    
                } catch (\Exception $httpException) {
                    return json([
                        'success' => false,
                        'error' => 'HTTP请求失败: ' . $httpException->getMessage(),
                        'callback_data' => $callbackData,
                        'sign_string' => $signString
                    ]);
                }
            }
            
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 接收回调测试 - 模拟商户接收回调
     */
    public function receiveCallback(Request $request): Response
    {
        try {
            // 记录接收到的回调数据
            $callbackData = $request->post();
            $receivedTime = date('Y-m-d H:i:s');
            
            // 验证必要参数
            $requiredFields = ['trade_id', 'order_id', 'amount', 'actual_amount', 'status', 'signature'];
            $missingFields = [];
            
            foreach ($requiredFields as $field) {
                if (!isset($callbackData[$field]) || $callbackData[$field] === '') {
                    $missingFields[] = $field;
                }
            }
            
            if (!empty($missingFields)) {
                return response('FAIL - Missing fields: ' . implode(', ', $missingFields), 400);
            }
            
            // 验证签名
            $signature = $callbackData['signature'];
            unset($callbackData['signature']);
            
            $apiToken = \app\model\SystemConfig::get('api_auth_token');
            $signString = http_build_query($callbackData) . '&key=' . $apiToken;
            $expectedSignature = md5($signString);
            
            $signatureValid = strtolower($signature) === strtolower($expectedSignature);
            
            // 构建响应数据
            $responseData = [
                'received_time' => $receivedTime,
                'callback_data' => $callbackData,
                'signature_received' => $signature,
                'signature_expected' => $expectedSignature,
                'signature_valid' => $signatureValid,
                'sign_string' => $signString,
                'status_text' => $this->getStatusText($callbackData['status']),
                'validation_result' => $signatureValid ? 'SUCCESS' : 'SIGNATURE_INVALID'
            ];
            
            // 记录到日志文件（可选）
            $logData = [
                'timestamp' => $receivedTime,
                'trade_id' => $callbackData['trade_id'],
                'order_id' => $callbackData['order_id'],
                'status' => $callbackData['status'],
                'signature_valid' => $signatureValid,
                'callback_data' => $callbackData
            ];
            
            // 写入日志文件
            $logFile = runtime_path() . '/logs/callback_test.log';
            $logDir = dirname($logFile);
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }
            file_put_contents($logFile, json_encode($logData, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);
            
            // 模拟商户的业务处理
            if ($signatureValid) {
                // 签名验证成功，处理订单状态
                switch ($callbackData['status']) {
                    case 2: // 支付成功
                        $businessResult = '订单支付成功，商户系统已更新订单状态';
                        break;
                    case 3: // 已过期
                        $businessResult = '订单已过期，商户系统已标记订单';
                        break;
                    case 4: // 已取消
                        $businessResult = '订单已取消，商户系统已处理';
                        break;
                    default:
                        $businessResult = '订单状态未知，商户系统待处理';
                }
                
                $responseData['business_result'] = $businessResult;
                
                // 返回SUCCESS给支付系统
                return response('SUCCESS', 200, [
                    'Content-Type' => 'text/plain',
                    'X-Callback-Result' => json_encode($responseData, JSON_UNESCAPED_UNICODE)
                ]);
                
            } else {
                // 签名验证失败
                $responseData['business_result'] = '签名验证失败，拒绝处理';
                
                return response('FAIL - Invalid signature', 400, [
                    'Content-Type' => 'text/plain',
                    'X-Callback-Result' => json_encode($responseData, JSON_UNESCAPED_UNICODE)
                ]);
            }
            
        } catch (\Exception $e) {
            $errorData = [
                'received_time' => date('Y-m-d H:i:s'),
                'error' => $e->getMessage(),
                'callback_data' => $request->post()
            ];
            
            // 记录错误日志
            $logFile = runtime_path() . '/logs/callback_error.log';
            $logDir = dirname($logFile);
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }
            file_put_contents($logFile, json_encode($errorData, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);
            
            return response('ERROR - ' . $e->getMessage(), 500, [
                'Content-Type' => 'text/plain'
            ]);
        }
    }

    /**
     * 查看回调日志
     */
    public function getCallbackLogs(Request $request): Response
    {
        try {
            $logFile = runtime_path() . '/logs/callback_test.log';
            $errorLogFile = runtime_path() . '/logs/callback_error.log';
            
            $logs = [];
            $errorLogs = [];
            
            // 读取成功日志
            if (file_exists($logFile)) {
                $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach (array_reverse(array_slice($lines, -20)) as $line) { // 最近20条
                    $logs[] = json_decode($line, true);
                }
            }
            
            // 读取错误日志
            if (file_exists($errorLogFile)) {
                $lines = file($errorLogFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach (array_reverse(array_slice($lines, -10)) as $line) { // 最近10条
                    $errorLogs[] = json_decode($line, true);
                }
            }
            
            return json([
                'success' => true,
                'data' => [
                    'callback_logs' => $logs,
                    'error_logs' => $errorLogs,
                    'log_file' => $logFile,
                    'error_log_file' => $errorLogFile
                ]
            ]);
            
        } catch (\Exception $e) {
            return json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}
