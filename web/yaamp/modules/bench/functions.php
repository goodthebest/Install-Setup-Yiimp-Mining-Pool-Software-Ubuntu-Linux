<?php

function getProductIdSuffix($row)
{
	$vidpid = $row['vendorid'];

	// todo: database product table...
	$known = array(
		// ASUS 970
		'1043:8508' => 'Strix',
		// Gigabyte 750 ti
		'1458:3649' => 'Black',
		// Gigabyte 960
		'1458:36ae' => '4GB',
		// EVGA 970
		'3842:3975' => 'SSC',
	);

	if (isset($known[$vidpid])) {
		return ' '.$known[$vidpid];
	}
	return '';
}
