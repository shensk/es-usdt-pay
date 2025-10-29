<?php

namespace plugin\admin\app\controller;

use support\Request;
use support\Response;
use plugin\admin\app\model\WalletAddress;
use plugin\admin\app\controller\Crud;
use support\exception\BusinessException;

/**
 * 钱包管理 
 */
class WalletAddressController extends Crud
{
    
    /**
     * @var WalletAddress
     */
    protected $model = null;

    /**
     * 构造函数
     * @return void
     */
    public function __construct()
    {
        $this->model = new WalletAddress;
    }
    
    /**
     * 浏览
     * @return Response
     */
    public function index(): Response
    {
        return view('wallet-address/index');
    }

    /**
     * 插入
     * @param Request $request
     * @return Response
     * @throws BusinessException
     */
    public function insert(Request $request): Response
    {
        if ($request->method() === 'POST') {
            return parent::insert($request);
        }
        return view('wallet-address/insert');
    }

    /**
     * 更新
     * @param Request $request
     * @return Response
     * @throws BusinessException
    */
    public function update(Request $request): Response
    {
        if ($request->method() === 'POST') {
            return parent::update($request);
        }
        return view('wallet-address/update');
    }

}
