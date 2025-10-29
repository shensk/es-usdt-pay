# USDT支付系统 - API对接文档

## 概述

本文档详细介绍了USDT支付系统的API接口，包括订单创建、状态查询、回调处理等功能。系统基于TRC20网络的USDT进行支付处理。

### 基本信息
- **API版本**: v1
- **请求协议**: HTTP/HTTPS
- **数据格式**: JSON
- **字符编码**: UTF-8
- **签名算法**: MD5

### 接口地址
- **生产环境**: `https://your-domain.com/api/v1/`
- **测试环境**: `http://your-test-domain.com/api/v1/`

## 1. 接口认证

### 1.1 签名机制

所有API请求都需要进行签名验证，签名算法如下：

1. 将所有请求参数（除signature外）按参数名ASCII码从小到大排序
2. 使用URL键值对格式拼接成字符串
3. 在字符串末尾拼接`&key=API_TOKEN`
4. 对整个字符串进行MD5加密（32位小写）

### 1.2 签名示例

**请求参数**：
```json
{
    "order_id": "ORDER123456",
    "amount": 100.00,
    "notify_url": "https://merchant.com/callback"
}
```

**签名步骤**：
```
1. 排序后: amount=100.00&notify_url=https://merchant.com/callback&order_id=ORDER123456
2. 拼接密钥: amount=100.00&notify_url=https://merchant.com/callback&order_id=ORDER123456&key=your_api_token
3. MD5加密: signature = md5("amount=100.00&notify_url=https://merchant.com/callback&order_id=ORDER123456&key=your_api_token")
```

### 1.3 API密钥配置

在系统配置中设置您的API密钥：
- 登录管理后台
- 进入"系统配置" → "API配置"
- 设置"API认证密钥"

## 2. 创建支付订单

### 2.1 接口信息

- **接口地址**: `/order/create-transaction`
- **请求方式**: POST
- **Content-Type**: application/json

### 2.2 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| order_id | string | 是 | 商户订单号，唯一标识 |
| amount | decimal | 是 | 订单金额（人民币），精确到分 |
| notify_url | string | 是 | 异步回调通知地址 |
| redirect_url | string | 否 | 支付完成后跳转地址 |
| signature | string | 是 | 签名字符串 |

### 2.3 请求示例

```bash
curl -X POST https://your-domain.com/api/v1/order/create-transaction \
  -H "Content-Type: application/json" \
  -d '{
    "order_id": "ORDER20231201001",
    "amount": 100.50,
    "notify_url": "https://merchant.com/usdt/callback",
    "redirect_url": "https://merchant.com/success",
    "signature": "a1b2c3d4e5f6789012345678901234567"
  }'
```

### 2.4 响应参数

| 参数名 | 类型 | 说明 |
|--------|------|------|
| status_code | int | 状态码，200表示成功 |
| message | string | 响应消息 |
| data | object | 响应数据 |

**data对象结构**：

| 参数名 | 类型 | 说明 |
|--------|------|------|
| trade_id | string | 系统交易号 |
| order_id | string | 商户订单号 |
| amount | decimal | 订单金额（CNY） |
| actual_amount | decimal | 实际支付金额（USDT） |
| token | string | 收款钱包地址 |
| expired_at | string | 订单过期时间 |
| qr_code | string | 支付二维码内容 |
| checkout_url | string | 支付页面地址 |

### 2.5 成功响应示例

```json
{
    "status_code": 200,
    "message": "订单创建成功",
    "data": {
        "trade_id": "T20231201123456789",
        "order_id": "ORDER20231201001",
        "amount": 100.50,
        "actual_amount": 15.2341,
        "token": "TQn9Y2khEsLJW1ChVWFMSMeRDow5oNDMH8",
        "expired_at": "2023-12-01 15:30:00",
        "qr_code": "TQn9Y2khEsLJW1ChVWFMSMeRDow5oNDMH8",
        "checkout_url": "https://your-domain.com/checkout/T20231201123456789"
    }
}
```

### 2.6 错误响应示例

```json
{
    "status_code": 400,
    "message": "订单号已存在",
    "data": null
}
```

