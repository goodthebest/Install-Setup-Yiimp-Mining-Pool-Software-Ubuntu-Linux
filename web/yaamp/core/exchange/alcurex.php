<?php

// https://alcurex.org/index.php/crypto/api_documentation

function alcurex_api_query($method, $params='')
{
	$uri = "https://alcurex.org/api/$method.php$params";
//	debuglog("$uri");

	$ch = curl_init($uri);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	$execResult = curl_exec($ch);
	$obj = json_decode($execResult);

	return $obj;
}
