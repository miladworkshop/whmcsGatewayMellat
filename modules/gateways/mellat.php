<?php
/* --------------------------------------------------
-	Author 		: Milad Maldar 						-
-	Version 	: 2.0 								-
-	Author URL 	: http://miladworkshop.ir 			-
-	Module URL	: https://vrl.ir/whmcs-mellat 		-
-------------------------------------------------- */

function mellat_config()
{
    $configarray = array(
		"FriendlyName" 			=> array("Type" => "System", "Value"=>"ماژول درگاه بانک ملت"),
		"terminalId" 			=> array("FriendlyName" => "شماره پایانه", "Type" => "text", "Size" => "50", ),
		"userName" 				=> array("FriendlyName" => "نام کاربری", "Type" => "text", "Size" => "50", ),
		"userPassword" 			=> array("FriendlyName" => "کلمه عبور", "Type" => "text", "Size" => "50", ),
		"Currencies" 			=> array("FriendlyName" => "واحد پول سیستم", "Type" => "dropdown", "Options" => "rial,toman", "Description" => "لطفا واحد پول سیستم خود را انتخاب کنید.",),
		"send_telegram_ok" 		=> array("FriendlyName" => "اطلاع از تراکنش های موفق", "Type" => "dropdown", "Options" => "No,Yes", "Description" => "ارسال گزارش تراکنش های مالی موفق این درگاه از طریق تلگرام",),
		"send_telegram_error" 	=> array("FriendlyName" => "ارسال هشدار تراکنش های ناموفق و خطاها", "Type" => "dropdown", "Options" => "No,Yes", "Description" => "ارسال گزارش تراکنش های ناموفق و خطاهای این درگاه از طریق تلگرام",),
		"telegram_chatid" 		=> array("FriendlyName" => "Chat ID تلگرام", "Type" => "text", "Description" => "چت آی دی تلگرام خود را وارد کنید - <a href='http://miladworkshop.ir/telegram-chat-id' target='_blank' style='color:#0000FF'>آموزش دریافت Chat ID تلگرام</a>", ),
		"author" 				=> array("FriendlyName" => "برنامه نویس", "Type" => "", "Description" => "طراحی و برنامه نویسی شده توسط <a href='http://miladworkshop.ir' target='_blank' style='color:#FF0000'>میلاد مالدار</a>", ),
    );
	return $configarray;
}

function mellat_link($params)
{
    $currencies = $params['Currencies'];
    $invoiceid 	= $params['invoiceid'];
    $email 		= $params['clientdetails']['email'];
    $amount 	= $params['amount'];
	$amount 	= $params['amount'] - '.00';
	$amount 	= ($currencies == 'toman') ? intval($amount) * 10 : intval($amount);

	$code = '<form method="post" action="modules/gateways/mellat/pay.php">
	<input type="hidden" name="invoiceid" value="'. $invoiceid .'" />
	<input type="hidden" name="amount" value="'. $amount .'" />
	<input type="hidden" name="email" value="'. $email .'" />
	<input type="hidden" name="currencies" value="'. $currencies .'" />
	<input type="submit" name="pay" value=" پرداخت " /></form>';

	return $code;
}
?>