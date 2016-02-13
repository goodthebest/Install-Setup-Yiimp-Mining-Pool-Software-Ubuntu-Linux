
#include "stratum.h"

void coind_getauxblock(YAAMP_COIND *coind)
{
	if(!coind->isaux) return;

	json_value *json = rpc_call(&coind->rpc, "getauxblock", "[]");
	if(!json)
	{
		coind_error(coind, "coind_getauxblock");
		return;
	}

	json_value *json_result = json_get_object(json, "result");
	if(!json_result)
	{
		coind_error(coind, "coind_getauxblock");
		return;
	}

//	coind->aux.height = coind->height+1;
	coind->aux.chainid = json_get_int(json_result, "chainid");

	const char *p = json_get_string(json_result, "target");
	if(p) strcpy(coind->aux.target, p);

	p = json_get_string(json_result, "hash");
	if(p) strcpy(coind->aux.hash, p);

//	if(strcmp(coind->symbol, "UNO") == 0)
//	{
//		string_be1(coind->aux.target);
//		string_be1(coind->aux.hash);
//	}

	json_value_free(json);
}

YAAMP_JOB_TEMPLATE *coind_create_template_memorypool(YAAMP_COIND *coind)
{
	json_value *json = rpc_call(&coind->rpc, "getmemorypool");
	if(!json || json->type == json_null)
	{
		coind_error(coind, "getmemorypool");
		return NULL;
	}

	json_value *json_result = json_get_object(json, "result");
	if(!json_result || json_result->type == json_null)
	{
		coind_error(coind, "getmemorypool");
		json_value_free(json);

		return NULL;
	}

	YAAMP_JOB_TEMPLATE *templ = new YAAMP_JOB_TEMPLATE;
	memset(templ, 0, sizeof(YAAMP_JOB_TEMPLATE));

	templ->created = time(NULL);
	templ->value = json_get_int(json_result, "coinbasevalue");
//	templ->height = json_get_int(json_result, "height");
	sprintf(templ->version, "%08x", (unsigned int)json_get_int(json_result, "version"));
	sprintf(templ->ntime, "%08x", (unsigned int)json_get_int(json_result, "time"));
	strcpy(templ->nbits, json_get_string(json_result, "bits"));
	strcpy(templ->prevhash_hex, json_get_string(json_result, "previousblockhash"));

	json_value_free(json);

	json = rpc_call(&coind->rpc, "getinfo", "[]");
	if(!json || json->type == json_null)
	{
		coind_error(coind, "coind_getinfo");
		return NULL;
	}

	json_result = json_get_object(json, "result");
	if(!json_result || json_result->type == json_null)
	{
		coind_error(coind, "coind_getinfo");
		json_value_free(json);

		return NULL;
	}

	templ->height = json_get_int(json_result, "blocks")+1;
	json_value_free(json);

	coind_getauxblock(coind);

	coind->usememorypool = true;
	return templ;
}

////////////////////////////////////////////////////////////////////////////////////////////////////////////

static int coind_parse_decred_header(YAAMP_JOB_TEMPLATE *templ, const char *header_hex)
{
	struct __attribute__((__packed__)) {
		uint32_t version;
		char prevblock[32];
		char merkleroot[32];
		char stakeroot[32];
		uint16_t votebits;
		char finalstate[6];
		uint16_t voters;
		uint8_t freshstake;
		uint8_t revoc;
		uint32_t poolsize;
		uint32_t nbits;
		uint64_t sbits;
		uint32_t height;
		uint32_t size;
		uint32_t ntime;
		uint32_t nonce;
		unsigned char extra[36];
		uint32_t hashtag[3];
	} header;

	//debuglog("HEADER: %s\n", header_hex);

	binlify((unsigned char*) &header, header_hex);

	templ->height = header.height;
	sprintf(templ->version, "%08x", bswap32(header.version));
	sprintf(templ->ntime, "%08x", header.ntime);
	sprintf(templ->nbits, "%08x", header.nbits);

	//hexlify(templ->prevhash_hex, (const unsigned char*) header.prevblock, 32);
	templ->prevhash_hex[64] = '\0';
	for(int i=0; i < 32; i++)
		sprintf(templ->prevhash_hex + (i*2), "%02x", (uint8_t) header.prevblock[31-i]);
	ser_string_be2(templ->prevhash_hex, templ->prevhash_be, 8);

	// store all other stuff
	memcpy(templ->header, &header, sizeof(header));

	return 0;
}

