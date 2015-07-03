<?php

// https://www.cryptopia.co.nz/api/GetMarkets/24

function cryptopia_api_query($method, $params='')
{
	$uri = "https://www.cryptopia.co.nz/api/$method";
	if (!empty($params))
		$uri .= "/$params";
//	debuglog("$uri");

	$ch = curl_init($uri);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	$execResult = curl_exec($ch);
	$obj = json_decode($execResult);

	return $obj;
}
