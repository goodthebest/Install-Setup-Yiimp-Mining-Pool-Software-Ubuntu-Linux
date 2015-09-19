<?php

echo getAdminSideBarLinks();

$server = getparam('server');

echo <<<end
<div align="right" style="margin-top: -14px; margin-bottom: 6px;">
Select Server:
<select id='server_select'>
<option value=''>all</option>
<option value='yaamp1'>yaamp1</option>
<option value='yaamp2'>yaamp2</option>
<option value='yaamp3'>yaamp3</option>
<option value='yaamp4'>yaamp4</option>
<option value='yaamp5'>yaamp5</option>
<option value='yaamp6'>yaamp6</option>
</select>&nbsp;
<input class="search" type="search" data-column="all" style="width: 140px;" placeholder="Search..." />
</div>

<div id='main_results'>
<br><br><br><br><br><br><br><br><br><br>
<br><br><br><br><br><br><br><br><br><br>
<br><br><br><br><br><br><br><br><br><br>
<br><br><br><br><br><br><br><br><br><br>
<br><br><br><br><br><br><br><br><br><br>
</div>

<br><a href='/site/create'><img width=16 src=''><b>CREATE COIN</b></a>
<!-- br><a href='/site/updateprice'><img width=16 src=''><b>UPDATE PRICE</b></a -->
<!-- br><a href='/site/dopayments'><img width=16 src=''><b>DO PAYMENTS</b></a -->

<br><br><br>

<script>

$('#server_select').change(function(event)
{
	var server = $('#server_select').val();
	window.location.href = '/site/admin?server='+server;
});

//var current_hash;

$(function()
{
//	current_hash = window.location.hash;
//	window.location.hash = '';

	main_refresh();
});

var main_delay=30000;
var main_timeout;

function main_ready(data)
{
	$('#main_results').html(data);
	$('#server_select').val('{$server}');

//	window.location.hash = current_hash;
	main_timeout = setTimeout(main_refresh, main_delay);
}

function main_error()
{
	main_timeout = setTimeout(main_refresh, main_delay*2);
}

function main_refresh()
{
	var url = "/site/admin_results?server=$server";

	clearTimeout(main_timeout);
	$.get(url, '', main_ready).error(main_error);
}

</script>

end;


