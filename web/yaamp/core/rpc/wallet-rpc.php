<?php
/**
 * Common yiimp Wallet RPC object
 */
class WalletRPC {

	public $type = 'Bitcoin';
	private $rpc;

	// cache
	private $account;
	private $accounts;
	private $info;
	private $height = 0;

	// Information and debugging
	// public $status;
	// public $error;
	// public $raw_response;
	// public $response;

	function __construct($userOrCoin, $pw='', $host='localhost', $port=8332, $url=null)
	{
		if (is_object($userOrCoin)) {

			$coin = $userOrCoin;
			switch ($coin->rpcencoding) {
			case 'GETH':
				$this->type = 'Ethereum';
				$this->account = empty($coin->account) ? $coin->master_wallet : $coin->account;
				$this->rpc = new Ethereum($coin->rpchost, $coin->rpcport);
				break;
			default:
				$this->type = 'Bitcoin';
				$this->rpc = new Bitcoin($coin->rpcuser, $coin->rpcpasswd, $coin->rpchost, $coin->rpcport, $url);
			}

		} else {
			// backward compat
			$user = $userOrCoin;
			$this->rpc = new Bitcoin($user, $pw, $host, $port, $url);
		}
	}

	function __call($method, $params)
	{
		if ($this->type == 'Ethereum') {
			if (!isset($this->accounts))
				$this->accounts = $this->rpc->eth_accounts();
			if (!is_array($this->accounts)) {
				// if wallet is stopped
				return false;
			}
			// convert common methods used by yiimp
			switch ($method) {
			case 'getaccountaddress':
				if (!empty($params[0]))
					return $params[0];
				return $this->account;
			case 'getinfo':
				if (!isset($this->info)) {
					$info = array();
					$info['accounts'] = array();
					$balances = 0;

					foreach ($this->accounts as $addr) {
						// web3.fromWei(eth.getBalance("0x..."), "ether")
						$balance = (double) $this->rpc->eth_getBalance($addr,'latest', true);
						$balance /= 1e18;
						$balances += $balance;
						$info['accounts'][$addr] = $balance;
					}
					$info['balance'] = $balances;
					$this->height = $this->height ? $this->height : $this->rpc->eth_blockNumber();
					$info['blocks'] = $this->height;
					$info['gasprice'] = (double) $this->rpc->eth_gasPrice();
					$info['gasprice'] /= 1e18;
					$info['connections'] = $this->rpc->net_peerCount();
					$info['version'] = $this->rpc->web3_clientVersion();
					$this->info = $info;
				}
				return $this->info;
			case 'getdifficulty':
				$this->height = $this->height ? $this->height : $this->rpc->eth_blockNumber();
				$block = $this->rpc->eth_getBlockByNumber($this->height);
				$difficulty = objSafeVal($block, 'difficulty', 0);
				return $this->rpc->decode_hex($difficulty);
			case 'getmininginfo':
				$info = array();
				$this->height = $this->height ? $this->height : $this->rpc->eth_blockNumber();
				$info['blocks'] = $this->height;
				$block = $this->rpc->eth_getBlockByNumber($info['blocks']);
				$difficulty = objSafeVal($block, 'difficulty', 0);
				$info['difficulty'] = $this->rpc->decode_hex($difficulty);
				$info['generate'] = $this->rpc->eth_mining();
				$info['errors'] = '';
				return $info;
			case 'getblock':
				$hash = arraySafeVal($params,0);
				$block = $this->rpc->eth_getBlockByHash($hash);
				return $block;
			case 'getblockhash':
				$n = arraySafeVal($params,0);
				$block = $this->rpc->eth_getBlockByNumber($n);
				return $block->hash;
			case 'gettransaction':
			case 'getrawtransaction':
				$txid = arraySafeVal($params,0,'');
				$tx = $this->rpc->eth_getTransactionByHash($txid);
				return $tx;
			case 'getwork':
				return false; //$this->rpc->eth_getWork(); auto enable miner!
			// todo...
			case 'getpeerinfo':
				$peers = array();
				return $peers;
			case 'listtransactions':
				$txs = array();
				return $txs;
			case 'listsinceblock':
				$txs = array();
				return $txs;
			default:
				return $this->rpc->ether_request($method,$params);
			}
		}

		return $this->rpc->__call($method,$params);
	}

	function __get($prop)
	{
		//debuglog("wallet get $prop ".json_encode($this->rpc->$prop));
		return $this->rpc->$prop;
	}

	function __set($prop, $value)
	{
		//debuglog("wallet set $prop ".json_encode($value));
		$this->rpc->$prop = $value;
	}

}
