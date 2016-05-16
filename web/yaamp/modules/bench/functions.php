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
		// EVGA 750
		'3842:3753' => 'SC',
		// EVGA 960
		'3842:2962' => 'SC',
		// EVGA 970
		'3842:2974' => 'SC',
		'3842:3975' => 'SSC',
	);

	if (isset($known[$vidpid])) {
		return ' '.$known[$vidpid];
	}
	return '';
}
