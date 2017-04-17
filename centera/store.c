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
char **inputData(const char *, const int, const char *[], const char *[], const char *[]);
#define BUFSIZE (128 + 1) * sizeof(char)
int main(int argc, char *argv[])
{
    const char *cookbookName = "Store Content";
    const int numParameters = 3;
    const char *prompts[] = { "Enter the IP address or DNS name of the cluster(s)",
                              "Enter the name of the file containing the content",
                              "Enter the threshold value for embedded blobs size in bytes" };
    const char *choices[] = { "" , "" , "" };

    const char *defaults[] = { "centera1.cascommunity.org", "" , "50000" };

    char **values = inputData(cookbookName, numParameters, prompts, choices, defaults);
    const char *poolAddress = values[0];
    const char *inputFileName = values[1];
    FPPoolRef poolRef;
    int   retCode = 0;
    short blobWriteSuccessful = 0;
    FPInt threshold = atoi(values[2]);
    FPPool_SetGlobalOption(FP_OPTION_OPENSTRATEGY, FP_LAZY_OPEN);
    FPPool_SetGlobalOption(FP_OPTION_EMBEDDED_DATA_THRESHOLD, threshold);
    poolRef = FPPool_Open(poolAddress);
    retCode = checkAndPrintError("Pool Open Error: ");
    if (!retCode){
        FPClipRef clipRef = 0;

        if (threshold > 0)
            fprintf(stdout, "Content smaller than %d will be stored embedded in the Clip\n", (int) threshold);
        else
            fprintf(stdout, "Content is never stored embedded in the clip\n");

        clipRef = FPClip_Create(poolRef, "StoreContentSampleObject");
        retCode = checkAndPrintError("C-Clip Creation Error: ");
        if (!retCode)
        {
            FPClip_SetRetentionPeriod(clipRef, FP_NO_RETENTION_PERIOD);
            retCode = checkAndPrintError("Set RetentionPeriod Error: ");

            FPClip_SetDescriptionAttribute(clipRef, "OriginalFilename", inputFileName);
            retCode = checkAndPrintError("Set OriginalFilename DescriptionAttribute Error: ");
fprintf( stdout, "filename be written is %s\n", inputFileName );
            if (!retCode)
            {
                FPTagRef fileTag;
                /* Get the top tag */
                FPTagRef topTag = FPClip_GetTopTag(clipRef);
                retCode = checkAndPrintError("Get Top Tag Error: ");

                if (!retCode)
                {
                    /* Create the tag to store the file and it's attributes */
                    fileTag = FPTag_Create(topTag, "StoreContentObject");
                    retCode = checkAndPrintError("Create File Attribute Tag Error: ");

                    if (!retCode)
                    {
                        /* Set the filename as one of the tag's String attributes */
                        FPTag_SetStringAttribute(fileTag, "filename", inputFileName);
                        retCode = checkAndPrintError("Set filename Attribute Error: ");

                        if (!retCode)
                        {
                            /* Write the blob Data */
                            if (!retCode)
                            {
                                FPStreamRef fpStreamRef = FPStream_CreateFileForInput(inputFileName, "rb", 16 * 1024);
                                retCode = checkAndPrintError("FP Stream creation Error: ");

                                if (!retCode)
                                {
                                    /*
                                     * Write the blob to the tag
                                     */
                                    FPTag_BlobWrite(fileTag, fpStreamRef, FP_OPTION_CLIENT_CALCID);
                                    retCode = checkAndPrintError("Blob Write Error: ");
                                    blobWriteSuccessful = (retCode == 0);

                                    /*
                                     * Close the stream
                                     */
                                    FPStream_Close(fpStreamRef);
                                    retCode = checkAndPrintError("FP Stream Close Error: ");
                                }

                            }
                        }

                        /*
                         * Close the tag
                         */
                        FPTag_Close(fileTag);
                        retCode = checkAndPrintError("File Tag Close Error: ");
                    }
                    FPTag_Close(topTag);
                    retCode = checkAndPrintError("Top Tag Close Error: ");

                    if (blobWriteSuccessful)
                    {
                        FPClipID clipID;
                        /*
                         * Write the C-Clip to Centera
                         */
                        FPClip_Write(clipRef, clipID);
fprintf( stderr, "clipID = %s\n", clipID );
                        retCode = checkAndPrintError("C-Clip Write Error: ");

                        if (!retCode)
                        {
                            FILE* outFile = NULL;
                            FPLong clipSize = 0;
                            const char *contentAddress = clipID;
                            int len = (int) strlen(contentAddress);

                            /* Write the Clip ID to the output file, "inputFileName.clipID" */
                            char outFileName[sizeof(inputFileName)+BUFSIZE];
                            fprintf(stdout, "The C-Clip ID of the content is %s", clipID);
                            sprintf(outFileName, "%s.clipID", inputFileName);

                            outFile = fopen(outFileName, "wb");
                            if (outFile != NULL)
                            {
                                fwrite((char*)contentAddress, sizeof(char), len, outFile);
                                fclose(outFile);
                            }
                            else
                                fprintf(stderr, "Failed to open the output file: %s\n", outFileName);

                            clipSize = FPClip_GetTotalSize (clipRef);
                            retCode = checkAndPrintError("Get Total Size Error: ");

                            if (threshold > 0 && clipSize < threshold)
                                fprintf(stdout, " - file stored embedded in the Clip as it's size (%d) is less than the threshold.", (unsigned) clipSize);

                            fprintf(stdout, "\n");
                        }
                    }
                }
            }

            /*
             * Close the C-Clip
             * @param clipRef The reference to the C-Clip
             */
            FPClip_Close(clipRef);
            retCode = checkAndPrintError("C-Clip Close Error: ");
        }
        /*
         * Close the pool
         * @param poolRef A reference to the pool
         */
        FPPool_Close(poolRef);
        retCode = checkAndPrintError("Pool Close Error: ");
    }

    free(values);
    return retCode;
}



