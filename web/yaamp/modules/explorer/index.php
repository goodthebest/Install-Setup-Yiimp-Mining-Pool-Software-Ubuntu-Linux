<?php

echo "<br>";
echo "<div class='main-left-box'>";
echo "<div class='main-left-title'>Block Explorer</div>";
echo "<div class='main-left-inner'>";

showTableSorter('maintable', '{headers: {0: {sorter: false}, 9: {sorter: false}}}');

echo <<<end
<thead>
<tr>
<th width="30"></th>
<th>Name</th>
<th>Symbol</th>
<th>Algo</th>
<th>Version</th>
<th>Height</th>
<th>Difficulty</th>
<th>Connections</th>
<th>Network Hash</th>
<th></th>
</tr>
</thead><tbody>
end;

$list = getdbolist('db_coins', "enable and visible order by name");
foreach($list as $coin)
{
	if($coin->symbol == 'BTC') continue;
	if(!empty($coin->symbol2)) continue;

	$coin->version = formatWalletVersion($coin);

	//if (!$coin->network_hash)
		$coin->network_hash = controller()->memcache->get("yiimp-nethashrate-{$coin->symbol}");
	if (!$coin->network_hash) {
		$remote = new Bitcoin($coin->rpcuser, $coin->rpcpasswd, $coin->rpchost, $coin->rpcport);
		if ($remote)
			$info = $remote->getmininginfo();
		if (isset($info['networkhashps'])) {
			$coin->network_hash = $info['networkhashps'];
			controller()->memcache->set("yiimp-nethashrate-{$coin->symbol}", $info['networkhashps'], 60);
		}
		else if (isset($info['netmhashps'])) {
			$coin->network_hash = floatval($info['netmhashps']) * 1e6;
			controller()->memcache->set("yiimp-nethashrate-{$coin->symbol}", $coin->network_hash, 60);
		}
	}

	$difficulty = Itoa2($coin->difficulty, 3);
	$nethash = $coin->network_hash? strtoupper(Itoa2($coin->network_hash)).'H/s': '';

	echo '<tr class="ssrow">';
	echo '<td><img src="'.$coin->image.'" width="18"></td>';

	echo "<td><b><a href='/explorer?id=$coin->id'>$coin->name</a></b></td>";
	echo "<td><b>$coin->symbol</b></td>";

	echo "<td>$coin->algo</td>";
	echo "<td>$coin->version</td>";

	echo "<td>$coin->block_height</td>";
	echo "<td>$difficulty</td>";
	$cnx_class = (intval($coin->connections) > 3) ? '' : 'low';
	echo '<td class="'.$cnx_class.'">'.$coin->connections.'</td>';
	echo "<td>$nethash</td>";

	echo "<td>";

	if(!empty($coin->link_bitcointalk))
		echo CHtml::link('forum', $coin->link_bitcointalk, array('target'=>'_blank'));

	elseif(!empty($coin->link_site))
		echo CHtml::link('site', $coin->link_site, array('target'=>'_blank'));

	echo "</td>";
	echo "</tr>";
}

echo <<<end
</tbody>
</table>

<style type="text/css">
td.low { color: red; font-weight: bold; }
</style>

<br></div></div>

<br><br><br><br><br><br><br><br><br><br>
<br><br><br><br><br><br><br><br><br><br>
<br><br><br><br><br><br><br><br><br><br>

end;

