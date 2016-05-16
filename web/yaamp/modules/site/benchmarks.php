<br>

<div class="main-left-box">
<div class="main-left-title">YIIMP BENCHMARKS</div>
<div class="main-left-inner">

<p style="width: 700px;">YiiMP now allow users to share their ccminer (1.7.6+) device hashrate, more supported miners will come later.</p>

<pre class="main-left-box" style='padding: 3px; font-size: .9em; background-color: #ffffee; font-family: monospace;'>
-o stratum+tcp://<?= YAAMP_STRATUM_URL ?>:&lt;PORT&gt; -a &lt;algo&gt; -u &lt;wallet_adress&gt; -p stats
</pre>

<p style="width: 700px;">You can download the compatible version of ccminer here :</p>

<ul>
<li><a href="http://ccminer.org/preview/ccminer.exe">http://ccminer.org/preview/ccminer.exe</a> (x86 CUDA 6.5)</li>
<li><a href="http://ccminer.org/preview/ccminer-x64.exe">http://ccminer.org/preview/ccminer-x64.exe</a> (x64 CUDA 7.5)</li>
</ul>

<p style="width: 700px;">
With this option enabled, the stratum will ask for device stats each 50 shares (for 4 times max).<br/>
<br/>
You can combine this miner option with other ones, like the <a href="/site/diff">pool difficulty</a> with a comma.
</p>

<br><br><br><br><br><br><br><br><br><br>
<br><br><br><br><br><br><br><br><br><br>
<br><br><br><br><br><br><br><br><br><br>

