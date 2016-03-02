<?php
echo getAdminSideBarLinks();

$coin_id = getiparam('id');
if ($coin_id) {
	$coin = getdbo('db_coins', $coin_id);
	$this->pageTitle = 'Earnings - '.$coin->symbol;
}
?>

<div id='main_results'></div>

<br><br><br><br><br><br><br><br><br><br>
<br><br><br><br><br><br><br><br><br><br>
<br><br><br><br><br><br><br><br><br><br>
<br><br><br><br><br><br><br><br><br><br>

<script>

$(function()
{
	main_refresh();
});

var main_delay=60000;
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
	var url = '/site/earning_results?id=<?= $coin_id ?>';

	clearTimeout(main_timeout);
	$.get(url, '', main_ready).error(main_error);
}

</script>