static YAAMP_JOB_TEMPLATE *coind_create_template_decred(YAAMP_COIND *coind)
{
	int retry_max = 3;
retry:
	json_value *gw = rpc_call(&coind->rpc, "getwork", "[]");
	if(!gw || json_is_null(gw)) {
		coind_error(coind, "getwork");
		return NULL;
	}
	json_value *gwr = json_get_object(gw, "result");
	if(!gwr) {
		coind_error(coind, "getwork json result");
		return NULL;
	}
	else if (json_is_null(gwr)) {
		json_value *jr = json_get_object(gw, "error");
		if (!jr || json_is_null(jr)) return NULL;
		const char *err = json_get_string(jr, "message");
		if (err && !strcmp(err, "internal error")) {
			sleep(500*YAAMP_MS); // not enough voters (testnet)
			if (--retry_max > 0) goto retry;
			debuglog("%s getwork %s\n", coind->symbol, err);
		}
		return NULL;
	}
	const char *header_hex = json_get_string(gwr, "data");
	if (!header_hex || !strlen(header_hex)) {
		coind_error(coind, "getwork data");
		return NULL;
	}

	YAAMP_JOB_TEMPLATE *templ = new YAAMP_JOB_TEMPLATE;
	memset(templ, 0, sizeof(YAAMP_JOB_TEMPLATE));

	templ->created = time(NULL);

	coind_parse_decred_header(templ, header_hex);
	json_value_free(gw);

	// bypass coinbase and merkle for now... send without nonce/extradata
	const unsigned char *hdr = (unsigned char *) &templ->header[36];
	hexlify(templ->coinb1, hdr, 192 - 80);
	strcpy(templ->coinb2, "");

	vector<string> txhashes;
	txhashes.push_back("");

	templ->txmerkles[0] = 0;
	templ->txcount = txhashes.size();
	templ->txsteps = merkle_steps(txhashes);
	txhashes.clear();

	return templ;
}

////////////////////////////////////////////////////////////////////////////////////////////////////////////

YAAMP_JOB_TEMPLATE *coind_create_template(YAAMP_COIND *coind)
{
	if(coind->usememorypool)
		return coind_create_template_memorypool(coind);

	char params[4*1024] = "[{}]";
	if(!strcmp(coind->symbol, "PPC")) strcpy(params, "[]");

	json_value *json = rpc_call(&coind->rpc, "getblocktemplate", params);
	if(!json || json->type == json_null)
	{
		coind_error(coind, "getblocktemplate");
		return NULL;
	}

	json_value *json_result = json_get_object(json, "result");
	if(!json_result || json_result->type == json_null)
	{
		coind_error(coind, "getblocktemplate result");
		json_value_free(json);
		return NULL;
	}

	json_value *json_tx = json_get_array(json_result, "transactions");
	if(!json_tx)
	{
		coind_error(coind, "getblocktemplate transactions");
		json_value_free(json);
		return NULL;
	}

	json_value *json_coinbaseaux = json_get_object(json_result, "coinbaseaux");
	if(!json_coinbaseaux && coind->isaux)
	{
		coind_error(coind, "getblocktemplate coinbaseaux");
		json_value_free(json);
		return NULL;
	}

	YAAMP_JOB_TEMPLATE *templ = new YAAMP_JOB_TEMPLATE;
	memset(templ, 0, sizeof(YAAMP_JOB_TEMPLATE));

	templ->created = time(NULL);
	templ->value = json_get_int(json_result, "coinbasevalue");
	templ->height = json_get_int(json_result, "height");
	sprintf(templ->version, "%08x", (unsigned int)json_get_int(json_result, "version"));
	sprintf(templ->ntime, "%08x", (unsigned int)json_get_int(json_result, "curtime"));

	const char *bits = json_get_string(json_result, "bits");
	strcpy(templ->nbits, bits ? bits : "");
	const char *prev = json_get_string(json_result, "previousblockhash");
	strcpy(templ->prevhash_hex, prev ? prev : "");
	const char *flags = json_get_string(json_coinbaseaux, "flags");
	strcpy(templ->flags, flags ? flags : "");

	if (!templ->height || !bits || !prev) {
		stratumlog("%s warning, gbt incorrect : version=%s height=%d value=%d bits=%s time=%s prev=%s\n",
			coind->symbol, templ->version, templ->height, templ->value, templ->nbits, templ->ntime, templ->prevhash_hex);
	}

	// temporary hack, until wallet is fixed...
	if (!strcmp(coind->symbol, "MBL")) { // MBL: chainid in version
		unsigned int nVersion = (unsigned int)json_get_int(json_result, "version");
		if (nVersion & 0xFFFF0000UL == 0) {
			nVersion |= (0x16UL << 16);
			debuglog("%s version %s >> %08x\n", coind->symbol, templ->version, nVersion);
		}
		sprintf(templ->version, "%08x", nVersion);
	}

//	debuglog("%s ntime %s\n", coind->symbol, templ->ntime);
//	uint64_t target = decode_compact(json_get_string(json_result, "bits"));
//	coind->difficulty = target_to_diff(target);

//	string_lower(templ->ntime);
//	string_lower(templ->nbits);

//	char target[1024];
//	strcpy(target, json_get_string(json_result, "target"));
//	uint64_t coin_target = decode_compact(templ->nbits);
//	debuglog("nbits %s\n", templ->nbits);
//	debuglog("target %s\n", target);
//	debuglog("0000%016llx\n", coin_target);

	if(coind->isaux)
	{
		json_value_free(json);
		coind_getauxblock(coind);
		return templ;
	}

	//////////////////////////////////////////////////////////////////////////////////////////

	vector<string> txhashes;
	txhashes.push_back("");

	for(int i = 0; i < json_tx->u.array.length; i++)
	{
		const char *p = json_get_string(json_tx->u.array.values[i], "hash");

		char hash_be[1024];
		memset(hash_be, 0, 1024);
		string_be(p, hash_be);

		txhashes.push_back(hash_be);

		const char *d = json_get_string(json_tx->u.array.values[i], "data");
		templ->txdata.push_back(d);
	}

	templ->txmerkles[0] = 0;
	templ->txcount = txhashes.size();
	templ->txsteps = merkle_steps(txhashes);
	txhashes.clear();

	vector<string>::const_iterator i;
	for(i = templ->txsteps.begin(); i != templ->txsteps.end(); ++i)
		sprintf(templ->txmerkles + strlen(templ->txmerkles), "\"%s\",", (*i).c_str());

	if(templ->txmerkles[0])
		templ->txmerkles[strlen(templ->txmerkles)-1] = 0;

//	debuglog("merkle transactions %d [%s]\n", templ->txcount, templ->txmerkles);
	ser_string_be2(templ->prevhash_hex, templ->prevhash_be, 8);

	if(!coind->pos)
		coind_aux_build_auxs(templ);

	coinbase_create(coind, templ, json_result);
	json_value_free(json);

	return templ;
}

