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
