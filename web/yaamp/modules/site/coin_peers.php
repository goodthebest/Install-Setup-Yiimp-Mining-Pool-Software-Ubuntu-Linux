<?php

if (!$coin) $this->goback();

$remote = new Bitcoin($coin->rpcuser, $coin->rpcpasswd, $coin->rpchost, $coin->rpcport);
$info = $remote->getinfo();

echo getAdminSideBarLinks().'<br/><br/>';
echo getAdminWalletLinks($coin, $info, 'peers').'<br/><br/>';

//////////////////////////////////////////////////////////////////////////////////////

JavascriptFile("/yaamp/ui/js/jquery.metadata.js");
JavascriptFile("/yaamp/ui/js/jquery.tablesorter.widgets.js");

$list = $remote->getpeerinfo();

showTableSorter('maintable', "{
	tableClass: 'dataGrid',
	headers: {
		0:{sorter:'text'},
		1:{sorter:'text'},
		2:{sorter:'numeric'},
		3:{sorter:'numeric'},
		4:{sorter:'text'},
		5:{sorter:'metadata'},
		6:{sorter:'metadata'},
		7:{sorter:'numeric'}
	},
	widgets: ['zebra','Storage','saveSort'],
	widgetOptions: {
		saveSort: true
	}
}");

echo <<<end
<thead>
<tr>
<th>Address</th>
<th>Version</th>
<th>Height</th>
<th>Ping</th>
<th>Services</th>
<th>Since</th>
<th>Last</th>
<th>Rx / Tx (kB)</th>
</tr>
</thead><tbody>
end;

$addnode = array();
$version = '';

foreach($list as $peer)
{
	echo '<tr class="ssrow">';

	$node = arraySafeVal($peer,'addr');
	echo '<td>'.$node.'</td>';
	$addnode[] = ($coin->symbol=='DCR' ? 'addpeer=' : 'addnode=') . $node;

	$version = max($version, arraySafeVal($peer,'version').' '.arraySafeVal($peer,'subver'));
	echo '<td>'.arraySafeVal($peer,'version').' '.arraySafeVal($peer,'subver').'</td>';

	$height = arraySafeVal($peer,'currentheight');
	if (!$height) $height = arraySafeVal($peer,'synced_blocks');
	echo '<td>'.$height.'</td>';

	echo '<td>'.arraySafeVal($peer,'pingtime','').'</td>';
	echo '<td>'.arraySafeVal($peer,'services','').'</td>';

	$conntime = arraySafeVal($peer,'conntime',time());
	$startingheight = arraySafeVal($peer,'startingheight');
	echo '<td>'.datetoa2($conntime)." ($startingheight)".'</td>';

	$lastrecv = arraySafeVal($peer,'lastrecv',time());
	$lastsend = arraySafeVal($peer,'lastsend',time());
	echo '<td>'.datetoa2(max($lastrecv,$lastsend)).'</td>';

	$bytesrecv = round(arraySafeVal($peer,'bytesrecv')/1024.,1);
	$bytessent = round(arraySafeVal($peer,'bytessent')/1024.,1);
	if ($bytesrecv+$bytessent)
		echo '<td>'."$bytesrecv / $bytessent".'</td>';
	else
		echo '<td></td>';
	echo '</tr>';
}

echo '</tbody></table><br>';

echo '<b>Last version: </b>'.$version;
echo '<pre>';
echo implode("\n",$addnode);
echo '</pre>';
//echo json_encode($list);