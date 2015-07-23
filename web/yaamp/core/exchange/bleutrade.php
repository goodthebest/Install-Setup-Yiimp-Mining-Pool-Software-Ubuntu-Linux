<?php

// same as bittrex

function bleutrade_api_query($method, $params='')
{
	require_once('/etc/yiimp/keys.php');

	$apikey = EXCH_BLEUTRADE_KEY; // your API-key
	$apisecret = EXCH_BLEUTRADE_SECRET; // your Secret-key

	$nonce = time();
	$uri = "https://bleutrade.com/api/v2/$method?apikey=$apikey&nonce=$nonce$params";

	if (strpos($method,'public/') === FALSE && !strpos($method,'getdepositaddress'))
		debuglog("bleutrade $method $params");

	$sign = hash_hmac('sha512', $uri, $apisecret);
	$ch = curl_init($uri);

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array("apisign:$sign"));

	$execResult = curl_exec($ch);
	$obj = json_decode($execResult);

	return $obj;
}




