<?php

// https://www.cryptomic.com/SimpleAPI?a=marketsv2

function cryptomic_api_simple($method, $params='')
{
	$uri = "https://www.cryptomic.com/SimpleAPI?a=$method";
	if (!empty($params))
		$uri .= "&$params";

	$ch = curl_init($uri);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	$execResult = curl_exec($ch);
	$obj = json_decode($execResult, true);

	return $obj;
}

// https://www.cryptomic.com/api/v4/public/getticker?market=LTC-BTC

function cryptomic_api_query($method, $params='')
{
	$uri = "https://www.cryptomic.com/api/v4/public/$method";
	if (!empty($params))
		$uri .= "$params";

	$ch = curl_init($uri);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	$execResult = curl_exec($ch);
	$obj = json_decode($execResult);

	return $obj;
}

// methods ok getbalances
// method failed getbalance "?currency=BTC"

function cryptomic_api_user($method, $params='')
{
	require_once('/etc/yiimp/keys.php');
	if (!defined('EXCH_BANX_SECKEY')) define('EXCH_BANX_SECKEY', '');

	if (empty(EXCH_BANX_USERNAME) || empty(EXCH_BANX_SECKEY)) return false;

	$uri = "https://www.cryptomic.com/api/v4/$method$params";

	//$nonce = time();
	$mt = explode(' ', microtime());
	$nonce = $mt[1].substr($mt[0], 2, 6);

	$headers = array(
		'Content-Type: application/json; charset=utf-8',
		'uts: '.$nonce,
		'uid: '.EXCH_BANX_USERNAME,
		'aut: '.EXCH_BANX_SECKEY,
	);

	$ch = curl_init($uri);
	curl_setopt($ch, CURLOPT_POST, 0);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	//curl_setopt($ch, CURLOPT_SSLVERSION, 1 /*CURL_SSLVERSION_TLSv1*/);
	curl_setopt($ch, CURLOPT_SSL_SESSIONID_CACHE, 0);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
	curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; Banx API PHP client; '.php_uname('s').'; PHP/'.phpversion().')');
	curl_setopt($ch, CURLOPT_ENCODING , '');

	$data = curl_exec($ch);
	$res = json_decode($data);

	if(!is_object($res) || !$res->success) {
		$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		debuglog("cryptomic: $method failed ($status) ".strip_data($data).' '.curl_error($ch));
	}

	curl_close($ch);

	return $res;
}
