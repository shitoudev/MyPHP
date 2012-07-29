<?php

/*
 * @Description 易宝支付产品通用接口范例 
 * @V3.0
 * @Author rui.xin
 * @modify by Jerome	2010.05.06
 */
class YeepayService{

	
	#	商户编号p1_MerId,以及密钥merchantKey 需要从易宝支付平台获得
	public $p1_MerId;
	public $merchantKey;

	#	产品通用接口正式请求地址
	public $reqURL_onLine = "https://www.yeepay.com/app-merchant-proxy/node";
	#	产品通用接口测试请求地址
	//public $reqURL_onLine = "http://tech.yeepay.com:8080/robot/debug.action";
		
	# 业务类型
	# 支付请求，固定值"Buy" .	
	public $p0_Cmd = "Buy";
		
	#	送货地址
	# 为"1": 需要用户将送货地址留在易宝支付系统;为"0": 不需要，默认为 "0".
	public $p9_SAF = "0";
	
	public function __construct($a_key,$s_key){
		$this->p1_MerId = $a_key;
		$this->merchantKey = $s_key;
	}
	
	#签名函数生成签名串
	public function getReqString($p2_Order,$p3_Amt,$p4_Cur,$p5_Pid,$p6_Pcat,$p7_Pdesc,$p8_Url,$pa_MP,$pd_FrpId,$pr_NeedResponse)
	{
			
		#进行签名处理，一定按照文档中标明的签名顺序进行
	  $sbOld = "";
	  #加入业务类型
	  $sbOld = $sbOld.$this->p0_Cmd;
	  #加入商户编号
	  $sbOld = $sbOld.$this->p1_MerId;
	  #加入商户订单号
	  $sbOld = $sbOld.$p2_Order;     
	  #加入支付金额
	  $sbOld = $sbOld.$p3_Amt;
	  #加入交易币种
	  $sbOld = $sbOld.$p4_Cur;
	  #加入商品名称
	  $sbOld = $sbOld.$p5_Pid;
	  #加入商品分类
	  $sbOld = $sbOld.$p6_Pcat;
	  #加入商品描述
	  $sbOld = $sbOld.$p7_Pdesc;
	  #加入商户接收支付成功数据的地址
	  $sbOld = $sbOld.$p8_Url;
	  #加入送货地址标识
	  $sbOld = $sbOld.$this->p9_SAF;
	  #加入商户扩展信息
	  $sbOld = $sbOld.$pa_MP;
	  #加入支付通道编码
	  $sbOld = $sbOld.$pd_FrpId;
	  #加入是否需要应答机制
	  $sbOld = $sbOld.$pr_NeedResponse;
		//logstr($p2_Order,$sbOld,HmacMd5($sbOld,$merchantKey));
	  //return HmacMd5($sbOld,$this->merchantKey);
	  return $sbOld;
	}
	
	public function getCallbackHmacString($r0_Cmd,$r1_Code,$r2_TrxId,$r3_Amt,$r4_Cur,$r5_Pid,$r6_Order,$r7_Uid,$r8_MP,$r9_BType)
	{
	  
		//include 'merchantProperties.php';
	  
		#取得加密前的字符串
		$sbOld = "";
		#加入商家ID
		$sbOld = $sbOld.$this->p1_MerId;
		#加入消息类型
		$sbOld = $sbOld.$r0_Cmd;
		#加入业务返回码
		$sbOld = $sbOld.$r1_Code;
		#加入交易ID
		$sbOld = $sbOld.$r2_TrxId;
		#加入交易金额
		$sbOld = $sbOld.$r3_Amt;
		#加入货币单位
		$sbOld = $sbOld.$r4_Cur;
		#加入产品Id
		$sbOld = $sbOld.$r5_Pid;
		#加入订单ID
		$sbOld = $sbOld.$r6_Order;
		#加入用户ID
		$sbOld = $sbOld.$r7_Uid;
		#加入商家扩展信息
		$sbOld = $sbOld.$r8_MP;
		#加入交易结果返回类型
		$sbOld = $sbOld.$r9_BType;

		//logstr($r6_Order,$sbOld,HmacMd5($sbOld,$merchantKey));
		return $this->HmacMd5($sbOld,$this->merchantKey);

	}


	#	取得返回串中的所有参数
	public function getCallBackValue(&$r0_Cmd,&$r1_Code,&$r2_TrxId,&$r3_Amt,&$r4_Cur,&$r5_Pid,&$r6_Order,&$r7_Uid,&$r8_MP,&$r9_BType,&$hmac)
	{  
		$r0_Cmd		= $_REQUEST['r0_Cmd'];
		$r1_Code	= $_REQUEST['r1_Code'];
		$r2_TrxId	= $_REQUEST['r2_TrxId'];
		$r3_Amt		= $_REQUEST['r3_Amt'];
		$r4_Cur		= $_REQUEST['r4_Cur'];
		$r5_Pid		= $_REQUEST['r5_Pid'];
		$r6_Order	= $_REQUEST['r6_Order'];
		$r7_Uid		= $_REQUEST['r7_Uid'];
		$r8_MP		= $_REQUEST['r8_MP'];
		$r9_BType	= $_REQUEST['r9_BType']; 
		$hmac			= $_REQUEST['hmac'];
		
		return null;
	}

	public function CheckHmac($_GET){
		if($_GET["hmac"]==$this->getCallbackHmacString($_GET["r0_Cmd"],$_GET["r1_Code"],$_GET["r2_TrxId"],$_GET["r3_Amt"],$_GET["r4_Cur"],$_GET["r5_Pid"],$_GET["r6_Order"],$_GET["r7_Uid"],$_GET["r8_MP"],$_GET["r9_BType"]))
			return true;
		else
			return false;
	}
		
  
	public function HmacMd5($data,$key){
		// RFC 2104 HMAC implementation for php.
		// Creates an md5 HMAC.
		// Eliminates the need to install mhash to compute a HMAC
		// Hacked by Lance Rushing(NOTE: Hacked means written)

		//需要配置环境支持iconv，否则中文参数不能正常处理
		$key = iconv("GB2312","UTF-8",$key);
		$data = iconv("GB2312","UTF-8",$data);

		$b = 64; // byte length for md5
		if (strlen($key) > $b) {
		$key = pack("H*",md5($key));
		}
		$key = str_pad($key, $b, chr(0x00));
		$ipad = str_pad('', $b, chr(0x36));
		$opad = str_pad('', $b, chr(0x5c));
		$k_ipad = $key ^ $ipad ;
		$k_opad = $key ^ $opad;

		return md5($k_opad . pack("H*",md5($k_ipad . $data)));
	}

/*
function logstr($orderid,$str,$hmac)
{
include 'merchantProperties.php';
$james=fopen($logName,"a+");
fwrite($james,"\r\n".date("Y-m-d H:i:s")."|orderid[".$orderid."]|str[".$str."]|hmac[".$hmac."]");
fclose($james);
}
*/

}
?>