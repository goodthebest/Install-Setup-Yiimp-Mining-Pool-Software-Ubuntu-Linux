<?php

//$id = getiparam('id');
if (!$id) return;

$coin = getdbo('db_coins', $id);
if (!$coin) return;

$t = time() - 7*24*60*60;

$markets = dbolist("SELECT M.id AS id, M.name AS name, MIN(MH.balance) AS min, MAX(MH.balance) AS max
	FROM market_history MH LEFT JOIN markets M ON M.id = MH.idmarket
	WHERE MH.idcoin=$id AND MH.time>$t AND NOT M.disabled
	GROUP BY M.id, M.name");

$stackedMax = (double) 0;

$series = array();
foreach ($markets as $m) {

	$market = getdbo('db_markets', $m['id']);

	$stats = getdbolist('db_market_history', "time>$t AND idmarket={$market->id} ORDER BY time");

	$max = 0;
	foreach($stats as $histo)
	{
		$d = date('Y-m-d H:i:s', $histo->time);
		$series[$m['name']][] = array($d, (double) bitcoinvaluetoa($histo->balance));

		$max = max($max, $histo->balance);
	}

	if ($histo && $market->balance && $market->balancetime > $histo->time) {
		$d = date('Y-m-d H:i:s', $market->balancetime);
		$series[$m['name']][] = array($d, (double) bitcoinvaluetoa($market->balance));
		$max = max($max, $market->balance);
	}

	$stackedMax += $max;
	if ($max == 0 && !empty($stats)) {
		unset($series[$m['name']]);
	}
}


echo json_encode(array(
	'data'=>array_values($series),
	'labels'=>array_keys($series),
	'rangeMin'=> (double) 0.0,
	'rangeMax'=> ($stackedMax * 1.10),
));
