<?php

function cryptsy_api_query($method, array $req = array())
{
//	debuglog("calling cryptsy_api_query $method");

	require_once('/etc/yiimp/keys.php');

	// API settings
	$key = YIIMP_CRYPT_PUB; // your API-key
	$secret = YIIMP_CRYPT_PVK; // your Secret-key

	// $cookie_jar = tempnam(YAAMP_HTDOCS.'/yaamp/runtime/cookies','cryptsy_cook');
	$cookie_jar = YAAMP_HTDOCS.'/yaamp/runtime/cookies/cryptsy_cookies.dat';
	//touch($cookie_jar);

	$req['method'] = $method;
	$mt = explode(' ', microtime());
	$req['nonce'] = $mt[1];

	// generate the POST data string
	$post_data = http_build_query($req, '', '&');
	$sign = hash_hmac("sha512", $post_data, $secret);

	// generate the extra headers
	$headers = array(
		'Sign: '.$sign,
		'Key: '.$key,
	);

	// our curl handle (initialize if required)
	static $ch = null;
	if (is_null($ch)) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; Cryptsy API PHP client; '.php_uname('s').'; PHP/'.phpversion().')');
	}
	curl_setopt($ch, CURLOPT_URL, 'https://api.cryptsy.com/api');
	curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_jar);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

	// run the query
	$res = curl_exec($ch);
	if ($res === false) {
		debuglog("ERROR cryptsy_api_query $method");
		return null;
	}

//	debuglog("cryptsy: $method");

	$dec = json_decode($res, true);
	if (!is_array($dec)) {
		debuglog("ERROR cryptsy_api_query $method");
		debuglog(strip_tags($res));
		return null;
	}

	//usleep(200*1000);
	return $dec;
}
