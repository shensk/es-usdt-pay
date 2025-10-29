<?php

namespace plugin\admin\app\controller;

use app\model\SystemConfig;
use plugin\admin\app\controller\Base;
use support\Request;
use support\Response;

/**
 * USDT支付系统配置管理控制器
 */
class SystemConfigController extends Base
{
    /**
     * USDT支付系统配置页面
     */
    public function index(Request $request): Response
    {
        $configs = SystemConfig::orderBy('group')->orderBy('sort')->get();
        
        // 按分组整理配置
        $groupedConfigs = [];
        foreach ($configs as $config) {
            $groupedConfigs[$config->group][] = $config;
        }
        
        return raw_view('system-config/index', ['groupedConfigs' => $groupedConfigs]);
    }

    /**
     * 更新配置
     */
    public function update(Request $request): Response
    {
        $configs = $request->post('configs', []);
        
        if (empty($configs)) {
            return $this->json(1, '没有配置需要更新');
        }
        
        $successCount = 0;
        foreach ($configs as $key => $value) {
            $config = SystemConfig::where('key', $key)->first();
            if ($config) {
                $config->value = $value;
                if ($config->save()) {
                    $successCount++;
                }
            }
        }
        
        // 清除配置缓存
        SystemConfig::clearCache();
        
        return $this->json(0, "成功更新 {$successCount} 项配置");
    }

    /**
     * 清除配置缓存
     */
    public function clearCache(Request $request): Response
    {
        SystemConfig::clearCache();
        return $this->json(0, '缓存清除成功');
    }
}






