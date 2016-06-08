<?php

///////////////////////////////////////////////////////////////////////////////////////////////////////////

function BenchUpdateChips()
{
	require_once(app()->getModulePath().'/bench/functions.php');

	$benchs = getdbolist('db_benchmarks', "IFNULL(chip,'')=''");
	foreach ($benchs as $bench) {
		if (empty($bench->vendorid) || empty($bench->device)) continue;

		debuglog("bench: {$bench->device}...");
		$chip = getChipName($bench->attributes);
		if (!empty($chip) && $chip != '-') {
			$bench->chip = $chip;
			$bench->save();
		}
	}

	// add new chips
	$rows = dbolist('SELECT DISTINCT B.chip, B.type FROM benchmarks B WHERE B.chip NOT IN (
		SELECT DISTINCT C.chip FROM bench_chips C WHERE C.devicetype = B.type
	)');

	foreach ($rows as $row) {
		if (empty($row['chip']) || empty($row['type'])) continue;
		$chip = new db_bench_chips;
		$chip->chip = $row['chip'];
		$chip->devicetype = $row['type'];
		if ($chip->insert()) {
			debuglog("bench: added {$chip->devicetype} chip {$chip->chip}");
			dborun('UPDATE benchmarks SET idchip=:id WHERE chip=:chip AND type=:type', array(
				':id'=>$chip->id, ':chip'=>$row['chip'], ':type'=>$row['type'],
			));
		}
	}
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////
