<?php

$tradeId = '20251028123729192528'; // 刚才创建的订单
$url = "http://localhost:8787/pay/checkout-counter/{$tradeId}";

echo "测试支付页面: {$url}\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP状态码: {$httpCode}\n";

if ($error) {
    echo "CURL错误: {$error}\n";
} else {
    echo "响应长度: " . strlen($response) . " 字节\n";
    
    if (strpos($response, '订单不存在') !== false) {
        echo "❌ 显示：订单不存在\n";
    } elseif (strpos($response, '错误') !== false) {
        echo "❌ 显示错误信息\n";
        // 提取错误信息
        if (preg_match('/<h1>错误：(.+?)<\/h1>/', $response, $matches)) {
            echo "错误详情: {$matches[1]}\n";
        }
    } elseif (strpos($response, 'html') !== false || strpos($response, 'HTML') !== false) {
        echo "✅ 返回HTML页面（可能是正常的支付页面）\n";
        
        // 检查是否包含支付相关信息
        if (strpos($response, 'USDT') !== false) {
            echo "✅ 页面包含USDT信息\n";
        }
        if (strpos($response, $tradeId) !== false) {
            echo "✅ 页面包含交易ID\n";
        }
    } else {
        echo "响应内容预览:\n";
        echo substr($response, 0, 500) . "\n";
    }
}
