<?php

// https://safecex.com/help/api

// https://safecex.com/api/getmarkets
// https://safecex.com/api/getmarket?market=VTC/BTC

function safecex_api_query($method, $params='')
{
	$uri = "https://safecex.com/api/{$method}{$params}";

	$ch = curl_init($uri);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	$execResult = strip_tags(curl_exec($ch));
	$obj = json_decode($execResult);

	return $obj;
}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////

// https://safecex.com/api/getbalances (seems unavailable yet)

function safecex_api_user($method, $params='')
{
	require_once('/etc/yiimp/keys.php');
	if (!defined('EXCH_SAFECEX_SECRET')) define('EXCH_SAFECEX_SECRET', '');

	if (empty(EXCH_SAFECEX_KEY) || empty(EXCH_SAFECEX_SECRET)) return false;

	$apikey = EXCH_SAFECEX_KEY;

	$nonce = time();
//	$mt = explode(' ', microtime());
//	$nonce = $mt[1].substr($mt[0], 2, 6);
	$url = "https://safecex.com/api/$method?apikey=$apikey&nonce=$nonce$params";

	$ch = curl_init($url);

	$sign = hash_hmac('sha512', $url, EXCH_SAFECEX_SECRET);

	curl_setopt($ch, CURLOPT_HTTPHEADER, array("apisign:$sign"));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
	//curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	//curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; Safecex PHP client; '.php_uname('s').'; PHP/'.phpversion().')');
	curl_setopt($ch, CURLOPT_ENCODING , '');

	$res = curl_exec($ch);
	if($res === false)
	{
		$e = curl_error($ch);
		debuglog("safecex: $e");
		curl_close($ch);
		return false;
	}

	$result = json_decode($res);
	if(!is_object($result) && !is_array($result)) {
		$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		debuglog("safecex: $method failed ($status) $res");
	}

	curl_close($ch);

	return $result;
}
