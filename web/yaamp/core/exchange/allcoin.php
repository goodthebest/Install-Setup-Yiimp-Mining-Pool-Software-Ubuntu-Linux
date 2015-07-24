<?php

// https://www.allcoin.com/pub/api#market_summary
// https://www.allcoin.com/api2/pairs

function allcoin_api_query($method, $params='')
{
	$uri = "https://www.allcoin.com/api2/{$method}{$params}";

	$ch = curl_init($uri);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	$execResult = strip_tags(curl_exec($ch));
	$obj = json_decode($execResult);

	return $obj;
}