char **inputData(const char *header,
                 const int  numParameters,
                 const char *prompts[],
                 const char *validOptions[],
                 const char *defaults[])
{
    int i;
    char buffer[BUFSIZE];
    char **values = (char **) malloc(numParameters * sizeof(char *));

    fprintf(stderr, "Enter values or leave blank to use defaults:\n\n");

    i = 0;
    while (i < numParameters)
    {
        FPBool valid = false;

        if (*prompts[i] !=  '\0')
            fprintf(stderr, "%s: ", prompts[i]);

        if (*validOptions[i] != '\0')
            fprintf(stderr, " Valid options [%s] ", validOptions[i]);

        if (*defaults[i] != '\0')
            fprintf(stderr, " <%s> ", defaults[i]);

        fgets(buffer, sizeof(buffer), stdin);
        buffer[strlen(buffer) - 1] = '\0';  /* Remove the terminating \n */

        if (buffer[0] == '\0')
        {
            if (*defaults[i] != '\0') /* Accept the default */
            {
                values[i] = (char *) malloc((strlen(defaults[i])+1) * sizeof(char));
                strcpy(values[i], defaults[i]);
                valid = true;
            }
            else
            {
                fprintf(stdout, "There is no default value - please enter data\n");
            }
        }
        else
        {
            /* Test that data is valid */
            if (*validOptions[i] == '\0') /* No choices to validate so accept what user entered */
            {
                values[i] = (char *) malloc((strlen(buffer)+1) * sizeof(char));
                strcpy(values[i], buffer);
                valid = true;
            }
            else
            {
                const char *substr = (const char *) strstr((char *) validOptions[i], buffer);

                if (substr) /* Input is within the validOptions string - check the if it is the whole value */
                {
                    const char *optionEnd =  strchr(substr, '|');

                    if (optionEnd)
                    {
                        int length = (int) (optionEnd - substr);

                        if (length == (int) strlen(buffer))
                            valid = true;
                    }
                    else
                        valid = true;
                }


                if (!valid)
                    fprintf(stderr, "%s is not in valid choices: [%s]\n", buffer, validOptions[i]);
                else
                {
                    values[i] = (char *) malloc((strlen(buffer)+1) * sizeof(char));
                    strcpy(values[i], buffer);
                }
            }
        }
        if (valid)
            ++i;
    }

    return values;
}

int checkAndPrintError(const char *errorMessage)
{
    /* Get the error code of the last SDK API function call */
    FPInt errorCode = FPPool_GetLastError();
    if (errorCode != ENOERR)
    {
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
