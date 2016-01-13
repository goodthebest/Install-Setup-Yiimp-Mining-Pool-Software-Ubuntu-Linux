<?php

// same as bittrex

function bleutrade_api_query($method, $params='')
{
	require_once('/etc/yiimp/keys.php');
	if (!defined('EXCH_BLEUTRADE_SECRET')) define('EXCH_BLEUTRADE_SECRET', '');

	// optional secret key
	if (empty(EXCH_BLEUTRADE_SECRET) && strpos($method, 'public') === FALSE) return false;
	if (empty(EXCH_BLEUTRADE_KEY) && strpos($method, 'public') === FALSE) return false;

	$apikey = EXCH_BLEUTRADE_KEY; // your API-key
	$apisecret = EXCH_BLEUTRADE_SECRET; // your Secret-key

	$nonce = time();

	$uri = "https://bleutrade.com/api/v2/$method?apikey=$apikey&nonce=$nonce$params";

//	if (strpos($method,'public/') === FALSE && !strpos($method,'getdepositaddress'))
//		debuglog("bleutrade $method $params");

	$sign = hash_hmac('sha512', $uri, $apisecret);
	$ch = curl_init($uri);

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array("apisign:$sign"));
	curl_setopt($ch, CURLOPT_ENCODING , 'gzip');

	$data = curl_exec($ch);
	$obj = json_decode($data);

	if(!is_object($obj)) debuglog("bleutrade: $method fail ".strip_tags($data).curl_error($ch));

	curl_close($ch);

	return $obj;
}
