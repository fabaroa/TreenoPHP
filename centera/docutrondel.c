#include <stdio.h>
#include <string.h>
#include <stdlib.h>
#include <malloc.h>
#include <FPAPI.h>
#define BUFSIZE (128 + 1) * sizeof(char)
//argv1 = centera ip or hostname
//argv2 = ca
//argv3 = reason string
int checkAndPrintError(const char *);
int main(int argc, char *argv[]){
    FPClipID clipID;
    FPPoolRef poolRef;
    char reasonString[BUFSIZE];
    int auditedDelete = false;
    int retCode;
    const char *cookbookName = "Delete Content";
    const char *choices[] = { "" , "", "" };
    const char *poolAddress = argv[1];
    strcpy(clipID, argv[2]);
    auditedDelete = true;
    strcpy(reasonString, argv[3]);
    FPPool_SetGlobalOption(FP_OPTION_OPENSTRATEGY, FP_LAZY_OPEN);
    poolRef = FPPool_Open(poolAddress);
    retCode = checkAndPrintError("Pool Open Error: ");
    if (!retCode){
        Boolean exist = FPClip_Exists(poolRef, clipID);
        retCode = checkAndPrintError("Check C-Clip Existence Error: ");
        if (!retCode){
            if (exist){
                fprintf(stdout, "The C-Clip %s Exists \n", clipID);
                fprintf(stdout, "Deleting the C-Clip: %s\n", clipID);
                if (!auditedDelete){
                    FPClip_Delete(poolRef, clipID);
                } else {
                    FPClip_AuditedDelete (poolRef, clipID, reasonString, FP_OPTION_DELETE_PRIVILEGED);
                }
                retCode = checkAndPrintError("C-Clip Delete Error: ");
                if (!retCode) {
                    exist = FPClip_Exists(poolRef, clipID);
                    retCode = checkAndPrintError("Check C-Clip Existence Error: ");
                    if (!retCode) {
                        if (!exist)
                            fprintf(stdout, "The C-Clip: %s has been successfully deleted from the Pool\n", clipID);
                    }
                }
            }
            else
                fprintf(stderr, "The C-Clip does not exist\n");
        }
        FPPool_Close(poolRef);
        retCode = checkAndPrintError("Pool Close Error: ");
    }

    return retCode;
}
int checkAndPrintError(const char *errorMessage)
{
    /* Get the error code of the last SDK API function call */
    FPInt errorCode = FPPool_GetLastError();
    if (errorCode != ENOERR)
    {
        FPErrorInfo errInfo;
        fprintf(stderr, "ERROR:%s", errorMessage);
        /* Get the error message of the last SDK API function call */
        FPPool_GetLastErrorInfo(&errInfo);
        if (!errInfo.message) /* the human readable error message */
            fprintf(stderr, "%s\n", errInfo.errorString);
        else if (!errInfo.errorString) /* the error string corresponds to an error code */
            fprintf(stderr, "%s\n", errInfo.message);
        else
            fprintf(stderr, "%s%s%s\n",errInfo.errorString," - ",errInfo.message);
    }

    return errorCode;
}
