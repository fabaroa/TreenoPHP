#include <stdio.h>
#include <string.h>
#include <stdlib.h>
#include <wchar.h>
#include <sys/stat.h>
#include <sys/types.h>
#include <malloc.h>
#include <FPAPI.h>
#include <FPErrors.h>
int checkAndPrintError(const char *);
#define BUFSIZE (128 + 1) * sizeof(char)
//argv1 = centera ip or name
//argv2 = filepath
int main(int argc, char *argv[]){
	if( argc != 3 ){
		fprintf( stdout, "%s", "ERROR: Bad Number of Arguments\n" );
		return -1;
	}
	const char *centname = argv[1];
	const char *filepath = argv[2];
    const char *cookbookName = "Store Content";
    const int numParameters = 3;
    const char *poolAddress = centname;//values[0];
    const char *inputFileName = filepath;//values[1];
    FPPoolRef poolRef;
    int   retCode = 0;
    short blobWriteSuccessful = 0;
    FPInt threshold = 102400;//atoi(values[2]);
	//lazy open
    FPPool_SetGlobalOption(FP_OPTION_OPENSTRATEGY, FP_LAZY_OPEN);
	//embedded data threshold
    FPPool_SetGlobalOption(FP_OPTION_EMBEDDED_DATA_THRESHOLD, threshold);
	//open pool
    poolRef = FPPool_Open(poolAddress);
	//check if opening pool was success
    retCode = checkAndPrintError("Pool Open Error: ");
    if (!retCode){
        FPClipRef clipRef = 0;
        clipRef = FPClip_Create(poolRef, "Docutron");
        retCode = checkAndPrintError("C-Clip Creation Error: ");
        if (!retCode){
            FPClip_SetRetentionPeriod(clipRef, FP_NO_RETENTION_PERIOD);
            retCode = checkAndPrintError("Set RetentionPeriod Error: ");
            FPClip_SetDescriptionAttribute(clipRef, "OriginalFilename", inputFileName);
            FPClip_SetDescriptionAttribute(clipRef, "DocutronSoftwareVersion", "3.6");
            FPClip_SetDescriptionAttribute(clipRef, "DocutronCenteraIntegration", "1.0");
            retCode = checkAndPrintError("Set OriginalFilename DescriptionAttribute Error: ");
            if (!retCode){
                FPTagRef fileTag;
                /* Get the top tag */
                FPTagRef topTag = FPClip_GetTopTag(clipRef);
                retCode = checkAndPrintError("Get Top Tag Error: ");
                if (!retCode){
                    /* Create the tag to store the file and it's attributes */
                    fileTag = FPTag_Create(topTag, "StoreContentObject");
                    retCode = checkAndPrintError("Create File Attribute Tag Error: ");
                    if (!retCode){
                        /* Set the filename as one of the tag's String attributes */
                        FPTag_SetStringAttribute(fileTag, "filename", inputFileName);
                        retCode = checkAndPrintError("Set filename Attribute Error: ");
                        if (!retCode){
                            /* Write the blob Data */
                            if (!retCode){
                                FPStreamRef fpStreamRef=FPStream_CreateFileForInput(inputFileName,"rb",16*1024);
                                retCode = checkAndPrintError("FP Stream creation Error: ");
                                if (!retCode){
                                    /** Write the blob to the tag*/
									//TODO FP_OPTION_CLIENT_CALCID_STREAMING
                                    FPTag_BlobWrite(fileTag, fpStreamRef, FP_OPTION_CLIENT_CALCID_STREAMING);
                                    retCode = checkAndPrintError("Blob Write Error: ");
                                    blobWriteSuccessful = (retCode == 0);
                                    /** Close the stream*/
                                    FPStream_Close(fpStreamRef);
                                    retCode = checkAndPrintError("FP Stream Close Error: ");
                                }
                            }
                        }
                        /** Close the tag */
                        FPTag_Close(fileTag);
                        retCode = checkAndPrintError("File Tag Close Error: ");
                    }
                    FPTag_Close(topTag);
                    retCode = checkAndPrintError("Top Tag Close Error: ");
                    if (blobWriteSuccessful){
                        FPClipID clipID;
                        /** Write the C-Clip to Centera */
                        FPClip_Write(clipRef, clipID);
                        retCode = checkAndPrintError("C-Clip Write Error: ");
                        if (!retCode){
                            FILE* outFile = NULL;
                            FPLong clipSize = 0;
                            const char *contentAddress = clipID;
                            int len = (int) strlen(contentAddress);
                            /* Write the Clip ID to the output file, "inputFileName.clipID" */
                            char outFileName[sizeof(inputFileName)+BUFSIZE];
                            fprintf(stdout, "%s", clipID);
                            clipSize = FPClip_GetTotalSize (clipRef);
                            retCode = checkAndPrintError("Get Total Size Error: ");
                        }
                    }
                }
            }
            /** Close the C-Clip* @param clipRef The reference to the C-Clip */
            FPClip_Close(clipRef);
            retCode = checkAndPrintError("C-Clip Close Error: ");
        }
        /** Close the pool * @param poolRef A reference to the pool */
        FPPool_Close(poolRef);
        retCode = checkAndPrintError("Pool Close Error: ");
    }
    return retCode;
}
int checkAndPrintError(const char *errorMessage){
    /* Get the error code of the last SDK API function call */
    FPInt errorCode = FPPool_GetLastError();
    if (errorCode != ENOERR){
        FPErrorInfo errInfo;
        fprintf(stderr, "ERROR:",errorMessage);
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
