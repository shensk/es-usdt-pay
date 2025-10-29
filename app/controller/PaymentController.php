<?php

namespace app\controller;

use app\service\PaymentService;
use support\Request;
use support\Response;

/**
 * 支付控制器
 */
class PaymentController
{
    /**
     * 创建支付订单
     *
     * @param Request $request
     * @return Response
     */
    public function createTransaction(Request $request): Response
    {
        try {
            $params = $request->post();
            
            // 验证签名
            if (!isset($params['signature'])) {
                return $this->errorResponse('缺少签名参数', 401);
            }
            
            if (!PaymentService::verifySignature($params, $params['signature'])) {
                return $this->errorResponse('签名验证失败', 401);
            }
            
            // 创建订单
            $result = PaymentService::createOrder($params);
            
            return $this->successResponse($result);
            
        } catch (\Exception $e) {
            $code = $e->getCode() ?: 400;
            return $this->errorResponse($e->getMessage(), $code);
        }
    }

    /**
     * 检查订单状态
     *
     * @param Request $request
     * @return Response
     */
    public function checkStatus(Request $request, $trade_id = null): Response
    {
        try {
            $tradeId = $trade_id;
            
            if (empty($tradeId)) {
                return $this->errorResponse('缺少交易ID参数', 400);
            }
            
            $result = PaymentService::checkOrderStatus($tradeId);
            
            return $this->successResponse($result);
            
        } catch (\Exception $e) {
            $code = $e->getCode() ?: 400;
            return $this->errorResponse($e->getMessage(), $code);
        }
    }

    /**
     * 支付收银台页面
     *
     * @param Request $request
     * @return Response
     */
    public function checkoutCounter(Request $request, $trade_id = null): Response
    {
        try {
            $tradeId = $trade_id;
            
            if (empty($tradeId)) {
                return response('<h1>订单不存在</h1>', 404);
            }
            
            $data = PaymentService::getCheckoutData($tradeId);
            
            // 渲染支付页面
            return $this->renderPaymentPage($data);
            
        } catch (\Exception $e) {
            return response("<h1>错误：{$e->getMessage()}</h1>", 400);
        }
    }

    /**
     * 渲染支付页面
     *
     * @param array $data
     * @return Response
     */
    private function renderPaymentPage(array $data): Response
    {
        // 使用Webman的模板引擎
        return view('payment/checkout', $data);
    }


    /**
     * 成功响应
     *
     * @param mixed $data
     * @param string $message
     * @return Response
     */
    private function successResponse($data = null, string $message = 'success'): Response
    {
        $response = [
            'status_code' => 200,
            'message' => $message,
            'data' => $data,
            'request_id' => $this->generateRequestId()
        ];

        return json($response);
    }

    /**
     * 错误响应
     *
     * @param string $message
     * @param int $code
     * @return Response
     */
    private function errorResponse(string $message, int $code = 400): Response
    {
        $response = [
            'status_code' => $code,
            'message' => $message,
            'data' => null,
            'request_id' => $this->generateRequestId()
        ];

        return json($response);
    }

    /**
     * 生成请求ID
     *
     * @return string
     */
    private function generateRequestId(): string
    {
        return sprintf(
            '%08x-%04x-%04x-%04x-%012x',
            mt_rand(0, 0xffffffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffffffffffff)
        );
    }
}






