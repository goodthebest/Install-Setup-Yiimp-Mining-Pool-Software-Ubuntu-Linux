<?php

$series = array();
$n = 0;

$json = controller()->memcache->get("yiimp-explorer-diff-".$coin->symbol);

if (empty($json)) {

	// version is used in multi algo coins
	$multiAlgos = versionToAlgo($coin, 0) !== false;

	$series['diff'] = array();

	$remote = new Bitcoin($coin->rpcuser, $coin->rpcpasswd, $coin->rpchost, $coin->rpcport);
	for($i = $coin->block_height; $i > max(0, $coin->block_height-500); $i--)
	{
		$hash = $remote->getblockhash($i);
		if(!$hash) continue;

		$block = $remote->getblock($hash);
		if(!$block) continue;

		// only graph PoW blocks
		if (arraySafeval($block,'nonce',0) == 0) continue;

		$n++;

		$tm = $block['time'];
		$dt = date('Y-m-d H:i:s', $tm);
		$diff = $block['difficulty'];
		$vers = $block['version'];
		$algo = versionToAlgo($coin, $vers);

		if (!$multiAlgos)
			$series['diff'][$n] = array($dt,$diff);
		else {
			$series[$algo][$n] = array($dt,$diff);
		}
	}

	if (!$multiAlgos)
		$json = json_encode(array_values($series['diff']));
	else if (!empty($coin->algo) && !empty($series[$coin->algo]))
		$json = json_encode(array_values($series[$coin->algo]));
	else {
		$json = '';
		foreach ($series as $algo => $data) {
			$values = array_values($data);
			$json .= json_encode($values).',';
		}
		$json = rtrim($json, ',');
	}
	// memcache the data
	controller()->memcache->set("yiimp-explorer-diff-".$coin->symbol, $json, 120);
}

echo "[$json]";