## 3. 查询订单状态

### 3.1 接口信息

- **接口地址**: `/order/query-transaction`
- **请求方式**: POST
- **Content-Type**: application/json

### 3.2 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| trade_id | string | 是 | 系统交易号 |
| signature | string | 是 | 签名字符串 |

### 3.3 请求示例

```bash
curl -X POST https://your-domain.com/api/v1/order/query-transaction \
  -H "Content-Type: application/json" \
  -d '{
    "trade_id": "T20231201123456789",
    "signature": "a1b2c3d4e5f6789012345678901234567"
  }'
```

### 3.4 响应参数

| 参数名 | 类型 | 说明 |
|--------|------|------|
| status_code | int | 状态码，200表示成功 |
| message | string | 响应消息 |
| data | object | 订单详情 |

**data对象结构**：

| 参数名 | 类型 | 说明 |
|--------|------|------|
| trade_id | string | 系统交易号 |
| order_id | string | 商户订单号 |
| amount | decimal | 订单金额（CNY） |
| actual_amount | decimal | 实际支付金额（USDT） |
| token | string | 收款钱包地址 |
| status | int | 订单状态：1-待支付，2-已支付，3-已过期，4-已取消 |
| block_transaction_id | string | 区块链交易哈希 |
| created_at | string | 创建时间 |
| paid_at | string | 支付时间 |
| expired_at | string | 过期时间 |

### 3.5 成功响应示例

```json
{
    "status_code": 200,
    "message": "查询成功",
    "data": {
        "trade_id": "T20231201123456789",
        "order_id": "ORDER20231201001",
        "amount": 100.50,
        "actual_amount": 15.2341,
        "token": "TQn9Y2khEsLJW1ChVWFMSMeRDow5oNDMH8",
        "status": 2,
        "block_transaction_id": "a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0",
        "created_at": "2023-12-01 15:00:00",
        "paid_at": "2023-12-01 15:10:30",
        "expired_at": "2023-12-01 15:30:00"
    }
}
```

## 4. 获取支付页面数据

### 4.1 接口信息

- **接口地址**: `/order/checkout-counter`
- **请求方式**: POST
- **Content-Type**: application/json

### 4.2 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| trade_id | string | 是 | 系统交易号 |
| signature | string | 是 | 签名字符串 |

### 4.3 响应参数

包含支付页面所需的完整数据，包括订单信息、二维码、倒计时等。

### 4.4 成功响应示例

```json
{
    "status_code": 200,
    "message": "获取成功",
    "data": {
        "trade_id": "T20231201123456789",
        "order_id": "ORDER20231201001",
        "amount": 100.50,
        "actual_amount": 15.2341,
        "token": "TQn9Y2khEsLJW1ChVWFMSMeRDow5oNDMH8",
        "status": 1,
        "qr_code": "TQn9Y2khEsLJW1ChVWFMSMeRDow5oNDMH8",
        "expired_at": "2023-12-01 15:30:00",
        "remaining_time": 1200
    }
}
```

## 5. 异步回调通知

### 5.1 回调机制

当订单状态发生变化时（如支付成功），系统会向商户提供的`notify_url`发送POST请求。

### 5.2 回调参数

| 参数名 | 类型 | 说明 |
|--------|------|------|
| trade_id | string | 系统交易号 |
| order_id | string | 商户订单号 |
| amount | decimal | 订单金额（CNY） |
| actual_amount | decimal | 实际支付金额（USDT） |
| token | string | 收款钱包地址 |
| status | int | 订单状态 |
| block_transaction_id | string | 区块链交易哈希 |
| signature | string | 签名字符串 |

### 5.3 回调示例

```json
{
    "trade_id": "T20231201123456789",
    "order_id": "ORDER20231201001",
    "amount": 100.50,
    "actual_amount": 15.2341,
    "token": "TQn9Y2khEsLJW1ChVWFMSMeRDow5oNDMH8",
    "status": 2,
    "block_transaction_id": "a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0",
    "signature": "a1b2c3d4e5f6789012345678901234567"
}
```

### 5.4 回调处理

