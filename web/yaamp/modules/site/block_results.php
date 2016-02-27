<?php

JavascriptFile("/yaamp/ui/js/jquery.metadata.js");
JavascriptFile("/yaamp/ui/js/jquery.tablesorter.widgets.js");

$id = getiparam('id');
if($id)
	$db_blocks = getdbolist('db_blocks', "coin_id=:id order by time desc limit 250", array(':id'=>$id));
else
	$db_blocks = getdbolist('db_blocks', "1 order by time desc limit 250");

showTableSorter('maintable', "{
	tableClass: 'dataGrid',
	headers: {
		0:{sorter:false},
		1:{sorter:false},
		2:{sorter:'metadata'},
		3:{sorter:'numeric'},
		4:{sorter:'currency'},
		5:{sorter:'text'},
		6:{sorter:'numeric'},
		7:{sorter:'numeric'},
		8:{sorter:'text'}
	},
	widgets: ['zebra','filter'],
	widgetOptions: {
		filter_columnFilters: false,
		filter_ignoreCase: true
	}
}");

echo <<<end
<style type="text/css">
td.orphan { color: darkred; }
</style>

<thead>
<tr>
<th width="20"></th>
<th>Name</th>
<th>Time</th>
<th>Height</th>
<th>Amount</th>
<th>Status</th>
<th>Difficulty</th>
<th>Found Diff</th>
<th>Blockhash</th>
</tr>
</thead><tbody>
end;

foreach($db_blocks as $db_block)
{
	if(!$db_block->coin_id) continue;

	$coin = getdbo('db_coins', $db_block->coin_id);
	if(!$coin) continue;

	if($db_block->category == 'stake' && !$this->admin) continue;
	if($db_block->category == 'generated' && !$this->admin) continue; // mature stake income

//	$remote = new Bitcoin($coin->rpcuser, $coin->rpcpasswd, $coin->rpchost, $coin->rpcport);

// 	$blockext = $remote->getblock($db_block->blockhash);
// 	$tx = $remote->gettransaction($blockext['tx'][0]);

// 	$db_block->category = $tx['details'][0]['category'];

	if($db_block->category == 'immature')
		echo "<tr style='background-color: #e0d3e8;'>";
	else
		echo "<tr class='ssrow'>";

	echo '<td><img width="16" src="'.$coin->image.'"></td>';

	echo '<td>';
	if ($this->admin)
		echo '<a href="/site/coin?id='.$coin->id.'"><b>'.$coin->name.'</b></a>';
	else
		echo '<b>'.$coin->name.'</b>';
	echo '&nbsp;('.$coin->symbol.')</td>';

//	$db_block->confirmations = $blockext['confirmations'];
//	$db_block->save();

	$d = datetoa2($db_block->time);
	echo '<td data="'.$db_block->time.'"><b>'.$d.' ago</b></td>';

	if (YIIMP_PUBLIC_EXPLORER)
		echo '<td><a href="/explorer?id='.$coin->id.'&height='.$db_block->height.'">'.$db_block->height.'</a></td>';
	else
		echo "<td>$db_block->height</td>";

	echo "<td>$db_block->amount</td>";

	echo '<td class="'.strtolower($db_block->category).'">';

	if($db_block->category == 'orphan')
		echo "Orphan";

	else if($db_block->category == 'immature')
		echo "Immature ({$db_block->confirmations})";

	else if($db_block->category == 'generate')
		echo 'Confirmed';

	else if($db_block->category == 'stake')
		echo "Stake ({$db_block->confirmations})";

	else if($db_block->category == 'generated')
		echo 'Stake';

	echo "</td>";

	echo "<td>$db_block->difficulty</td>";
	echo "<td>$db_block->difficulty_user</td>";

	echo '<td style="font-size: .8em; font-family: monospace;">';
	if (YIIMP_PUBLIC_EXPLORER)
		echo '<a href="/explorer?id='.$coin->id.'&hash='.$db_block->blockhash.'">'.$db_block->blockhash.'</a>';
	else
		echo $db_block->blockhash;
	echo "</td>";
	echo "</tr>";
}

echo "</tbody></table>";









