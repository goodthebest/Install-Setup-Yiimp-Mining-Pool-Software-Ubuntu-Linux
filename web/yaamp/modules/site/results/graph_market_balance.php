<?php

//$id = getiparam('id');
if (!$id) return;

$coin = getdbo('db_coins', $id);
if (!$coin) return;

$t = time() - 7*24*60*60;

$markets = dbolist("SELECT M.id AS id, M.name AS name, MIN(MH.balance) AS min, MAX(MH.balance) AS max
	FROM market_history MH LEFT JOIN markets M ON M.id = MH.idmarket
	WHERE MH.idcoin=$id AND MH.time>$t AND NOT M.disabled
	GROUP BY M.id, M.name HAVING max > 0");

$stackedMax = (double) 0;

$series = array();
foreach ($markets as $m) {

	$market = getdbo('db_markets', $m['id']);

	$stats = getdbolist('db_market_history', "time>$t AND idmarket={$market->id} ORDER BY time");

	$max = 0;
	foreach($stats as $histo) {
		$d = date('Y-m-d H:i', $histo->time);
		$series[$m['name']][] = array($d, (double) bitcoinvaluetoa($histo->balance));

		$max = max($max, $histo->balance);
	}

	$stackedMax += $max;
}

// "yiimp" balance

$stats = getdbolist('db_market_history', "time>$t AND idcoin={$id} AND idmarket IS NULL ORDER BY time");

$max = 0;
foreach($stats as $histo) {
	$d = date('Y-m-d H:i', $histo->time);
	$series['yiimp'][] = array($d, (double) bitcoinvaluetoa($histo->balance));
	$max = max($max, $histo->balance);
}
$stackedMax += $max;

// Stacked graph specific : seems to require same amount of points :/
$max = 0;
foreach ($series as $serie) {
	$max = max($max, count($serie));
}
foreach ($series as $name => $serie) {
	$n = count($serie);
	for ($i = count($serie); $i < $max; $i++) {
		array_unshift($series[$name], $series[$name][0]);
	}
}

echo json_encode(array(
	'data'=>array_values($series),
	'labels'=>array_keys($series),
	'rangeMin'=> (double) 0.0,
	'rangeMax'=> ($stackedMax * 1.10),
));
