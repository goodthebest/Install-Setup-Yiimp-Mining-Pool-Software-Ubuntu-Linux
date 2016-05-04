<?php

if (!$coin) $this->goback();
$this->pageTitle = 'Console - '.$coin->symbol;

$remote = new Bitcoin($coin->rpcuser, $coin->rpcpasswd, $coin->rpchost, $coin->rpcport);

echo getAdminSideBarLinks().'<br/><br/>';

$info = $remote->getinfo();
if (!$info) {
	echo $remote->error;
	return;
}

echo getAdminWalletLinks($coin, $info, 'console').'<br/><br/>';

//////////////////////////////////////////////////////////////////////////////////////

function humanizeJson($json)
{
	// timestamps like "blocktime": 1462359961,
	$res = preg_match_all("#\": ([0-9]{10}),#", $json, $matches, PREG_OFFSET_CAPTURE);
	if ($res) foreach($matches[1] as $m) {
		$ts = intval($m[0]);
		$date = strftime("%Y-%m-%d %T %z", $ts);
		$json = str_replace(' '.$m[0].",", ' "'.$date.'",', $json);
	}
	return $json;
}

//////////////////////////////////////////////////////////////////////////////////////

$last_query = htmlentities(trim($query));

echo <<<end
<script type="text/javascript">
function main_resize() {
	var w = 0 + jQuery('div.form').width();
	var wpx = (w - 100).toString() + 'px';
	jQuery('.main-text-input').css({width: wpx});
}
</script>

<style type="text/css">
div.form { margin-right: 8px; }
pre.rpcerror { color: darkred; background: transparent; padding: 4px; margin-top: 0; margin-bottom: -12px; margin-right: 8px; }
pre.terminal { color: silver; background: black; padding: 4px; min-height: 180px; margin-right: 8px; }
</style>

<div class="form">
<form action="/site/console?id={$coin->id}" method="post" style="padding: 0px;">
<input class="main-text-input" value="{$last_query}" type="text" name="query" placeholder="Query" style="width: 50%; margin-right: 4px;">
<input class="main-submit-button" type="submit" value="Execute" style="width: 80px;">
</form>
</div>
end;

$result = '';

if (!empty($query)) try {
	$params = split(' ', trim($query));
	$command = array_shift($params);

	$p = array();
	foreach ($params as $param) {
		$param = (is_numeric($param)) ? 0 + $param : trim($param,'"');
		$p[] = $param;
	}

	switch (count($params)) {
	case 0:
		$result = $remote->$command();
		break;
	case 1:
		$result = $remote->$command($p[0]);
		break;
	case 2:
		$result = $remote->$command($p[0], $p[1]);
		break;
	case 3:
		$result = $remote->$command($p[0], $p[1], $p[2]);
		break;
	case 4:
		$result = $remote->$command($p[0], $p[1], $p[2], $p[3]);
		break;
	case 5:
		$result = $remote->$command($p[0], $p[1], $p[2], $p[3], $p[4]);
		break;
	case 6:
		$result = $remote->$command($p[0], $p[1], $p[2], $p[3], $p[4], $p[5]);
		break;
	case 7:
		$result = $remote->$command($p[0], $p[1], $p[2], $p[3], $p[4], $p[5], $p[6]);
		break;
	default:
		$result = 'error: too much parameters';
	}

} catch (Exception $e) {
	$result = $remote->error;
}

if (!empty($remote->error) && $remote->error != $result) {
	$err = $remote->error;
	echo '<pre class="rpcerror">';
	echo is_string($err) ? htmlentities($err) : htmlentities(json_encode($err, 128));
	echo '</pre>';
}

echo '<pre class="terminal">';
echo is_string($result) ? htmlentities($result) : htmlentities(humanizeJson(json_encode($result, 128)));
echo '</pre>';

JavascriptReady("main_resize(); $(window).resize(main_resize); $('.main-text-input:first').focus();");
