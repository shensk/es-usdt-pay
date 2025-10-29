<?php

namespace plugin\admin\app\controller;

use app\model\WalletAddress;
use plugin\admin\app\controller\Crud;
use support\Request;
use support\Response;

/**
 * 钱包地址管理控制器
 */
class WalletController extends Crud
{
    /**
     * @var WalletAddress
     */
    protected $model = null;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->model = new WalletAddress;
    }

    /**
     * 验证插入数据
     */
    protected function insertInput(Request $request): array
    {
        $data = parent::insertInput($request);
        
        // 验证钱包地址格式（TRC20地址以T开头，34位）
        if (!preg_match('/^T[A-Za-z0-9]{33}$/', $data['token'])) {
            throw new \support\exception\BusinessException('钱包地址格式不正确，TRC20地址应以T开头，共34位');
        }
        
        // 检查地址是否已存在
        if (WalletAddress::where('token', $data['token'])->exists()) {
            throw new \support\exception\BusinessException('钱包地址已存在');
        }
        
        return $data;
    }

    /**
     * 验证更新数据
     */
    protected function updateInput(Request $request): array
    {
        [$id, $data] = parent::updateInput($request);
        
        // 验证钱包地址格式
        if (isset($data['token']) && !preg_match('/^T[A-Za-z0-9]{33}$/', $data['token'])) {
            throw new \support\exception\BusinessException('钱包地址格式不正确，TRC20地址应以T开头，共34位');
        }
        
        // 检查地址是否已被其他钱包使用
        if (isset($data['token']) && WalletAddress::where('token', $data['token'])->where('id', '!=', $id)->exists()) {
            throw new \support\exception\BusinessException('钱包地址已被其他记录使用');
        }
        
        return [$id, $data];
    }

    /**
     * 重写查询输入参数，默认按创建时间倒序
     */
    protected function selectInput(\support\Request $request): array
    {
        [$where, $format, $limit, $field, $order, $page] = parent::selectInput($request);
        
        // 如果没有指定排序字段，默认按创建时间倒序
        if (!$field) {
            $field = 'created_at';
            $order = 'desc';
        }
        
        return [$where, $format, $limit, $field, $order, $page];
    }

    /**
     * 检查钱包余额
     */
    public function checkBalance(Request $request): Response
    {
        $id = $request->post('id');
        if (!$id) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        $wallet = WalletAddress::find($id);
        if (!$wallet) {
            return json(['code' => 1, 'msg' => '钱包不存在']);
        }

        try {
            // 使用TronService获取最新余额
            $tronService = new \app\service\TronService();
            $client = new \GuzzleHttp\Client(['timeout' => 10]);
            
            $response = $client->get('https://apilist.tronscanapi.com/api/account', [
                'query' => ['address' => $wallet->token]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $balance = 0;
            
            if (isset($data['trc20token_balances'])) {
                foreach ($data['trc20token_balances'] as $token) {
                    // USDT合约地址 (TRC20)
                    if ($token['tokenId'] === 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t') {
                        $balance = $token['balance'] / 1000000; // USDT精度为6位
                        break;
                    }
                }
            }

            // 更新钱包余额
            $wallet->balance = $balance;
            $wallet->last_check_time = date('Y-m-d H:i:s');
            $wallet->save();

            return json(['code' => 0, 'msg' => '查询成功', 'data' => ['balance' => number_format($balance, 4)]]);
        } catch (\Exception $e) {
            return json(['code' => 1, 'msg' => '查询失败：' . $e->getMessage()]);
        }
    }

    /**
     * 更新钱包状态
     */
    public function updateStatus(Request $request): Response
    {
        $id = $request->post('id');
        $status = $request->post('status');
        
        if (!$id || !in_array($status, [1, 2])) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        $wallet = WalletAddress::find($id);
        if (!$wallet) {
            return json(['code' => 1, 'msg' => '钱包不存在']);
        }

        $wallet->status = $status;
        $wallet->save();

        return json(['code' => 0, 'msg' => '操作成功']);
    }
}






