<?php
/**
 * API-call related functions
 *
 * @author Remdev
 * @license MIT License - https://github.com/Remdev/PHP-ccex-api
 */

class CcexAPI
{

    protected $api_url = 'https://c-cex.com/t/';
    protected $api_key = EXCH_CCEX_KEY;
    protected $api_secret; // = EXCH_CCEX_SECRET; // not used yet

//  public function __construct($api_key = '') {
//       $this->api_key = $api_key;
//  }

    protected function jsonQuery($url)
    {
        $opts = array(
            'http' => array(
                'method'  => 'GET',
                'timeout' => 10
            )
        );

        $context = stream_context_create($opts);
        $feed = @file_get_contents($url, false, $context);

        if(!$feed) {

            debuglog("c-cex error $url");
            return null; //array('error' => 'Invalid parameters');

        } else {

            $a = json_decode($feed, true);
            if(!$a) debuglog("c-cex: $feed");

            return $a;
        }
    }

    public function getTickerInfo($pair){
        $json = $this->jsonQuery($this->api_url.$pair.'.json');
        return $json['ticker'];
    }

    public function getCoinNames(){
       $json = $this->jsonQuery($this->api_url.'coinnames.json');
       return is_array($json) ? $json : array();
    }

    public function getMarkets(){
       $json = $this->jsonQuery($this->api_url.'api_pub.html?a=getmarkets');
       return isset($json['result']) ? $json['result'] : array();
    }

    public function getPairs(){
       $json = $this->jsonQuery($this->api_url.'pairs.json');
       return isset($json['pairs'])? $json['pairs']: array();
    }

    public function getVolumes($hours=24,$pair=false){
        $url = ($pair) ? 'volume' : 'lastvolumes&pair='.$pair.'&';
        return $this->jsonQuery($this->api_url."s.html?a=".$url."&h=".$hours);
    }

    public function getOrders($pair,$self = 0){
        $self = intval( (bool)$self );//return only 0 or 1
        return $this->jsonQuery($this->api_url."r.html?key={$this->api_key}&a=orderlist&self={$self}&pair={$pair}");
    }

    public function getHistory($pair,$fromTime = false,$toTime = false,$self = false){

        if($fromTime === false){
            $fromTime = 0;
        }

        if($toTime === false){
            $toTime = time();
        }

        $fromDate = date('Y-d-m',(int)$fromTime);
        $toDate = date('Y-d-m',(int)$toTime);

        $url = ($self) ? "r.html?key={$this->api_key}&" : "s.html?";
        return $this->jsonQuery($this->api_url.$url."a=tradehistory&d1={$fromDate}&d2={$toDate}&pair={$pair}");
    }

    public function makeOrder($type,$pair,$quantity,$price){
        if(strtolower($type) == 'sell'){
            $type = 's';
        }
        if(strtolower($type) == 'buy'){
            $type = 'b';
        }
        return $this->jsonQuery($this->api_url."r.html?key={$this->api_key}&a=makeorder&pair={$pair}&q={$quantity}&t={$type}&r={$price}");
    }

    public function cancelOrder($order) {
        return $this->jsonQuery($this->api_url."r.html?key={$this->api_key}&a=cancelorder&id={$order}");
    }

    public function getBalance() {
        return $this->jsonQuery($this->api_url."r.html?key={$this->api_key}&a=getbalance");
    }

    // If not exists - will generate new
    public function getDepositAddress($symbol) {
        $coin = strtolower($symbol);
        return $this->jsonQuery($this->api_url."r.html?key={$this->api_key}&a=getaddress&coin={$coin}");
    }

    public function checkDeposit($symbol, $txid) {
        $coin = strtolower($symbol);
        return $this->jsonQuery($this->api_url."r.html?key={$this->api_key}&a=checkdeposit&coin={$coin}&tid={$txid}");
    }

    public function withdraw($symbol, $amount, $address) {
        $coin = strtolower($symbol);
        return $this->jsonQuery($this->api_url."r.html?key={$this->api_key}&a=withdraw&coin={$coin}&amount={$amount}&address={$address}");
    }

    public function getDepositHistory($symbol, $limit=100) {
        $coin = strtolower($symbol);
        return $this->jsonQuery($this->api_url."r.html?key={$this->api_key}&a=deposithistory&coin={$coin}&limit={$limit}");
    }

    public function getWithdrawalHistory($symbol, $limit=100) {
        $coin = strtolower($symbol);
        return $this->jsonQuery($this->api_url."r.html?key={$this->api_key}&a=withdrawalhistory&coin={$coin}&limit={$limit}");
    }

}

