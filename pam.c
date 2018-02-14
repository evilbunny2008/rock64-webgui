// https://unix.stackexchange.com/questions/105098/securest-way-to-authenticate-users-against-etc-shadow-using-php
// gcc -g -lpam -o chkpasswd pam.c

#include <security/pam_appl.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>

struct pam_response *reply;

int null_conv(int num_msg, const struct pam_message **msg, struct pam_response **resp, void *appdata_ptr)
{
        *resp = reply;
        return PAM_SUCCESS;
}

static struct pam_conv conv = { null_conv, NULL };

int authenticate(char *service, char *user, char *pass)
{
        pam_handle_t *pamh = NULL;
        int retval = pam_start(service, user, &conv, &pamh);

        if (retval == PAM_SUCCESS)
	{
                reply = (struct pam_response *)malloc(sizeof(struct pam_response));
                reply[0].resp = pass;
                reply[0].resp_retcode = 0;

                retval = pam_authenticate(pamh, 0);

                pam_end(pamh, PAM_SUCCESS);

                return ( retval == PAM_SUCCESS ? 0:1 );
        }

        return ( retval == PAM_SUCCESS ? 0:1 );
}

int main(int argc, char *argv[])
{
        int retval;
        char *user, *pass;

        if(argc == 3)
	{
                user = argv[1];
                pass = strdup(argv[2]);

        } else {
                exit(1);
        }

        return authenticate("system-auth", user, pass);
}
