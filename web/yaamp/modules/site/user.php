<?php

echo getAdminSideBarLinks();

$symbol = getparam('symbol');
$coins = "<option value='all'>-all-</option>";

$list = getdbolist('db_coins', "enable AND id IN (select distinct coinid from accounts where balance>0.0001)");
foreach($list as $coin)
{
	if($coin->symbol == $symbol)
		$coins .= "<option value='$coin->symbol' selected>$coin->symbol</option>";
	else
		$coins .= "<option value='$coin->symbol'>$coin->symbol</option>";
}


echo <<<end

<div align="right" style="margin-top: -14px; margin-bottom: 6px;">
Select coin: <select id='coin_select'>$coins</select>&nbsp;
</div>

<div id='main_results'></div>

<br><br><br><br><br><br><br><br><br><br>
<br><br><br><br><br><br><br><br><br><br>
<br><br><br><br><br><br><br><br><br><br>
<br><br><br><br><br><br><br><br><br><br>

<script>

$(function()
{
	$('#coin_select').change(function(event)
	{
		var symbol = $('#coin_select').val();
		window.location.href = '/site/user?symbol='+symbol;
	});

	main_refresh();
});

var main_delay=30000;
var main_timeout;

function main_ready(data)
{
	$('#main_results').html(data);
	main_timeout = setTimeout(main_refresh, main_delay);
}

function main_error()
{
	main_timeout = setTimeout(main_refresh, main_delay*2);
}

function main_refresh()
{
	var symbol = $('#coin_select').val();
	var url = "/site/user_results?symbol="+symbol;

	clearTimeout(main_timeout);
	$.get(url, '', main_ready).error(main_error);
}

</script>

end;

echo "Note: this table show users with a non-zero balance.";