
// http://www.righto.com/2014/02/bitcoin-mining-hard-way-algorithms.html

// https://en.bitcoin.it/wiki/Merged_mining_specification#Merged_mining_coinbase

#include "stratum.h"

#define TX_VALUE(v, s)	((unsigned int)(v>>s)&0xff)

static void encode_tx_value(char *encoded, json_int_t value)
{
	sprintf(encoded, "%02x%02x%02x%02x%02x%02x%02x%02x",
		TX_VALUE(value, 0), TX_VALUE(value, 8), TX_VALUE(value, 16), TX_VALUE(value, 24),
		TX_VALUE(value, 32), TX_VALUE(value, 40), TX_VALUE(value, 48), TX_VALUE(value, 56));
}

static void job_pack_tx(YAAMP_COIND *coind, char *data, json_int_t amount, char *key)
{
	int ol = strlen(data);
	char evalue[64];
	encode_tx_value(evalue, amount);

	sprintf(data+strlen(data), "%s", evalue);

	if(coind->pos && !key)
		sprintf(data+strlen(data), "2321%sac", coind->pubkey);

	else
		sprintf(data+strlen(data), "1976a914%s88ac", key? key: coind->script_pubkey);

//	debuglog("pack tx %s\n", data+ol);
//	debuglog("pack tx %lld\n", amount);
}

void coinbase_aux(YAAMP_JOB_TEMPLATE *templ, char *aux_script)
{
	vector<string> hashlist = coind_aux_hashlist(templ->auxs, templ->auxs_size);
	while(hashlist.size() > 1)
	{
		vector<string> l;
		for(int i = 0; i < hashlist.size()/2; i++)
		{
			string s = hashlist[i*2] + hashlist[i*2+1];

			char bin[YAAMP_HASHLEN_BIN*2];
			char out[YAAMP_HASHLEN_STR];

			binlify((unsigned char *)bin, s.c_str());
			sha256_double_hash_hex(bin, out, YAAMP_HASHLEN_BIN*2);

			l.push_back(out);
		}

		hashlist = l;
	}

	char merkle_hash[4*1024];
	memset(merkle_hash, 0, 4*1024);
	string_be(hashlist[0].c_str(), merkle_hash);

	sprintf(aux_script+strlen(aux_script), "fabe6d6d%s%02x00000000000000", merkle_hash, templ->auxs_size);
//	debuglog("aux_script is %s\n", aux_script);
}

void coinbase_create(YAAMP_COIND *coind, YAAMP_JOB_TEMPLATE *templ, json_value *json_result)
{
	char eheight[64];
	ser_number(templ->height, eheight);

	char etime[64];
	ser_number(time(NULL), etime);

	char entime[64];
	memset(entime, 0, 64);

	if(coind->pos)
		ser_string_be(templ->ntime, entime, 1);

	char eversion1[64] = "01000000";

	if(coind->txmessage)
		strcpy(eversion1, "02000000");

	char script1[4*1024];
	sprintf(script1, "%s%s%s08", eheight, templ->flags, etime);

	char script2[4*1024] = "7969696d7000"; // "yiimp\0" in hex ascii
	if(!coind->pos && !coind->isaux && templ->auxs_size)
		coinbase_aux(templ, script2);

	int script_len = strlen(script1)/2 + strlen(script2)/2 + 8;

	sprintf(templ->coinb1,
		"%s%s010000000000000000000000000000000000000000000000000000000000000000ffffffff%02x%s",		// 8+8+74+2 -> height
		eversion1, entime, script_len, script1);

	sprintf(templ->coinb2, "%s00000000", script2);
	json_int_t available = templ->value;

	// sample coins using mandatory dev/foundation fees
	if(strcmp(coind->symbol, "EGC") == 0) {
		if (coind->charity_percent <= 0)
			coind->charity_percent = 2;
		if (strlen(coind->charity_address) == 0)
			sprintf(coind->charity_address, "EdFwYw4Mo2Zq6CFM2yNJgXvE2DTJxgdBRX");
	}
	else if(strcmp(coind->symbol, "LTCR") == 0) {
		if (coind->charity_percent <= 0)
			coind->charity_percent = 10;
		if (strlen(coind->charity_address) == 0)
			sprintf(coind->charity_address, "BCDrF1hWdKTmrjXXVFTezPjKBmGigmaXg5");
	}

	// 2 txs are required on these coins, one for foundation (dev fees)
	if(coind->charity_percent)
	{
		char charity_payee[1024] = { 0 };
		const char *payee = json_get_string(json_result, "payee");
		if (payee) snprintf(charity_payee, 1023, "%s", payee);
		else sprintf(charity_payee, "%s", coind->charity_address);
		if (strlen(charity_payee) == 0)
			stratumlog("ERROR %s has no charity_address set!\n", coind->name);

		char script_payee[1024];
		base58_decode(charity_payee, script_payee);

		json_int_t charity_amount = (available * coind->charity_percent) / 100;
		available -= charity_amount;
		coind->charity_amount = charity_amount;

		strcat(templ->coinb2, "02");
		job_pack_tx(coind, templ->coinb2, available, NULL);
		job_pack_tx(coind, templ->coinb2, charity_amount, script_payee);
		strcat(templ->coinb2, "00000000"); // locktime

		coind->reward = (double)available/100000000*coind->reward_mul;
		return;
	}

//	if(strcmp(coind->symbol, "DASH") == 0)
	if(coind->hasmasternodes)
	{
		char charity_payee[1024] = { 0 };
		const char *payee = json_get_string(json_result, "payee");
		if (payee) snprintf(charity_payee, 1023, "%s", payee);

		json_int_t charity_amount = json_get_int(json_result, "payee_amount");
		bool charity_payments = json_get_bool(json_result, "masternode_payments");
		bool charity_enforce = json_get_bool(json_result, "enforce_masternode_payments");

		if(charity_payments && charity_enforce)
		{
			available -= charity_amount;

			char script_payee[1024];
			base58_decode(charity_payee, script_payee);

			strcat(templ->coinb2, "02");
			job_pack_tx(coind, templ->coinb2, charity_amount, script_payee);
		}
		else
			strcat(templ->coinb2, "01");
	}

	else
		strcat(templ->coinb2, "01");

	job_pack_tx(coind, templ->coinb2, available, NULL);
	strcat(templ->coinb2, "00000000"); // locktime

	//if(coind->txmessage)
	//	strcat(templ->coinb2, "00");

	coind->reward = (double)available/100000000*coind->reward_mul;
//	debuglog("coinbase %f\n", coind->reward);

//	debuglog("coinbase %s: version %s, nbits %s, time %s\n", coind->symbol, templ->version, templ->nbits, templ->ntime);
//	debuglog("coinb1 %s\n", templ->coinb1);
//	debuglog("coinb2 %s\n", templ->coinb2);
}



