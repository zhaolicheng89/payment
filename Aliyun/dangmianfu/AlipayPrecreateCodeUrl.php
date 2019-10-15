<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/4/24
 * Time: 9:22
 * 当面支付类  支付两种方法都可以使用
 */
namespace Aliyun\dangmianfu;
require_once 'f2fpay/model/builder/AlipayTradePrecreateContentBuilder.php';
require_once 'f2fpay/service/AlipayTradeService.php';
require_once 'AopSdk.php';
class AlipayPrecreateCodeUrl{

    /*
     * 初始化
     * */
    public function __construct($config = array())
    {
        //具体参数请参数：https://docs.open.alipay.com/api_1/alipay.trade.precreate
        $this->config =\Aliyun\dangmianfu\Loader::config($config);
        //支付宝网关
        $this->gatewayUrl= $this->config['gatewayUrl'];
        //应用ID,您的APPID。
        $this->appId= $this->config['app_id'];
        //商户私钥，您的原始格式RSA私钥
        $this->rsaPrivateKey= $this->config['merchant_private_key'];
        //支付宝公钥,查看地址：https://openhome.alipay.com/platform/keyManage.htm 对应APPID下的支付宝公钥。
        $this->alipayrsaPublicKey= $this->config['alipay_public_key'];
        //版本
        $this->apiVersion='1.0';
        //签名方式
        $this->signType= $this->config['sign_type'];
        //编码格式
        $this->postCharset= $this->config['charset'];
        //参数格式
        $this->format='json';
        // 支付超时，线下扫码交易定义为5分钟
        $this->timeExpress='5m';
    }
    /*
   * 当面付
   * $subject  商品名称
   * $outTradeNo  订单号
   * $totalAmount  支付金额 单位:元
   * */
    public  function aliyunPrecreateCodePay($subject,$outTradeNo,$totalAmount,$type=1){
        //具体参数请参数：https://docs.open.alipay.com/api_1/alipay.trade.precreate
        // 支付超时，线下扫码交易定义为5分钟
        $timeExpress = "5m";
        //业务类型
        $body=$type;//1 充值业务类型  1订单支付  2 充值业务  说明：由于当面付不支持自定义参数 只能在这里进行透传然后解析
        $config = array (
            //签名方式,默认为RSA2(RSA2048)
            'sign_type' => $this->config['sign_type'],

            //支付宝公钥
            'alipay_public_key' => $this->config['alipay_public_key'],

            //商户私钥
            'merchant_private_key' =>$this->config['merchant_private_key'],

            //编码格式
            'charset' => $this->config['charset'],

            //支付宝网关
            'gatewayUrl' =>$this->config['gatewayUrl'],

            //应用ID
            'app_id' =>$this->config['app_id'],

            //异步通知地址,只有扫码支付预下单可用
            'notify_url' =>  $this->config['notify_url'],

            //最大查询重试次数
            'MaxQueryRetry' => $this->config['MaxQueryRetry'],

            //查询间隔
            'QueryDuration' =>  $this->config['QueryDuration'],
        );
        $qrPayRequestBuilder = new \AlipayTradePrecreateContentBuilder();
        $qrPayRequestBuilder->setOutTradeNo($outTradeNo);
        $qrPayRequestBuilder->setTotalAmount($totalAmount);
        $qrPayRequestBuilder->setTimeExpress($timeExpress);
        $qrPayRequestBuilder->setSubject($subject);
        $qrPayRequestBuilder->setBody($body);
        // 调用qrPay方法获取当面付应答
        $qrPay = new \AlipayTradeService($config);
        $qrPayResult = $qrPay->qrPay($qrPayRequestBuilder);
        $response = $qrPayResult->getResponse();
        //	根据状态值进行业务处理
        switch ($qrPayResult->getTradeStatus()){
            case "SUCCESS":
                return $response->qr_code;
                break;
            case "FAILED":
                return $response->sub_msg.'或者订单号重复';
                break;
            case "UNKNOWN":
                return $response->sub_msg;
                break;
            default:
                return "非法访问!";
                break;
        }
    }
    /*
     * 当面付
     * $subject  商品名称
     * $outTradeNo  订单号
     * $totalAmount  支付金额 单位:元
     * $type 默认为1  业务类型  1订单支付  2 充值业务  说明：由于当面付不支持自定义参数 只能在这里进行透传然后解析
     * */
    public  function aliyunPrecreateCodeTwoPay($subject,$outTradeNo,$totalAmount,$type=1){
        $aop = new \AopClient();
        $aop->gatewayUrl = $this->gatewayUrl;
        $aop->appId = $this->appId;
        $aop->rsaPrivateKey =   $this->rsaPrivateKey;
        $aop->alipayrsaPublicKey= $this->alipayrsaPublicKey;
        $aop->apiVersion =  $this->apiVersion;
        $aop->signType =  $this->signType;
        $aop->postCharset=  $this->postCharset;
        $aop->format=$this->format;
        $timeExpress =$this->timeExpress;
        $body=$type;
        $qrPayRequestBuilder = new \AlipayTradePrecreateContentBuilder();
        $qrPayRequestBuilder->setOutTradeNo($outTradeNo);
        $qrPayRequestBuilder->setTotalAmount($totalAmount);
        $qrPayRequestBuilder->setTimeExpress($timeExpress);
        $qrPayRequestBuilder->setSubject($subject);
        $qrPayRequestBuilder->setBody($body);
        $request = new \AlipayTradePrecreateRequest ();
        $request->setBizContent($qrPayRequestBuilder->getBizContent());
        $result = $aop->execute ($request);
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;
        if(!empty($resultCode)&&$resultCode == 10000){
            return $result->$responseNode->qr_code;
        } else {
            return $result;
        }
    }
    /*
   * 当面付
     * 订单查询
     *$outTradeNo 商户订单号
     */
    public function orderQuery($out_trade_no){
        if(empty($out_trade_no)){
            $result=array(
                'code'=>40004,
                'message'=>'Business Failed',
                'sub_msg'=>'参数无效：商户订单号不能为空',
            );
            return $result;
        }
        $aop = new \AopClient();
        $aop->gatewayUrl = $this->gatewayUrl;
        $aop->appId = $this->appId;
        $aop->rsaPrivateKey =   $this->rsaPrivateKey;
        $aop->alipayrsaPublicKey= $this->alipayrsaPublicKey;
        $aop->apiVersion =  $this->apiVersion;
        $aop->signType =  $this->signType;
        $aop->postCharset=  $this->postCharset;
        $aop->format=$this->format;
        $request = new \AlipayTradeQueryRequest();
        $bizcontent= json_encode(['out_trade_no'=>$out_trade_no]);
        $request->setBizContent($bizcontent);
        $result = $aop->execute ($request);
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;
        if(!empty($resultCode)&&$resultCode == 10000){
            $arr=array(
                'code'=>$result->$responseNode->code,
                'message'=>$result->$responseNode->msg,
                'out_trade_no'=>$result->$responseNode->out_trade_no,
                'total_amount'=>$result->$responseNode->total_amount,
                'trade_no'=>$result->$responseNode->trade_no,
                'buyer_user_id'=>$result->$responseNode->buyer_user_id,
                'send_pay_date'=>$result->$responseNode->send_pay_date,
            );
            return $arr;
        } else {
            $arr=array(
                'code'=>$result->$responseNode->code,
                'message'=>$result->$responseNode->msg,
                'sub_msg'=>$result->$responseNode->sub_msg,
                'out_trade_no'=>$result->$responseNode->out_trade_no,
            );
            return $arr;
        }
    }
    /*
   * 当面付
    * 订单退款
    *$outTradeNo 商户订单号
    */
    public function refund($out_trade_no){
        if(empty($out_trade_no)) {
            $result=array(
                'code'=>40004,
                'message'=>'Business Failed',
                'sub_msg'=>'参数无效：退款请求号不能为空',
            );
            return $result;
        }
        $aop = new \AopClient ();
        $aop->gatewayUrl = $this->gatewayUrl;
        $aop->appId = $this->appId;
        $aop->rsaPrivateKey =   $this->rsaPrivateKey;
        $aop->alipayrsaPublicKey= $this->alipayrsaPublicKey;
        $aop->apiVersion =  $this->apiVersion;
        $aop->signType =  $this->signType;
        $aop->postCharset=  $this->postCharset;
        $aop->format=$this->format;
        $request = new \AlipayTradeFastpayRefundQueryRequest();
        $bizcontent= json_encode(['out_trade_no'=>$out_trade_no,'out_request_no'=>$out_trade_no]);
        $request->setBizContent($bizcontent);
        $result= $aop->execute($request);
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;
        if($resultCode==10000){
            $arr=array(
                'code'=>$result->$responseNode->code,
                'message'=>$result->$responseNode->msg,
                'sub_msg'=>'退款成功',
                'out_trade_no'=>$result->$responseNode->out_trade_no,
                'refund_amount'=>$result->$responseNode->refund_amount,
                'total_amount'=>$result->$responseNode->total_amount,
                'trade_no'=>$result->$responseNode->trade_no,
            );
            return $arr;
        }else{
            $arr=array(
                'code'=>$result->$responseNode->code,
                'message'=>$result->$responseNode->msg,
                'sub_msg'=>$result->$responseNode->sub_msg,
                'out_trade_no'=>$result->$responseNode->out_trade_no,
            );
            return $arr;
        }
    }
}
