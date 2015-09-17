<?php

$series = array();
$n = 0;

echo '[';

$series['diff'] = controller()->memcache->get("yiimp-explorer-diff-{$coin->symbol}");

if (empty($series['diff'])) {
	$remote = new Bitcoin($coin->rpcuser, $coin->rpcpasswd, $coin->rpchost, $coin->rpcport);
	for($i = $coin->block_height; $i > $coin->block_height-100; $i--)
	{
		$hash = $remote->getblockhash($i);
		if(!$hash) continue;

		$block = $remote->getblock($hash);
		if(!$block) continue;

		$n++;

		$tm = $block['time'];
		$dt = date('Y-m-d H:i:s', $tm);
		$tx = count($block['tx']);
		$diff = $block['difficulty'];

		$series['diff'][$n] = array($dt,$diff);
		$series['txs'][$n]  = array($dt,$tx);
	}
}

echo json_encode(array_values($series['diff']));
//echo ",";
//echo json_encode(array_values($series['txs']));

echo ']';

// memcache the data
if (!empty($series['diff']))
	controller()->memcache->set("yiimp-explorer-diff-{$coin->symbol}", $series['diff'], 60);