**商户需要：**
1. 验证签名是否正确
2. 检查订单状态是否为已支付（status=2）
3. 验证订单金额是否正确
4. 处理业务逻辑（发货、充值等）
5. 返回字符串"OK"表示处理成功

**PHP示例代码**：
```php
<?php
// 接收回调数据
$data = json_decode(file_get_contents('php://input'), true);

// 验证签名
$signature = $data['signature'];
unset($data['signature']);
ksort($data);

$signString = '';
foreach ($data as $key => $value) {
    if ($value !== '' && $value !== null) {
        if ($signString !== '') {
            $signString .= '&';
        }
        $signString .= "{$key}={$value}";
    }
}
$signString .= '&key=' . $apiToken;
$expectedSignature = md5($signString);

if (strtolower($signature) !== strtolower($expectedSignature)) {
    exit('FAIL - Invalid signature');
}

// 检查订单状态
if ($data['status'] == 2) {
    // 订单已支付，处理业务逻辑
    $orderId = $data['order_id'];
    $amount = $data['amount'];
    
    // 更新订单状态、发货等业务处理
    // ...
    
    echo 'OK';  // 必须返回OK
} else {
    echo 'FAIL - Invalid status';
}
?>
```

### 5.5 重试机制

- 如果商户未返回"OK"，系统会重试发送回调
- 重试间隔：1分钟、5分钟、10分钟、30分钟、1小时
- 最大重试次数：5次
- 超过重试次数后，需要商户手动处理

## 6. 状态码说明

### 6.1 HTTP状态码

| 状态码 | 说明 |
|--------|------|
| 200 | 请求成功 |
| 400 | 请求参数错误 |
| 401 | 签名验证失败 |
| 404 | 接口不存在 |
| 500 | 服务器内部错误 |

### 6.2 业务状态码

| 状态码 | 说明 |
|--------|------|
| 200 | 成功 |
| 400 | 参数错误 |
| 401 | 签名验证失败 |
| 404 | 订单不存在 |
| 409 | 订单号重复 |
| 500 | 系统错误 |

### 6.3 订单状态

| 状态值 | 说明 |
|--------|------|
| 1 | 待支付 |
| 2 | 已支付 |
| 3 | 已过期 |
| 4 | 已取消 |

## 7. 错误处理

### 7.1 常见错误

**签名验证失败**：
```json
{
    "status_code": 401,
    "message": "签名验证失败",
    "data": null
}
```

**订单号重复**：
```json
{
    "status_code": 409,
    "message": "订单号已存在",
    "data": null
}
```

**订单不存在**：
```json
{
    "status_code": 404,
    "message": "订单不存在",
    "data": null
}
```

**参数错误**：
```json
{
    "status_code": 400,
    "message": "参数错误：amount必须大于0",
    "data": null
}
```

### 7.2 错误处理建议

1. **网络错误**：实现重试机制，建议重试3次
2. **签名错误**：检查签名算法和API密钥
3. **订单重复**：使用唯一的订单号
4. **系统错误**：联系技术支持

## 8. SDK示例

### 8.1 PHP SDK

