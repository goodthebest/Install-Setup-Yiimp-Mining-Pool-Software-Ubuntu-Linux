<?php

JavascriptFile("/yaamp/ui/js/jquery.metadata.js");
JavascriptFile("/yaamp/ui/js/jquery.tablesorter.widgets.js");

echo <<<end
<div align="right" style="margin-bottom: 6px;">
<input class="search" type="search" data-column="all" style="width: 140px;" placeholder="Search..." />
</div>
<style type="text/css">
tr.ssrow.filtered { display: none; }
</style>
end;

showTableSorter('maintable', "{
	tableClass: 'dataGrid',
	headers: {
		0:{sorter:false},
		1:{sorter:'text'},
		2:{sorter:'text'},
		3:{sorter:'text'},
		4:{sorter:'text'},
		5:{sorter:'text'},
		6:{sorter:'metadata'},
		7:{sorter:'numeric'},
		8:{sorter:'text'},
		9:{sorter: false }
	},
	widgets: ['zebra','filter'],
	widgetOptions: {
		filter_external: '.search',
		filter_columnFilters: false,
		filter_childRows : true,
		filter_ignoreCase: true
	}
}");

echo <<<end
<thead><tr>
<th width="30"></th>
<th>Name</th>
<th>Symbol</th>
<th>Algo</th>
<th>Status</th>
<th>Version</th>
<th>Created</th>
<th>Height</th>
<th>Message</th>
<th>Links</th>
</tr></thead>
<tbody>
end;

$total_active = 0;
$total_installed = 0;

$coins = getdbolist('db_coins', "1 order by id desc");
foreach($coins as $coin)
{
//	if($coin->symbol == 'BTC') continue;
	if($coin->enable) $total_active++;
	if($coin->installed) $total_installed++;

	$coin->errors = substr($coin->errors, 0, 30);
	$coin->version = substr($coin->version, 0, 20);
	$difficulty = Itoa2($coin->difficulty, 3);
	$created = datetoa2($coin->created);

	echo "<tr class='ssrow' title='$coin->specifications'>";
	echo "<td><img src='$coin->image' width=18></td>";

	echo "<td><b><a href='/coin/update?id=$coin->id'>$coin->name</a></b></td>";

	if($this->admin)
		echo "<td><b><a href='/site/update?id=$coin->id'>$coin->symbol</a></b></td>";
	else
		echo "<td><b>$coin->symbol</b></td>";

	echo "<td>$coin->algo</td>";

	if($coin->enable)
		echo "<td>running</td>";

	else if($coin->installed)
		echo "<td>installed</td>";

	else
		echo "<td></td>";

	echo "<td>$coin->version</td>";
	echo '<td data="'.$coin->created.'">'.$created.'</td>';

//	echo "<td align=right>$difficulty</td>";
	echo '<td align="center">'.$coin->block_height.'</td>';

	echo "<td>$coin->errors</td>";
	echo "<td>";

	if(!empty($coin->link_bitcointalk))
		echo "<a href='$coin->link_bitcointalk' target=_blank>forum</a> ";

	if(!empty($coin->link_github))
		echo "<a href='$coin->link_github' target=_blank>git</a> ";

//	if(!empty($coin->link_explorer))
//		echo "<a href='$coin->link_explorer' target=_blank>expl</a> ";

	echo "<a href='http://google.com/search?q=$coin->name%20$coin->symbol%20bitcointalk' target=_blank>google</a> ";

//	if(!empty($coin->link_exchange))
//		echo "<a href='$coin->link_exchange' target=_blank>exch</a> ";

	$list2 = getdbolist('db_markets', "coinid=$coin->id");
	foreach($list2 as $market)
	{
		$url = getMarketUrl($coin, $market->name);
		echo "<a href='$url' target=_blank>$market->name</a> ";
	}


	echo "</td>";
	echo "</tr>";
}

echo "</tbody>";

$total = count($coins);

echo "<tr class='ssrow'>";
echo "<td></td>";
echo '<td colspan="6">';
echo "<b>$total coins, $total_installed installed, $total_active running</b>";
echo '<br/><br/><a href="/coin/create">Add a coin</a>';
echo '<td style="display: none;"></td>';
echo '<td style="display: none;"></td>';
echo '<td style="display: none;"></td>';
echo '<td style="display: none;"></td>';
echo '<td style="display: none;" data="0"></td>';
echo '</td>';
echo "</tr>";

echo "</table>";

echo "<br><br><br><br><br>";
echo "<br><br><br><br><br>";
