<?php

namespace plugin\admin\app\controller;

use support\Request;
use support\Response;
use plugin\admin\app\model\Order;
use plugin\admin\app\controller\Crud;
use support\exception\BusinessException;

/**
 * 订单管理 
 */
class OrderController extends Crud
{
    
    /**
     * @var Order
     */
    protected $model = null;

    /**
     * 构造函数
     * @return void
     */
    public function __construct()
    {
        $this->model = new Order;
    }
    
    /**
     * 浏览
     * @return Response
     */
    public function index(): Response
    {
        return view('order/index');
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
        return view('order/insert');
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
        return view('order/update');
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
     * 更新订单状态
     */
    public function updateStatus(Request $request): Response
    {
        $id = $request->post('id');
        $status = $request->post('status');
        
        if (!$id || !in_array($status, [1, 2, 3, 4])) {
            return json(['code' => 1, 'msg' => '参数错误']);
        }

        $order = \app\model\Order::find($id);
        if (!$order) {
            return json(['code' => 1, 'msg' => '订单不存在']);
        }

        $order->status = $status;
        if ($status == 2) {
            $order->paid_at = date('Y-m-d H:i:s');
        }
        $order->save();

        return json(['code' => 0, 'msg' => '操作成功']);
    }

    /**
     * 订单统计页面
     */
    public function statistics(): Response
    {
        $Order = new \app\model\Order();
        
        // 今日统计
        $today = [
            'total_orders' => $Order->where('created_at', '>=', date('Y-m-d 00:00:00'))->count(),
            'paid_orders' => $Order->where('created_at', '>=', date('Y-m-d 00:00:00'))->where('status', 2)->count(),
            'total_amount' => $Order->where('created_at', '>=', date('Y-m-d 00:00:00'))->sum('amount') ?: 0,
            'paid_amount' => $Order->where('created_at', '>=', date('Y-m-d 00:00:00'))->where('status', 2)->sum('amount') ?: 0,
            'total_usdt_amount' => $Order->where('created_at', '>=', date('Y-m-d 00:00:00'))->sum('actual_amount') ?: 0,
            'paid_usdt_amount' => $Order->where('created_at', '>=', date('Y-m-d 00:00:00'))->where('status', 2)->sum('actual_amount') ?: 0,
        ];
        
        // 昨日统计
        $yesterday = [
            'total_orders' => $Order->whereBetween('created_at', [date('Y-m-d 00:00:00', strtotime('-1 day')), date('Y-m-d 23:59:59', strtotime('-1 day'))])->count(),
            'paid_orders' => $Order->whereBetween('created_at', [date('Y-m-d 00:00:00', strtotime('-1 day')), date('Y-m-d 23:59:59', strtotime('-1 day'))])->where('status', 2)->count(),
            'total_amount' => $Order->whereBetween('created_at', [date('Y-m-d 00:00:00', strtotime('-1 day')), date('Y-m-d 23:59:59', strtotime('-1 day'))])->sum('amount') ?: 0,
            'paid_amount' => $Order->whereBetween('created_at', [date('Y-m-d 00:00:00', strtotime('-1 day')), date('Y-m-d 23:59:59', strtotime('-1 day'))])->where('status', 2)->sum('amount') ?: 0,
            'total_usdt_amount' => $Order->whereBetween('created_at', [date('Y-m-d 00:00:00', strtotime('-1 day')), date('Y-m-d 23:59:59', strtotime('-1 day'))])->sum('actual_amount') ?: 0,
            'paid_usdt_amount' => $Order->whereBetween('created_at', [date('Y-m-d 00:00:00', strtotime('-1 day')), date('Y-m-d 23:59:59', strtotime('-1 day'))])->where('status', 2)->sum('actual_amount') ?: 0,
        ];
        
        // 本月统计
        $this_month = [
            'total_orders' => $Order->where('created_at', '>=', date('Y-m-01 00:00:00'))->count(),
            'paid_orders' => $Order->where('created_at', '>=', date('Y-m-01 00:00:00'))->where('status', 2)->count(),
            'total_amount' => $Order->where('created_at', '>=', date('Y-m-01 00:00:00'))->sum('amount') ?: 0,
            'paid_amount' => $Order->where('created_at', '>=', date('Y-m-01 00:00:00'))->where('status', 2)->sum('amount') ?: 0,
            'total_usdt_amount' => $Order->where('created_at', '>=', date('Y-m-01 00:00:00'))->sum('actual_amount') ?: 0,
            'paid_usdt_amount' => $Order->where('created_at', '>=', date('Y-m-01 00:00:00'))->where('status', 2)->sum('actual_amount') ?: 0,
        ];
        
        // 上月统计
        $last_month_start = date('Y-m-01 00:00:00', strtotime('first day of last month'));
        $last_month_end = date('Y-m-t 23:59:59', strtotime('last day of last month'));
        $last_month = [
            'total_orders' => $Order->whereBetween('created_at', [$last_month_start, $last_month_end])->count(),
            'paid_orders' => $Order->whereBetween('created_at', [$last_month_start, $last_month_end])->where('status', 2)->count(),
            'total_amount' => $Order->whereBetween('created_at', [$last_month_start, $last_month_end])->sum('amount') ?: 0,
            'paid_amount' => $Order->whereBetween('created_at', [$last_month_start, $last_month_end])->where('status', 2)->sum('amount') ?: 0,
            'total_usdt_amount' => $Order->whereBetween('created_at', [$last_month_start, $last_month_end])->sum('actual_amount') ?: 0,
            'paid_usdt_amount' => $Order->whereBetween('created_at', [$last_month_start, $last_month_end])->where('status', 2)->sum('actual_amount') ?: 0,
        ];
        
        // 总计统计
        $total = [
            'total_orders' => $Order->count(),
            'paid_orders' => $Order->where('status', 2)->count(),
            'pending_orders' => $Order->where('status', 1)->count(),
            'expired_orders' => $Order->where('status', 3)->count(),
            'total_amount' => $Order->sum('amount') ?: 0,
            'paid_amount' => $Order->where('status', 2)->sum('amount') ?: 0,
            'total_usdt_amount' => $Order->sum('actual_amount') ?: 0,
            'paid_usdt_amount' => $Order->where('status', 2)->sum('actual_amount') ?: 0,
        ];
        
        return view('order/statistics', compact('today', 'yesterday', 'this_month', 'last_month', 'total'));
    }
}






