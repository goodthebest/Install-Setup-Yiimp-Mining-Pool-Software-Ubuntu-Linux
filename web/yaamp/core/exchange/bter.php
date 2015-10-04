<?php

// http://data.bter.com/api/1/marketlist

function bter_api_query($method, $params='')
{
	$uri = 'http://data.bter.com/api/1/'.$method;
	if (!empty($params))
		$uri .= '/'.$params;

	$ch = curl_init($uri);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	$data = strip_tags(curl_exec($ch));
	if ($method == 'tickers' && empty($params)) {
		$obj = json_decode($data, true);
	}
	else $obj = json_decode($data);

	return $obj;
}
