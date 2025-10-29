<?php
/**
 * This file is part of webman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

use Webman\Route;

// USDT支付API路由 - 兼容原Epusdt系统
Route::group('/api/v1', function () {
    // 创建支付订单
    Route::post('/order/create-transaction', [app\controller\PaymentController::class, 'createTransaction']);
});

// 支付相关路由
Route::group('/pay', function () {
    // 收银台页面
    Route::get('/checkout-counter/{trade_id}', [app\controller\PaymentController::class, 'checkoutCounter']);
    
    // 检查订单状态
    Route::get('/check-status/{trade_id}', [app\controller\PaymentController::class, 'checkStatus']);
});

// Demo测试路由
Route::group('/demo', function () {
    // Demo首页
    Route::get('/', [app\controller\DemoController::class, 'index']);
    
    // API测试接口
    Route::post('/create-order', [app\controller\DemoController::class, 'createOrder']);
    Route::post('/check-status', [app\controller\DemoController::class, 'checkStatus']);
    Route::post('/get-checkout-data', [app\controller\DemoController::class, 'getCheckoutData']);
    Route::post('/get-config', [app\controller\DemoController::class, 'getConfig']);
    Route::post('/get-wallets', [app\controller\DemoController::class, 'getWallets']);
    Route::post('/get-orders', [app\controller\DemoController::class, 'getOrders']);
    Route::post('/manual-callback', [app\controller\DemoController::class, 'manualCallback']);
    Route::post('/receive-callback', [app\controller\DemoController::class, 'receiveCallback']);
    Route::post('/get-callback-logs', [app\controller\DemoController::class, 'getCallbackLogs']);
});






