<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/28
 * Time: 14:39
 */
namespace WxPayPubHelper;
require_once "WxPay.Api.php";
require_once "WxPay.NativePay.php";
require_once "WxPay.JsApiPay.php";
require_once "WxPay.Notify.php";
require_once "log.php";
class Wxpay
{
    /*
     * 初始化
     * */
    public function __construct($config = array()){
        $this->config=$config;
    }
    /*
   * NATIVE二维码支付
     * $goods_title 商品名称
     * $ordersn  商户订单号
     * $money   单位元 人民币
     * $type 默认为1  //订单业务类型  1订单支付  2 充值业务
   * */
    public  function getPayQrcode($goods_title,$ordersn,$money,$type=1){
        $input = new \WxPayUnifiedOrder();
        $input->SetBody($goods_title);
        $input->SetAttach($type);//订单业务类型  1订单支付  2 充值业务
        $input->SetOut_trade_no($ordersn);
        $input->SetTotal_fee($money*100);//转换为分
        $input->SetGoods_tag($goods_title);
        $input->SetTrade_type("NATIVE");
        $input->SetProduct_id($ordersn);

        $WxPayApi=new \WxPayApi($this->config);
        $result = $WxPayApi->unifiedOrder($input);
        if($result['return_code']=='FAIL'){
            return $result;
        }
        if($result['result_code']=='FAIL'){
            return $result;
        }
        $codeurl = $result["code_url"];
        return $codeurl;
    }
    /*
   * JSAPI支付 openid必填
     * openid必填 openid
    * $goods_title 商品名称
    * $ordersn  商户订单号
    * $money   单位元 人民币
    * $type 默认为1  //订单业务类型  1订单支付  2 充值业务
  * */
    public  function getJsApiPay($openid,$goods_title,$ordersn,$money,$type=1){
        $tools = new \JsApiPay();
        if(empty($openid)){
            $openid = $tools->GetOpenid();
        }
        $input = new \WxPayUnifiedOrder();
        $input->SetBody($goods_title);
        $input->SetAttach($type);//订单业务类型  1订单支付  2 充值业务
        $input->SetOut_trade_no($ordersn);
        $input->SetTotal_fee($money*100);//转换为分  测试写死  切记上线后务必换成这个 $money*100
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetGoods_tag($goods_title);
        $input->SetTrade_type("JSAPI");
        $input->SetProduct_id($ordersn);
        $input->SetOpenid($openid);
        $WxPayApi=new \WxPayApi($this->config);
        $order = $WxPayApi->unifiedOrder($input);
        $jsApiParameters = $tools->GetJsApiParameters($order);
        //获取共享收货地址js函数参数
        $editAddress = $tools->GetEditAddressParameters();
        $wx_data=array(
            'jsApiParameters'=>$jsApiParameters,
            'editAddress'=>$editAddress,
        );
        return $wx_data;
    }
    /*
     * 订单查询
     * $order_number 商户订单号
     * */
    public  function orderQuery($order_number){
        $input = new \WxPayUnifiedOrder();
        $input->SetOut_trade_no($order_number);
        $WxPayApi=new WxPayApi($this->config);
        $result = $WxPayApi->orderQuery($input);
        if($result['return_code']=='FAIL') {
            $data=array(
                'out_trade_no'=>$order_number,
                'return_code'=>$result['return_code'],
                'return_msg'=>$result['return_msg']
            );
            return $data;
        }
        return $result;
    }
    /*
   * 退款
     * $order_number 商户订单号
   * */
    public  function refund($order_number){
        $order_info=self::orderQuery($order_number);//查询订单
        if($order_info['return_code']=='FAIL') {
            return $order_info;
        }
        if($order_info['return_code']=='SUCCESS'&&$order_info['result_code']=='FAIL') {
            $data=array(
                'out_trade_no'=>$order_number,
                'return_code'=>$order_info['return_code'],
                'err_code_des'=>$order_info['err_code_des'],
                'return_msg'=>$order_info['return_msg']
            );
            return $data;
        }
        if(!isset($order_info['transaction_id'])) {
            return $order_info;
        }
        if($order_info['trade_state']=='REFUND') {
            $data=array(
                'result_code'=>$order_info['result_code'],
                'out_trade_no'=>$order_number,
                'transaction_id'=>$order_info['transaction_id'],
                'return_msg'=>$order_info['trade_state_desc']
            );
            return $data;
        }
        $input = new \WxPayRefund();
        $input->SetOut_refund_no($order_info['transaction_id']);//微信订单号
        $input->SetOut_trade_no($order_number);//商户订单号
        $input->SetRefund_fee($order_info['total_fee']);
        $input->SetTotal_fee($order_info['total_fee']);
        $WxPayApi=new WxPayApi($this->config);
        $result = $WxPayApi->refund($input);
        if($result['result_code']=='SUCCESS') {
            $data=array(
                'result_code'=>$result['result_code'],
                'out_trade_no'=>$result['out_trade_no'],
                'transaction_id'=>$result['transaction_id'],
                'return_msg'=>'退款成功'
            );
        }
        else {
            $data=array(
                'result_code'=>$result['result_code'],
                'out_trade_no'=>$order_number,
                'return_msg'=>$result['err_code_des']
            );
        }
        return $data;
    }
    /*
     * xml转换数组
     * */
    public static function FromXml($xml){
        $input = new \WxPayUnifiedOrder();
        return $input->FromXml($xml);
    }
    /**
     * 直接ajax返回成功信息（返回类型仅适用于json格式）
     * @param int $code  状态
     * @param string $msg   提示信息
     * @param array $data  json数据
     */
    public function _return($code,$msg,$data=array())
    {
        $result['result']=$data;
        $info['code'] = $code;
        $info['msg'] = $msg;
        $info['data'] =$result?:array();
        exit(json_encode($info,JSON_UNESCAPED_UNICODE));
    }
    public function Handle()
    {
        $input = new \WxPayNotify();
        $t=$input->Handle();
        return $t;
    }
}