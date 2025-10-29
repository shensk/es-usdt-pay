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

use plugin\admin\app\controller\AccountController;
use plugin\admin\app\controller\DictController;
use Webman\Route;
use support\Request;

Route::any('/app/admin/account/captcha/{type}', [AccountController::class, 'captcha']);

Route::any('/app/admin/dict/get/{name}', [DictController::class, 'get']);

// USDT支付系统管理路由
Route::group('/app/admin', function () {
    // 订单管理 - 使用CRUD自动路由
    Route::any('/order/index', [plugin\admin\app\controller\OrderController::class, 'index']);
    Route::any('/order/select', [plugin\admin\app\controller\OrderController::class, 'select']);
    Route::any('/order/insert', [plugin\admin\app\controller\OrderController::class, 'insert']);
    Route::any('/order/update', [plugin\admin\app\controller\OrderController::class, 'update']);
    Route::any('/order/delete', [plugin\admin\app\controller\OrderController::class, 'delete']);
    
    // 系统配置 - 自定义配置页面
    Route::any('/system-config/index', [plugin\admin\app\controller\SystemConfigController::class, 'index']);
    Route::any('/system-config/update', [plugin\admin\app\controller\SystemConfigController::class, 'update']);
    Route::any('/system-config/clear-cache', [plugin\admin\app\controller\SystemConfigController::class, 'clearCache']);
});

Route::fallback(function (Request $request) {
    return response($request->uri() . ' not found' , 404);
}, 'admin');
