<br>

<div class="main-left-box">
<div class="main-left-title">YIIMP API</div>
<div class="main-left-inner">

<p>Simple REST API.</p>

<p><b>Pool Status</b></p>

request:
<p class="main-left-box" style='padding: 3px; font-size: .8em; background-color: #ffffee; font-family: monospace;'>
	http://<?=YAAMP_SITE_URL?>/api/status</p>

result:
<pre class="main-left-box" style='padding: 3px; font-size: .8em; background-color: #ffffee; font-family: monospace;'>
{
	"x11":
	{
		"coins": 10,
		"fees": 1,
		"hashrate": 269473938,
		"workers": 1,
		"lastbloc": 35101,
		"timesincelast": 437,
		"estimate_current": 0.00053653,
		"estimate_last24h": 0.00036408,
		"actual_last24h": 0.00035620
	},

	...
}
</pre>


<p><b>Wallet Status</b></p>

request:
<p class="main-left-box" style='padding: 3px; font-size: .8em; background-color: #ffffee; font-family: monospace;'>
	http://<?=YAAMP_SITE_URL?>/api/wallet?address=BITCOIN_WALLET</p>

result:
<pre class="main-left-box" style='padding: 3px; font-size: .8em; background-color: #ffffee; font-family: monospace;'>
{
	"unsold": 0.00050362,
	"balance": 0.00000000,
	"unpaid": 0.00050362,
	"paid": 0.00000000,
	"total": 0.00050362
}
</pre>

<!--
<p><b>Rental Status</b></p>

request:
<p class="main-left-box" style='padding: 3px; font-size: .8em; background-color: #ffffee; font-family: monospace;'>
	http://<?=YAAMP_SITE_URL?>/api/rental?key=API_KEY</p>

result:
<pre class="main-left-box" style='padding: 3px; font-size: .8em; background-color: #ffffee; font-family: monospace;'>
{
	"balance": 0.00000000,
	"unconfirmed": 0.00000000,
	"jobs":
	[
		{
			"jobid": "19",
			"algo": "x11",
			"price": "1",
			"hashrate": "1000000",
			"server": "stratum.server.com",
			"port": "3333",
			"username": "1A5pAdfWLUFXoqcUb6N9Fre2EApr5QLNdG",
			"password": "xx",
			"started": "1",
			"active": "1",
			"accepted": "586406.2014805333",
			"rejected": "",
			"diff": "0.04"
		},

		...

	]
}
</pre>

<p><b>Rental Price</b></p>

<p>Set the rental price of a job.</p>

request:
<p class="main-left-box" style='padding: 3px; font-size: .8em; background-color: #ffffee; font-family: monospace;'>
	http://<?=YAAMP_SITE_URL?>/api/rental_price?key=API_KEY&jobid=xx&price=xx</p>

</pre>

<p><b>Rental Hashrate</b></p>

<p>Set the rental max hashrate of a job.</p>

request:
<p class="main-left-box" style='padding: 3px; font-size: .8em; background-color: #ffffee; font-family: monospace;'>
	http://<?=YAAMP_SITE_URL?>/api/rental_hashrate?key=API_KEY&jobid=xx&hashrate=xx</p>

</pre>

<p><b>Start Rental Job</b></p>

request:
<p class="main-left-box" style='padding: 3px; font-size: .8em; background-color: #ffffee; font-family: monospace;'>
	http://<?=YAAMP_SITE_URL?>/api/rental_start?key=API_KEY&jobid=xx</p>

</pre>

<p><b>Stop Rental Job</b></p>

request:
<p class="main-left-box" style='padding: 3px; font-size: .8em; background-color: #ffffee; font-family: monospace;'>
	http://<?=YAAMP_SITE_URL?>/api/rental_stop?key=API_KEY&jobid=xx</p>

</pre>
-->

<br><br>

</div></div>

<br><br><br><br><br><br><br><br><br><br>
<br><br><br><br><br><br><br><br><br><br>
<br><br><br><br><br><br><br><br><br><br>
<br><br><br><br><br><br><br><br><br><br>

<script>


</script>


