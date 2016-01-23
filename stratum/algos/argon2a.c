#include <stdio.h>
#include <stdlib.h>
#include <stdint.h>
#include <string.h>

#include "sysendian.h"

#include "ar2/argon2.h"
#include "ar2/cores.h"
#include "ar2/scrypt-jane.h"

#define _ALIGN(x) __attribute__ ((aligned(x)))

#define T_COSTS 2
#define M_COSTS 16
#define MASK 8
#define ZERO 0

static void argon_call(void *out, void *in, void *salt, int type)
{
	argon2_context context = { 0 };

	context.out = (uint8_t *)out;
	context.pwd = (uint8_t *)in;
	context.salt = (uint8_t *)salt;

	argon2_core(&context, type);
}

static void bin2hex(char *s, const unsigned char *p, size_t len)
{
        for (size_t i = 0; i < len; i++)
                sprintf(s + (i * 2), "%02x", (unsigned int) p[i]);
}

static char *abin2hex(const unsigned char *p, size_t len)
{
        char *s = (char*) malloc((len * 2) + 1);
        if (!s)
                return NULL;
        bin2hex(s, p, len);
        return s;
}

static void applog_data(void *pdata)
{
        char* hex = abin2hex((unsigned char*)pdata, 80);
        fprintf(stderr, "%s\n", hex);
        free(hex);
}

void argon2_hash(const char* input, char* output, uint32_t len)
{
	// these uint512 in the c++ source of the client are backed by an array of uint32
	uint32_t _ALIGN(32) hashA[8], hashB[8], hashC[8];
	uint32_t _ALIGN(32) endian[20], *in;

	in = (uint32_t*) input;
	for (int i=0; i<len/4; i++)
		endian[i] = in[i];
	//	be32enc(&endian[i], in[i]);
	//applog_data((void*) endian);

	my_scrypt((unsigned char *)endian, len,
		(unsigned char *)endian, len,
		(unsigned char *)hashA);

	argon_call(hashB, hashA, hashA, (hashA[0] & MASK) == ZERO);

	my_scrypt((const unsigned char *)hashB, 32,
		(const unsigned char *)hashB, 32,
		(unsigned char *)hashC);

	memcpy(output, hashC, 32);
}

