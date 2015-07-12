<?php

// https://www.banx.io/SimpleAPI?a=marketsv2

function banx_simple_api_query($method, $params='')
{
	$uri = "https://www.banx.io/SimpleAPI?a=$method";
	if (!empty($params))
		$uri .= "&$params";

	$ch = curl_init($uri);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	$execResult = curl_exec($ch);
	$obj = json_decode($execResult);

	return $obj;
}

// https://www.banx.io/api/v2/public/getmarkets

function banx_public_api_query($method, $params='')
{
	$uri = "https://www.banx.io/api/v2/public/$method";
	if (!empty($params))
		$uri .= "$params";

	$ch = curl_init($uri);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	$execResult = curl_exec($ch);
	$obj = json_decode($execResult);

	return $obj;
}
