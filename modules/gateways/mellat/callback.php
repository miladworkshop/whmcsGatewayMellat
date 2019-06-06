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

$gatewaymodule 		= 'mellat';
$GATEWAY 			= getGatewayVariables($gatewaymodule);

if (!$GATEWAY['type']) die('Module Not Activated');

$whmcs_url			= $CONFIG['SystemURL'];
$invoiceid 			= $_GET['invoiceid'];
$order_id 			= $invoiceid;
$tran_id 			= $invoiceid;
$refcode			= $_POST['SaleReferenceId'];

$invoiceid 	= checkCbInvoiceID($invoiceid, $GATEWAY['name']);

$results = select_query( "tblinvoices", "", array( "id" => $invoiceid ) );
$data = mysql_fetch_array($results);
$db_amount = strtok($data['total'],'.');

$amount = $db_amount;

$terminalId				= $GATEWAY['terminalId'];		// Terminal ID
$userName				= $GATEWAY['userName'];			// Username
$userPassword			= $GATEWAY['userPassword'];		// Password
$orderId 				= $_POST['SaleOrderId'];		// Order ID

$verifySaleOrderId 		= $_POST['SaleOrderId'];
$verifySaleReferenceId 	= $_POST['SaleReferenceId'];

if(!empty($invoiceid)){
	
	if ($_POST['ResCode'] == '0') {
		//--پرداخت در بانک باموفقیت بوده
		include_once('nusoap.php');
		$client = new nusoap_client('https://bpm.shaparak.ir/pgwchannel/services/pgw?wsdl');
		$namespace='http://interfaces.core.sw.bps.com/';

		$parameters = array(
			'terminalId' => $terminalId,
			'userName' => $userName,
			'userPassword' => $userPassword,
			'orderId' => $orderId,
			'saleOrderId' => $verifySaleOrderId,
			'saleReferenceId' => $verifySaleReferenceId);
		// Call the SOAP method
		$result = $client->call('bpVerifyRequest', $parameters, $namespace);
		if($result == 0) {

			//-- وریفای به درستی انجام شد٬ درخواست واریز وجه
			// Call the SOAP method
			$result = $client->call('bpSettleRequest', $parameters, $namespace);
			if($result == 0) {
				//-- تمام مراحل پرداخت به درستی انجام شد.
				//-- آماده کردن خروجی

				$cartNumber = $_POST['CardHolderPan'];
				
				addInvoicePayment($invoiceid, $refcode, $amount, 0, $gatewaymodule);
				logTransaction($GATEWAY["name"], array(
					'invoiceid' 		=> $invoiceid,
					'order_id' 			=> $order_id,
					'amount' 			=> $amount ." ". $GATEWAY['Currencies'],
					'tran_id' 			=> $tran_id,
					'RefId' 			=> $_POST['RefId'],
					'SaleReferenceId' 	=> $refcode,
					'CardNumber'		=> $cartNumber,
					'status' 			=> "OK"
				), "موفق");
				
				if ($GATEWAY['send_telegram_ok'] == "Yes") {
					
					$pm = "یک تراکنش موفق در سیستم ثبت شد ( درگاه پرداخت ملت )
					----------------------------------------------------------------------------------------------\n
					
					Gateway : mellat
					
					Price : $amount $GATEWAY[Currencies]
					Ref Code : $refcode
					Order ID : $order_id
					Invoice ID : $invoiceid
					Customer Cart Number : $cartNumber";
					
					$chat_id 		= $GATEWAY['telegram_chatid'];
					$botToken 		= "291958747:AAF65_lFLaap35HS5zYxSbO1ycNb8Pl2vTk";
					$data = array('chat_id' => $chat_id, 'text' => $pm . "\n\n----------------------------------------------------------------------------------------------\n" . base64_decode("V0hNQ1MgVGVsZWdyYW0gTm90aWZpY2F0aW9uIEJ5IE1pbGFkLmlu"));
					$curl = curl_init();
					curl_setopt($curl, CURLOPT_URL, "http://telegram.europe.miladworkshop.ir/bot$botToken/sendMessage");
					curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
					curl_setopt($curl, CURLOPT_TIMEOUT, 10);
					curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
					curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
					curl_exec($curl);
					curl_close($curl);
				}
				
			} else {
				//-- در درخواست واریز وجه مشکل به وجود آمد. درخواست بازگشت وجه داده شود.
				$client->call('bpReversalRequest', $parameters, $namespace);			
				logTransaction($GATEWAY["name"] ,  array('invoiceid'=>$invoiceid,'order_id'=>$order_id,'amount'=>$amount,'tran_id'=>$tran_id,'status'=>$result), "ناموفق");
				
				if ($GATEWAY['send_telegram_error'] == "Yes") {
					
					$pm = "گزارش تراکنش ناموفق / خطا ( درگاه پرداخت ملت )
					----------------------------------------------------------------------------------------------\n
					
					Gateway : mellat
					
					Price : $amount $GATEWAY[Currencies]
					Order ID : $order_id
					Invoice ID : $invoiceid
					
					Error : در درخواست واریز وجه مشکل به وجود آمد. درخواست بازگشت وجه داده شود
					
					Error Code : $result";
					
					$chat_id 		= $GATEWAY['telegram_chatid'];
					$botToken 		= "291958747:AAF65_lFLaap35HS5zYxSbO1ycNb8Pl2vTk";
					$data = array('chat_id' => $chat_id, 'text' => $pm . "\n\n----------------------------------------------------------------------------------------------\n" . base64_decode("V0hNQ1MgVGVsZWdyYW0gTm90aWZpY2F0aW9uIEJ5IE1pbGFkLmlu"));
					$curl = curl_init();
					curl_setopt($curl, CURLOPT_URL, "http://telegram.europe.miladworkshop.ir/bot$botToken/sendMessage");
					curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
					curl_setopt($curl, CURLOPT_TIMEOUT, 10);
					curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
					curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
					curl_exec($curl);
					curl_close($curl);
				}
			}
		} else {
			//-- وریفای به مشکل خورد٬ نمایش پیغام خطا و بازگشت زدن مبلغ
			$client->call('bpReversalRequest', $parameters, $namespace);
			logTransaction($GATEWAY["name"] ,  array('invoiceid'=>$invoiceid,'order_id'=>$order_id,'amount'=>$amount,'tran_id'=>$tran_id,'status'=>$result), "ناموفق");
			
			if ($GATEWAY['send_telegram_error'] == "Yes") {
				
				$pm = "گزارش تراکنش ناموفق / خطا ( درگاه پرداخت ملت )
				----------------------------------------------------------------------------------------------\n
				
				Gateway : mellat
				
				Price : $amount $GATEWAY[Currencies]
				Order ID : $order_id
				Invoice ID : $invoiceid
				
				Error : وریفای به مشکل خورد٬ نمایش پیغام خطا و بازگشت زدن مبلغ
				
				Error Code : $result";
				
				$chat_id 		= $GATEWAY['telegram_chatid'];
				$botToken 		= "291958747:AAF65_lFLaap35HS5zYxSbO1ycNb8Pl2vTk";
				$data = array('chat_id' => $chat_id, 'text' => $pm . "\n\n----------------------------------------------------------------------------------------------\n" . base64_decode("V0hNQ1MgVGVsZWdyYW0gTm90aWZpY2F0aW9uIEJ5IE1pbGFkLmlu"));
				$curl = curl_init();
				curl_setopt($curl, CURLOPT_URL, "http://telegram.europe.miladworkshop.ir/bot$botToken/sendMessage");
				curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
				curl_setopt($curl, CURLOPT_TIMEOUT, 10);
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_exec($curl);
				curl_close($curl);
			}
		}
	} else {
		//-- پرداخت با خطا همراه بوده
		logTransaction($GATEWAY["name"] ,  array('invoiceid'=>$invoiceid,'order_id'=>$order_id,'amount'=>$amount,'tran_id'=>$tran_id,'status'=>$_POST['ResCode']), "ناموفق");
		
		if ($GATEWAY['send_telegram_error'] == "Yes") {
			
			$pm = "گزارش تراکنش ناموفق / خطا ( درگاه پرداخت ملت )
			----------------------------------------------------------------------------------------------\n
			
			Gateway : mellat
			
			Price : $amount $GATEWAY[Currencies]
			Order ID : $order_id
			Invoice ID : $invoiceid
			
			Error : پرداخت با خطا همراه بوده
			
			Error Code : $_POST[ResCode]";
			
			$chat_id 		= $GATEWAY['telegram_chatid'];
			$botToken 		= "291958747:AAF65_lFLaap35HS5zYxSbO1ycNb8Pl2vTk";
			$data = array('chat_id' => $chat_id, 'text' => $pm . "\n\n----------------------------------------------------------------------------------------------\n" . base64_decode("V0hNQ1MgVGVsZWdyYW0gTm90aWZpY2F0aW9uIEJ5IE1pbGFkLmlu"));
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, "http://telegram.europe.miladworkshop.ir/bot$botToken/sendMessage");
			curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
			curl_setopt($curl, CURLOPT_TIMEOUT, 10);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_exec($curl);
			curl_close($curl);
		}
	}

	$action = $whmcs_url ."/viewinvoice.php?id=". $invoiceid;
	header('Location: '. $action);
} else {
	echo "invoice id is blank";
}
?>