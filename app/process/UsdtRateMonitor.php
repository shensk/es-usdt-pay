<?php

namespace app\process;

use app\service\TronService;
use app\model\SystemConfig;
use Workerman\Timer;

/**
 * USDT汇率监控进程
 * 参考原Epusdt系统实现
 */
class UsdtRateMonitor
{
    /**
     * 进程启动时执行
     */
    public function onWorkerStart()
    {
        echo "USDT汇率监控进程启动...\n";
        
        $tronService = new TronService();
        
        // 每30分钟更新一次USDT汇率
        Timer::add(1800, function() use ($tronService) {
            try {
                $rate = $tronService->getUsdtRate();
                if ($rate > 0) {
                    // 更新系统配置中的汇率
                    $config = SystemConfig::where('key', 'usdt_rate')->first();
                    if ($config) {
                        $oldRate = $config->value;
                        $config->value = $rate;
                        $config->save();
                        
                        echo "[" . date('Y-m-d H:i:s') . "] USDT汇率更新: {$oldRate} -> {$rate}\n";
                    }
                    
                    // 清除配置缓存
                    SystemConfig::clearCache();
                } else {
                    echo "[" . date('Y-m-d H:i:s') . "] USDT汇率获取失败\n";
                }
            } catch (\Exception $e) {
                echo "[" . date('Y-m-d H:i:s') . "] USDT汇率更新时发生错误: " . $e->getMessage() . "\n";
            }
        });
        
        // 启动时立即执行一次
        Timer::add(5, function() use ($tronService) {
            try {
                $rate = $tronService->getUsdtRate();
                if ($rate > 0) {
                    $config = SystemConfig::where('key', 'usdt_rate')->first();
                    if ($config) {
                        $config->value = $rate;
                        $config->save();
                        SystemConfig::clearCache();
                        echo "[" . date('Y-m-d H:i:s') . "] 初始USDT汇率: {$rate}\n";
                    }
                }
            } catch (\Exception $e) {
                echo "[" . date('Y-m-d H:i:s') . "] 初始汇率获取失败: " . $e->getMessage() . "\n";
            }
        }, [], false); // false表示只执行一次
    }
}
