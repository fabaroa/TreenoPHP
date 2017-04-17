#include <stdio.h>
#include <string.h>
#include <stdlib.h>
#include <malloc.h>
#include <FPAPI.h>
#define BUFSIZE (128 + 1) * sizeof(char)
#define MAX_NAME_SIZE 128
int checkAndPrintError(const char *);
//argv1 = ipaddress
//argv2 = clip address
int main(int argc, char *argv[]){
    FPClipID clipID;
    FPPoolRef poolRef;
    int retCode = 0;
    const char *cookbookName = "Retrieve Content";
    const char *poolAddress = argv[1];
	const char *ca = argv[2];
    strcpy(clipID, ca);
    /* New in 2.3 - use LazyOpen option for opening pools as it is more efficient */
    FPPool_SetGlobalOption(FP_OPTION_OPENSTRATEGY, FP_LAZY_OPEN);
    /** Open up a Pool */
    poolRef = FPPool_Open(poolAddress);
    retCode = checkAndPrintError("ERROR:Pool Open Error: ");
    if (!retCode){
        /* Read the content of blobs of the C-Clip to a file */
        FPClipRef clipRef = FPClip_Open(poolRef, clipID, FP_OPEN_FLAT);
        retCode = checkAndPrintError("ERROR:C-Clip Open Error: ");
        if (!retCode){
            /* Read the content of the blob to the output file */
            FPTagRef myObjectTag = FPClip_FetchNext(clipRef);
            retCode = checkAndPrintError("ERROR:Get The Top Tag Error: ");
            if (!retCode){
                FPInt nameSize = MAX_NAME_SIZE;
                char  name[MAX_NAME_SIZE];
                /* Check if the tag is myObject tag by comparing the name */
                FPTag_GetTagName(myObjectTag, name, &nameSize);
//fprintf( stderr, "filename = %s\n", name );
                retCode = checkAndPrintError("ERROR:Get Tag Name Error: ");
                if (!retCode){
                    if (strcmp(name, "StoreContentObject") == 0){
                        int retCode = 0;
                        char outfile[MAX_NAME_SIZE+1+1+1];
                        nameSize = MAX_NAME_SIZE;
                        /* Retrieve the "filename" attribute */
                        FPTag_GetStringAttribute(myObjectTag, "filename", name, &nameSize);
                        retCode = checkAndPrintError("ERROR:Get filename Attribute Error: ");
                        if (!retCode){
                            FPStreamRef fpStreamRef;
                            sprintf(outfile,"%s", name);
                            /** Create a generic stream to write to a file*/
//fprintf( stderr, "outfile = %s\n", outfile );
                            fpStreamRef = FPStream_CreateFileForOutput(outfile, "wb");
                            retCode = checkAndPrintError("ERROR:FP Stream Creation Error: ");
                            if (!retCode){
                                /** Read the content of the blob out to the stream*/
                                FPTag_BlobRead(myObjectTag, fpStreamRef, FP_OPTION_DEFAULT_OPTIONS);
                                retCode = checkAndPrintError("ERROR:Blob Read Error: ");
                                /** Close the stream*/
                                FPStream_Close(fpStreamRef);
                                retCode |= checkAndPrintError("ERROR:FP Stream Close Error: ");
                            }
                        }
                        else
                            fprintf(stderr, "ERROR:Cannot create the output file");
                        if (!retCode)
                            fprintf(stdout, "Success C-Clip has retrieved the file:%s\n", outfile);
                    }
                    else
                        fprintf(stderr, "ERROR:Application Error: Not A C-Clip Created By StoreContent Example\n");
                }
                FPTag_Close(myObjectTag);
                retCode |= checkAndPrintError("ERROR:Tag Close Error: ");
            }
            /** Close the C-Clip*/
            FPClip_Close(clipRef);
            retCode |= checkAndPrintError("ERROR:C-Clip Close Error: ");
        }
        /** Close the pool*/
        FPPool_Close(poolRef);
        retCode |= checkAndPrintError("ERROR:Pool Close Error: ");
    }
    return retCode;
}
int checkAndPrintError(const char *errorMessage){
    /* Get the error code of the last SDK API function call */
    FPInt errorCode = FPPool_GetLastError();
    if (errorCode != ENOERR){
        FPErrorInfo errInfo;
        fprintf(stderr, errorMessage);
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