////////////////////////////////////////////////////////////////////////////////////////////////////////////

void coind_create_job(YAAMP_COIND *coind, bool force)
{
//	debuglog("create job %s\n", coind->symbol);

	bool b = rpc_connected(&coind->rpc);
	if(!b) return;

	CommonLock(&coind->mutex);

	YAAMP_JOB_TEMPLATE *templ;

	// DCR gbt block header is not compatible with getwork submit, so...
	if (coind->usegetwork && !strcmp(coind->symbol, "DCR"))
		templ = coind_create_template_decred(coind);
	else
		templ = coind_create_template(coind);

	if(!templ)
	{
		CommonUnlock(&coind->mutex);
//		debuglog("%s: create job template failed!\n", coind->symbol);
		return;
	}

	YAAMP_JOB *job_last = coind->job;

	if(	!force && job_last && job_last->templ && job_last->templ->created + 45 > time(NULL) &&
		templ->height == job_last->templ->height &&
		templ->txcount == job_last->templ->txcount &&
		strcmp(templ->coinb2, job_last->templ->coinb2) == 0)
	{
//		debuglog("coind_create_job %s %d same template %x \n", coind->name, coind->height, coind->job->id);
		if (templ->txcount) {
			templ->txsteps.clear();
			templ->txdata.clear();
		}
		delete templ;

		CommonUnlock(&coind->mutex);
		return;
	}

	////////////////////////////////////////////////////////////////////////////////////////

	int height = coind->height;
	coind->height = templ->height-1;

	if(height > coind->height)
	{
		stratumlog("%s went from %d to %d\n", coind->name, height, coind->height);
	//	coind->auto_ready = false;
	}

	if(height < coind->height && !coind->newblock)
	{
		if(coind->auto_ready && coind->notreportingcounter++ > 5)
			stratumlog("%s %d not reporting\n", coind->name, coind->height);
	}

	uint64_t coin_target = decode_compact(templ->nbits);
	if (templ->nbits && !coin_target) coin_target = 0xFFFF000000000000ULL; // under decode_compact min diff
	coind->difficulty = target_to_diff(coin_target);

//	stratumlog("%s %d diff %g %llx %s\n", coind->name, height, coind->difficulty, coin_target, templ->nbits);

	coind->newblock = false;

	////////////////////////////////////////////////////////////////////////////////////////

	object_delete(coind->job);

	coind->job = new YAAMP_JOB;
	memset(coind->job, 0, sizeof(YAAMP_JOB));

	sprintf(coind->job->name, "%s", coind->symbol);

	coind->job->id = job_get_jobid();
	coind->job->templ = templ;

	coind->job->profit = coind_profitability(coind);
	coind->job->maxspeed = coind_nethash(coind) *
		(g_current_algo->profit? min(1.0, coind_profitability(coind)/g_current_algo->profit): 1);

	coind->job->coind = coind;
	coind->job->remote = NULL;

	g_list_job.AddTail(coind->job);
	CommonUnlock(&coind->mutex);

//	debuglog("coind_create_job %s %d new job %x\n", coind->name, coind->height, coind->job->id);
}















