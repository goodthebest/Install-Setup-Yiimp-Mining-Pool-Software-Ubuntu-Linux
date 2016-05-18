<?php

function getProductIdSuffix($row)
{
	$vidpid = $row['vendorid'];

	// todo: database product table...
	$known = array(
		// ASUS 960
		'1043:8520' => 'Strix',
		// ASUS 970
		'1043:8508' => 'Strix',
		// Gigabyte 750 ti
		'1458:362d' => 'OC',
		'1458:3649' => 'Black',
		// Gigabyte 960
		'1458:36ae' => '4GB',
		// MSI 960
		'1462:3202' => 'Gaming 2G',
		// MSI 970
		'1462:3160' => 'Gaming',
		// EVGA 740
		'3842:2744' => 'SC DDR3',
		// EVGA 750 Ti
		'3842:3753' => 'SC',
		'3842:3757' => 'FTW',
		// EVGA 950
		'3842:2957' => 'SSC',
		'3842:2966' => 'SSC 4GB',
		// EVGA 960
		'3842:2962' => 'SC',
		'3842:3966' => 'SSC 4GB',
		// EVGA 970
		'3842:2974' => 'SC',
		'3842:2978' => 'FTW',
		'3842:3975' => 'SSC',
		// EVGA 980
		'3842:2983' => 'SC',
		'3842:2986' => 'FTW',
		'3842:2989' => 'Hydro',
		// EVGA 980 Ti
		'3842:1996' => 'Hybrid',
	);

	if (isset($known[$vidpid])) {
		return ' '.$known[$vidpid];
	}
	return '';
}

function formatCudaArch($arch)
{
	if (is_numeric($arch)) {
		$a = intval($arch);
		return 'SM '.floor($a / 100).'.'.(($a % 100)/10);
	} else if (strpos($arch, '@')) {
		$p = explode('@', $arch);
		$a = intval($p[0]);
		$b = intval($p[1]);
		$hard = floor($a / 100).'.'.(($a % 100)/10);
		$real = floor($b / 100).'.'.(($b % 100)/10);
		return "SM {$hard}@{$real}";
	}
	return $arch;
}