#!/bin/sh
/usr/sbin/groupadd -f client_files
/usr/sbin/groupadd -g client_files client_files
/usr/sbin/useradd -g client_files client_files
(echo client_files;echo client_files) | /usr/bin/smbpasswd -a client_files -s
