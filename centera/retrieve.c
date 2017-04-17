/******************************************************************************\
 *
 * Copyright (c) 2001-2004 EMC Corporation
 * All Rights Reserved
 *
 * RetrieveContent.c
 *
 * RetrieveContent Source File Build Version 2.3.206
 *
 * This sourcefile contains the intellectual property of EMC Corporation
 * or is licensed to EMC Corporation from third parties. Use of this sourcefile
 * and the intellectual property contained therein is expressly limited to the
 * terms and conditions of the License Agreement.
 *
\******************************************************************************/

#include <stdio.h>
#include <string.h>
#include <stdlib.h>
#include <malloc.h>


/*
 * The standard header file for the Centera access API.
 * This includes all function prototypes and type
 * definitions needed to use the access API.
 */
#include <FPAPI.h>

#define BUFSIZE (128 + 1) * sizeof(char)
#define MAX_NAME_SIZE 128

char **inputData(const char *, const int, const char *[], const char *[], const char *[]);
int checkAndPrintError(const char *);

int main(int argc, char *argv[])
{
    FPClipID clipID;
    FPPoolRef poolRef;
    int retCode = 0;

    const char *cookbookName = "Retrieve Content";
    const int numParameters = 2;
    const char *prompts[] = { "Enter the IP address or DNS name of the cluster(s)",
                              "Enter the Content Address of the object to be retrieved "};
    const char *choices[] = { "" , "" };
    const char *defaults[] = { "centera1.cascommunity.org", "" };

    /* Verify the input options */
    char **values = inputData(cookbookName, numParameters, prompts, choices, defaults);
    const char *poolAddress = values[0];
    strcpy(clipID, values[1]);

    /* New in 2.3 - use LazyOpen option for opening pools as it is more efficient */
    FPPool_SetGlobalOption(FP_OPTION_OPENSTRATEGY, FP_LAZY_OPEN);

    /*
     * Open up a Pool
     */
    poolRef = FPPool_Open(poolAddress);
    retCode = checkAndPrintError("Pool Open Error: ");
    if (!retCode)
    {
        /* Read the content of blobs of the C-Clip to a file */
        FPClipRef clipRef = FPClip_Open(poolRef, clipID, FP_OPEN_FLAT);
        retCode = checkAndPrintError("C-Clip Open Error: ");
        if (!retCode)
        {
            /* Read the content of the blob to the output file */
            FPTagRef myObjectTag = FPClip_FetchNext(clipRef);
            retCode = checkAndPrintError("Get The Top Tag Error: ");
            if (!retCode)
            {
                FPInt nameSize = MAX_NAME_SIZE;
                char  name[MAX_NAME_SIZE];
                /* Check if the tag is myObject tag by comparing the name */
                FPTag_GetTagName(myObjectTag, name, &nameSize);
                retCode = checkAndPrintError("Get Tag Name Error: ");

                if (!retCode)
                {
                    if (strcmp(name, "StoreContentObject") == 0)
                    {
                        int retCode = 0;
                        char outfile[MAX_NAME_SIZE+sizeof("out")+1+1];
                        nameSize = MAX_NAME_SIZE;

                        /* Retrieve the "filename" attribute */
                        FPTag_GetStringAttribute(myObjectTag, "filename", name, &nameSize);
                        retCode = checkAndPrintError("Get filename Attribute Error: ");
                        if (!retCode)
                        {
                            FPStreamRef fpStreamRef;
                            sprintf(outfile,"%s.out", name);

                            /*
                             * Create a generic stream to write to a file
                             */
                            fpStreamRef = FPStream_CreateFileForOutput(outfile, "wb");
                            retCode = checkAndPrintError("FP Stream Creation Error: ");
                            if (!retCode)
                            {
                                /*
                                 * Read the content of the blob out to the stream
                                 */
                                FPTag_BlobRead(myObjectTag, fpStreamRef, FP_OPTION_DEFAULT_OPTIONS);
                                retCode = checkAndPrintError("Blob Read Error: ");

                                /*
                                 * Close the stream
                                 */
                                FPStream_Close(fpStreamRef);
                                retCode |= checkAndPrintError("FP Stream Close Error: ");
                            }
                        }
                        else
                            fprintf(stderr, "Cannot create the output file");

                        if (!retCode)
                            fprintf(stdout, "The C-Clip has been stored into %s\n", outfile);
                    }
                    else
                        fprintf(stderr, "Application Error: Not A C-Clip Created By StoreContent Example\n");
                }
                FPTag_Close(myObjectTag);
                retCode |= checkAndPrintError("Tag Close Error: ");
            }

            /*
             * Close the C-Clip
             */
            FPClip_Close(clipRef);
            retCode |= checkAndPrintError("C-Clip Close Error: ");
        }
        /*
         * Close the pool
         */
        FPPool_Close(poolRef);
        retCode |= checkAndPrintError("Pool Close Error: ");
    }

    return retCode;
}

char **inputData(const char *header,
                 const int numParameters,
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