```php
<?php
class UsdtPaymentSDK
{
    private $apiUrl;
    private $apiToken;
    
    public function __construct($apiUrl, $apiToken)
    {
        $this->apiUrl = rtrim($apiUrl, '/');
        $this->apiToken = $apiToken;
    }
    
    /**
     * 创建支付订单
     */
    public function createOrder($orderId, $amount, $notifyUrl, $redirectUrl = '')
    {
        $params = [
            'order_id' => $orderId,
            'amount' => $amount,
            'notify_url' => $notifyUrl,
            'redirect_url' => $redirectUrl
        ];
        
        $params['signature'] = $this->generateSignature($params);
        
        return $this->request('/order/create-transaction', $params);
    }
    
    /**
     * 查询订单状态
     */
    public function queryOrder($tradeId)
    {
        $params = [
            'trade_id' => $tradeId
        ];
        
        $params['signature'] = $this->generateSignature($params);
        
        return $this->request('/order/query-transaction', $params);
    }
    
    /**
     * 生成签名
     */
    private function generateSignature($params)
    {
        ksort($params);
        
        $signString = '';
        foreach ($params as $key => $value) {
            if ($value !== '' && $value !== null) {
                if ($signString !== '') {
                    $signString .= '&';
                }
                $signString .= "{$key}={$value}";
            }
        }
        
        $signString .= '&key=' . $this->apiToken;
        
        return md5($signString);
    }
    
    /**
     * 发送HTTP请求
     */
    private function request($endpoint, $params)
    {
        $url = $this->apiUrl . '/api/v1' . $endpoint;
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($params),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json'
            ],
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("HTTP Error: {$httpCode}");
        }
        
        return json_decode($response, true);
    }
}

// 使用示例
$sdk = new UsdtPaymentSDK('https://your-domain.com', 'your_api_token');

try {
    // 创建订单
    $result = $sdk->createOrder(
        'ORDER20231201001',
        100.50,
        'https://merchant.com/callback',
        'https://merchant.com/success'
    );
    
    if ($result['status_code'] === 200) {
        echo "订单创建成功，支付地址：" . $result['data']['checkout_url'];
    } else {
        echo "订单创建失败：" . $result['message'];
    }
    
    // 查询订单
    $tradeId = $result['data']['trade_id'];
    $queryResult = $sdk->queryOrder($tradeId);
    
    echo "订单状态：" . $queryResult['data']['status'];
    
} catch (Exception $e) {
    echo "请求失败：" . $e->getMessage();
}
?>
```

### 8.2 Node.js SDK

```javascript
const crypto = require('crypto');
const axios = require('axios');

class UsdtPaymentSDK {
    constructor(apiUrl, apiToken) {
        this.apiUrl = apiUrl.replace(/\/$/, '');
        this.apiToken = apiToken;
    }
    
    // 创建支付订单
    async createOrder(orderId, amount, notifyUrl, redirectUrl = '') {
        const params = {
            order_id: orderId,
            amount: amount,
            notify_url: notifyUrl,
            redirect_url: redirectUrl
        };
        
        params.signature = this.generateSignature(params);
        
        return await this.request('/order/create-transaction', params);
    }
    
    // 查询订单状态
    async queryOrder(tradeId) {
        const params = {
            trade_id: tradeId
        };
        
        params.signature = this.generateSignature(params);
        
        return await this.request('/order/query-transaction', params);
    }
    
    // 生成签名
    generateSignature(params) {
        const sortedKeys = Object.keys(params).sort();
        
        let signString = '';
        for (const key of sortedKeys) {
            const value = params[key];
            if (value !== '' && value !== null && value !== undefined) {
                if (signString !== '') {
                    signString += '&';
                }
                signString += `${key}=${value}`;
            }
        }
        
        signString += `&key=${this.apiToken}`;
        
        return crypto.createHash('md5').update(signString).digest('hex');
    }
    
    // 发送HTTP请求
    async request(endpoint, params) {
        const url = `${this.apiUrl}/api/v1${endpoint}`;
        
        try {
            const response = await axios.post(url, params, {
                headers: {
                    'Content-Type': 'application/json'
                },
                timeout: 30000
            });
            
            return response.data;
        } catch (error) {
            throw new Error(`Request failed: ${error.message}`);
        }
    }
}

// 使用示例
const sdk = new UsdtPaymentSDK('https://your-domain.com', 'your_api_token');

(async () => {
    try {
        // 创建订单
        const result = await sdk.createOrder(
            'ORDER20231201001',
            100.50,
            'https://merchant.com/callback',
            'https://merchant.com/success'
        );
        
        if (result.status_code === 200) {
            console.log('订单创建成功，支付地址：', result.data.checkout_url);
            
            // 查询订单
            const queryResult = await sdk.queryOrder(result.data.trade_id);
            console.log('订单状态：', queryResult.data.status);
        } else {
            console.log('订单创建失败：', result.message);
        }
    } catch (error) {
        console.error('请求失败：', error.message);
    }
})();
```

## 9. 测试工具

### 9.1 在线测试

访问系统提供的测试页面：
- **测试地址**: `https://your-domain.com/demo`
- **功能**: 创建订单、查询状态、模拟回调等

