<?php

$algo = user()->getState('yaamp-algo');

echo "<br><table class='dataGrid'>";
echo "<thead>";
echo "<tr>";
echo "<th>Wallet</th>";
echo "<th>Pass</th>";
echo "<th>Client</th>";
echo "<th>Version</th>";
echo "<th>Diff</th>";
echo "<th>Hashrate</th>";
echo "<th>Bad</th>";
echo "<th>%</th>";
echo "<th></th>";
echo "</tr>";
echo "</thead><tbody>";

$workers = getdbolist('db_workers', "algo=:algo order by name", array(':algo'=>$algo));

$total_rate = 0.0;
foreach($workers as $worker)
{
	$total_rate += yaamp_worker_rate($worker->id);
}

foreach($workers as $worker)
{
	$user_rate = yaamp_worker_rate($worker->id);
	$percent = 0.0;
	if ($total_rate) $percent = (100.0 * $user_rate) / $total_rate;
	$user_bad = yaamp_worker_rate_bad($worker->id);
	$pct_bad = ($user_rate+$user_bad)? round($user_bad*100/($user_rate+$user_bad), 3): 0;
	$user_rate = Itoa2($user_rate).'h/s';

	$name = $worker->worker;
	if ($worker->userid) {
		$user = getdbosql('db_accounts', 'id=:id', array(':id'=>$worker->userid));
		$name = $user->login;
	}

	$dns = !empty($worker->dns)? $worker->dns: $worker->ip;
	if(strlen($worker->dns) > 40)
		$dns = '...'.substr($worker->dns, strlen($worker->dns) - 40);

	echo "<tr class='ssrow'>";
	echo "<td><a href='/?address=$worker->name'><b>$worker->name</b></a></td>";
	echo "<td>$worker->password</td>";
	echo "<td title='$worker->ip'>$dns</td>";
	echo "<td>$worker->version</td>";
	echo "<td>$worker->difficulty</td>";
	echo "<td>$user_rate</td>";

	echo "<td>". ($user_bad ? Itoa2($user_bad).'h/s' : '-');
	if ($user_bad) {
		if ($pct_bad > 50)
			echo "<b> {$pct_bad}%</b>";
		else
			echo " {$pct_bad}%";
	}
	echo "</td>";

	echo '<td>'.number_format($percent,1,'.','').'%</td>';

	echo "<td>$name</td>";
	echo "</tr>";
}

echo "</tbody></table>";




