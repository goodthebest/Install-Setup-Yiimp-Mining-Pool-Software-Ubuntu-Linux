<?php

if (isset($_GET['algo']))
	user()->setState('yaamp-algo', $_GET['algo']);

$algo = user()->getState('yaamp-algo');

JavascriptFile("/yaamp/ui/js/jquery.metadata.js");
JavascriptFile("/yaamp/ui/js/jquery.tablesorter.widgets.js");

echo <<<end
<div align="right" style="margin-top: -20px; margin-bottom: 6px;">
<input class="search" type="search" data-column="all" style="width: 140px;" placeholder="Search..." />
</div>
<style type="text/css">
tr.ssrow.filtered { display: none; }
</style>
end;

showTableSorter('maintable', "{
	tableClass: 'dataGrid',
	headers: {
		0:{sorter:'metadata'},
		1:{sorter:'text'},
		2:{sorter:'text'},
		3:{sorter:'text'},
		4:{sorter:'text'},
		5:{sorter:'text'},
		6:{sorter:'metadata'},
		7:{sorter:'numeric'},
		8:{sorter:'numeric'},
		9:{sorter:'numeric'},
		10:{sorter:'numeric'},
		11:{sorter:'numeric'},
		12:{sorter:'text'}
	},
	widgets: ['zebra','filter','Storage','saveSort'],
	widgetOptions: {
		saveSort: true,
		filter_saveFilters: false,
		filter_external: '.search',
		filter_columnFilters: false,
		filter_childRows : true,
		filter_ignoreCase: true
	}
}");

echo <<<end
<thead>
<tr>
<th width="20"></th>
<th>Coin</th>
<th>Address</th>
<th>Pass</th>
<th>Client</th>
<th>Version</th>
<th>Hashrate</th>
<th>Diff</th>
<th>Shares</th>
<th>Bad</th>
<th>%</th>
<th>Found</th>
<th></th>
<th></th>
</tr>
</thead><tbody>
end;

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
	$user = $coin = NULL;
	$coinimg = ''; $coinlink = ''; $coinsym = ''; $shares= '';
	if ($worker->userid) {
		$user = getdbo('db_accounts', $worker->userid);
		if ($user) {
			$coin = getdbo('db_coins', $user->coinid);
			$coinsym = $coin->symbol;
			$coinimg = CHtml::image($coin->image, $coin->symbol, array('width'=>'16'));
			$coinlink = CHtml::link($coin->name, '/site/coin?id='.$coin->id);
		}
		$name = empty($name) ? $user->login : $name;
		$gift = $user->donation;
	}

	$dns = !empty($worker->dns)? $worker->dns: $worker->ip;
	if(strlen($worker->dns) > 40)
		$dns = '...'.substr($worker->dns, strlen($worker->dns) - 40);

	echo "<tr class='ssrow'>";
	echo '<td width="20">'.$coinimg.'</td>';
	echo '<td><b>'.$coinlink.'</b>&nbsp;('.$coinsym.')</td>';
	echo "<td><a href='/?address=$worker->name'><b>$worker->name</b></a></td>";
	echo "<td>$worker->password</td>";
	echo "<td title='$worker->ip'>$dns</td>";
	echo "<td>$worker->version</td>";
	echo "<td>$user_rate</td>";
	echo "<td>$worker->difficulty</td>";

	$shares = dboscalar("SELECT COUNT(id) as shares FROM shares WHERE workerid=:worker AND algo=:algo", array(
		':worker'=> $worker->id,
		':algo'=> $algo
	));
	echo "<td>$shares</td>";

	echo "<td>";
	if ($user_bad > 0) {
		if ($pct_bad > 50)
			echo "<b> {$pct_bad}%</b>";
		else
			echo " {$pct_bad}%";
	}
	echo "</td>";

	$worker_blocs = dboscalar("SELECT COUNT(id) as blocs FROM blocks WHERE workerid=:worker AND algo=:algo", array(
		':worker'=> $worker->id,
		':algo'=> $algo
	));
	$user_blocs = dboscalar("SELECT COUNT(id) as blocs FROM blocks WHERE userid=:user AND algo=:algo
		AND time > (SELECT min(time) FROM workers WHERE algo=:algo)", array(
		':user'=> $worker->userid,
		':algo'=> $algo
	));
	echo '<td>'.number_format($percent,1,'.','').'%</td>';

	echo '<td>'.$worker_blocs.' / '.$user_blocs.'</td>';
	echo '<td>'.$name.'</td>';
	echo '<td>'.($gift ? "$gift&nbsp;%" : '').'</td>';
	echo '</tr>';
}

echo "</tbody></table>";




