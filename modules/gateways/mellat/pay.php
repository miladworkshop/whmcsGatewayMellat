<?php
/* --------------------------------------------------
-	Author 		: Milad Maldar 						-
-	Version 	: 2.0 								-
-	Author URL 	: http://miladworkshop.ir 			-
-	Module URL	: https://vrl.ir/whmcs-mellat 		-
-------------------------------------------------- */

if(file_exists('../../../init.php')){require( '../../../init.php' );}else{require("../../../dbconnect.php");}
include("../../../includes/functions.php");
include("../../../includes/gatewayfunctions.php");
include("../../../includes/invoicefunctions.php");

include_once('nusoap.php');

$gatewaymodule 	= 'mellat';
$GATEWAY 		= getGatewayVariables($gatewaymodule);

if (!$GATEWAY['type']) die('Module Not Activated');

$invoiceid 		= $_POST['invoiceid'];
$get_amount 	= $_POST['amount'];
$terminalId		= $GATEWAY['terminalId'];		// Terminal ID
$userName		= $GATEWAY['userName'];			// Username
$userPassword	= $GATEWAY['userPassword'];		// Password
$currencies 	= $GATEWAY['Currencies'];		// Currencies
$orderId		= time();						// Order ID
$localDate		= date('Ymd');					// Date
$localTime		= date('Gis');					// Time
$additionalData	= '';
$callBackUrl	= $CONFIG['SystemURL'] .'/modules/gateways/mellat/callback.php?invoiceid='. $invoiceid;
$payerId		= 0;

$db_result 		= mysql_query("SELECT * FROM `tblinvoices` WHERE `id` = {$invoiceid}");

if (mysql_num_rows($db_result) > 0)
{
	$db_data 	= mysql_fetch_array($db_result);
	$amount 	= ($currencies == 'toman') ? intval($db_data['total']) * 10 : intval($db_data['total']);

	if (is_numeric($amount) && $amount > 0 && $amount == $get_amount)
	{		
		//-- تبدیل اطلاعات به آرایه برای ارسال به بانک
		$parameters = array(
			'terminalId' 		=> $terminalId,
			'userName' 			=> $userName,
			'userPassword' 		=> $userPassword,
			'orderId' 			=> $orderId,
			'amount' 			=> $amount,
			'localDate' 		=> $localDate,
			'localTime' 		=> $localTime,
			'additionalData' 	=> $additionalData,
			'callBackUrl' 		=> $callBackUrl,
			'payerId' 			=> $payerId);
		 
		$client 	= new nusoap_client('https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl');
		$namespace 	= 'http://interfaces.core.sw.bps.com/';
		$result 	= $client->call('bpPayRequest', $parameters, $namespace);

		//-- بررسی وجود خطا
		if ($client->fault)
		{
			//-- نمایش خطا
			echo "There was a problem connecting to Bank";
			exit;
		} 
		else
		{
			$err = $client->getError();
			if ($err)
			{
				//-- نمایش خطا
				echo "Error : ". $err;
				exit;
			} 
			else
			{
				$res 		= explode (',',$result);
				$ResCode 	= $res[0];
				if ($ResCode == "0")
				{
					//-- انتقال به درگاه پرداخت
					echo '<form name="myform" action="https://bpm.shaparak.ir/pgwchannel/startpay.mellat" method="POST">
							<input type="hidden" id="RefId" name="RefId" value="'. $res[1] .'">
						</form>
						<script type="text/javascript">window.onload = formSubmit; function formSubmit() { document.forms[0].submit(); }</script>';
					exit;
				}
				else
				{
					//-- نمایش خطا
					echo "Error : ". $result;
					exit;
				}
			}
		}
	} else {
		//-- نمایش خطا
		echo "Error : Invoice Amount is invalid";
		exit;
	}
} else {
	//-- نمایش خطا
	echo "Error : Invoice is invalid";
	exit;
}
?>