### 9.2 Postman集合

导入以下Postman集合进行API测试：

```json
{
    "info": {
        "name": "USDT Payment API",
        "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
    },
    "item": [
        {
            "name": "创建支付订单",
            "request": {
                "method": "POST",
                "header": [
                    {
                        "key": "Content-Type",
                        "value": "application/json"
                    }
                ],
                "body": {
                    "mode": "raw",
                    "raw": "{\n    \"order_id\": \"ORDER{{$timestamp}}\",\n    \"amount\": 100.50,\n    \"notify_url\": \"https://merchant.com/callback\",\n    \"redirect_url\": \"https://merchant.com/success\",\n    \"signature\": \"{{signature}}\"\n}"
                },
                "url": {
                    "raw": "{{base_url}}/api/v1/order/create-transaction",
                    "host": ["{{base_url}}"],
                    "path": ["api", "v1", "order", "create-transaction"]
                }
            }
        },
        {
            "name": "查询订单状态",
            "request": {
                "method": "POST",
                "header": [
                    {
                        "key": "Content-Type",
                        "value": "application/json"
                    }
                ],
                "body": {
                    "mode": "raw",
                    "raw": "{\n    \"trade_id\": \"{{trade_id}}\",\n    \"signature\": \"{{signature}}\"\n}"
                },
                "url": {
                    "raw": "{{base_url}}/api/v1/order/query-transaction",
                    "host": ["{{base_url}}"],
                    "path": ["api", "v1", "order", "query-transaction"]
                }
            }
        }
    ],
    "variable": [
        {
            "key": "base_url",
            "value": "https://your-domain.com"
        },
        {
            "key": "api_token",
            "value": "your_api_token"
        }
    ]
}
```

## 10. 最佳实践

### 10.1 安全建议

1. **API密钥安全**：
   - 定期更换API密钥
   - 不要在前端代码中暴露密钥
   - 使用HTTPS传输

2. **订单号管理**：
   - 确保订单号全局唯一
   - 建议使用时间戳+随机数
   - 避免连续递增的订单号

3. **回调处理**：
   - 必须验证签名
   - 实现幂等性处理
   - 记录回调日志

### 10.2 性能优化

1. **请求优化**：
   - 设置合理的超时时间
   - 实现请求重试机制
   - 使用连接池

2. **缓存策略**：
   - 缓存汇率数据
   - 缓存钱包地址
   - 避免频繁查询

### 10.3 监控告警

1. **接口监控**：
   - 监控接口响应时间
   - 监控成功率
   - 设置异常告警

2. **业务监控**：
   - 监控订单创建量
   - 监控支付成功率
   - 监控回调成功率

## 11. 常见问题

### 11.1 签名相关

**Q: 签名验证总是失败？**
A: 检查以下几点：
- 参数排序是否正确（ASCII码排序）
- 是否包含了空值参数
- API密钥是否正确
- MD5是否为32位小写

**Q: 特殊字符如何处理？**
A: 直接使用原始值，不需要URL编码

### 11.2 订单相关

**Q: 订单过期时间是多久？**
A: 默认30分钟，可在系统配置中修改

**Q: 支持哪些币种？**
A: 目前仅支持TRC20网络的USDT

**Q: 最小支付金额是多少？**
A: 建议最小1 USDT，约6.5人民币

### 11.3 回调相关

**Q: 回调没有收到？**
A: 检查以下几点：
- 回调地址是否可访问
- 是否返回了"OK"
- 检查防火墙设置
- 查看系统回调日志

**Q: 如何处理重复回调？**
A: 在业务逻辑中实现幂等性，根据订单号判断是否已处理

## 12. 联系支持

如果您在对接过程中遇到问题，请联系我们：

- **技术支持邮箱**: support@your-domain.com
- **技术支持QQ群**: 123456789
- **工作时间**: 周一至周五 9:00-18:00

提供问题时，请包含以下信息：
- 接口地址和参数
- 完整的请求和响应
- 错误日志
- 系统环境信息

---

**版本信息**：
- 文档版本：v1.0
- 最后更新：2023-12-01
- API版本：v1
