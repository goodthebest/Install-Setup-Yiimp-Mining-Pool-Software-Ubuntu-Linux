<?php

// DB Settings, initially made for trade settings on exchanges

function settings_key_type($key)
{
	if (strpos($key, 'enabled') !== false) return 'bool';
	if (strpos($key, 'disabled') !== false) return 'bool';
	if (substr($key, -3) == 'pct') return 'percent';
	if (substr($key, -3) == 'btc') return 'price';
	if (substr($key, -5) == 'price') return 'price';
	return 'string';
}

function settings_get($key, $default=null)
{
	$row = dborow("SELECT value, type FROM settings WHERE param=:key", array(':key'=>$key));
	if (!$row) return $default;

	$type = arraySafeVal($row, 'type', settings_key_type($key));
	$value = $row['value'];
	switch ($type) {
	case 'bool':
		return (bool) $value;
	case 'int':
		return intval($value);
	case 'percent':
		return ((double) $value) / 100.0;
	case 'price':
	case 'real':
		return (double) $value;
	case 'json':
		return json_decode($value, true);
	}
	return $value;
}

function settings_set($key, $value)
{
	$type = settings_key_type($key);
	if ($type == 'json') {
		$value = json_encode($value);
		if (strlen($value) > 255) {
			debuglog("warning: settings_set($key) value is too long!");
			return false;
		}
	}

	if ($type == 'bool' && strcasecmp($value,'true') == 0) $value = 1;
	if ($type == 'bool' && strcasecmp($value,'false')== 0) $value = 0;
	if ($type == 'percent' && strpos($value, '%') === false) $value = floatval($value) * 100;

	dborun("INSERT IGNORE INTO settings(param,value) VALUES (:key,:val)", array(
		':key'=>$key,':val'=>$value
	));
	dborun("UPDATE settings SET value=:val, type=:type WHERE param=:key", array(
		':key'=>$key,':val'=>$value,':type'=>$type
	));
	return true;
}

function settings_unset($key)
{
	dborun("DELETE FROM settings WHERE param=:key", array(':key'=>$key));
	return 0;
}

function exchange_get($exchange, $key, $default=null)
{
	$value = settings_get("$exchange-$key", $default);
	return $value;
}

function exchange_set($exchange, $key, $value)
{
	return settings_set("$exchange-$key", $value);
}

function exchange_unset($exchange, $key)
{
	// reset to default value
	return settings_unset("$exchange-$key");
}

/**
 * Returns the list of valid keys, with a description
 */
function exchange_valid_keys()
{
	return array(
		'disabled' => 'Fully disable the exchange',

		'trade_min_btc'			=> 'Minimum order on the exchange',
		'trade_sell_ask_pct'	=> 'Initial order ask price related to the lowest ask (in %)',
		'trade_cancel_ask_pct'	=> 'Cancel orders if the lowest ask reach this % of your order',

		'withdraw_btc_address'	=> 'Custom withdraw BTC address for the exchange',
		'withdraw_min_btc'		=> 'Auto withdraw when your BTC balance reach this amount (0=disabled)',
		'withdraw_fee_btc'		=> 'Fees in BTC required to withdraw BTCs on the exchange',
	);
}
