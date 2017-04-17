need to install compat-gcc-32-c++ before compiling this
read the release notes to see how to install this software
the following needs to be in the environment for development
export LD_LIBRARY_PATH=$LD_LIBRARY_PATH:/usr/local/Centera_SDK/lib/32/




for deployment you need to export that path in the startup script for httpd
Need the Centera_SDK in /usr/local/

TODO
See if we can statically link to the libraries so we do not need to export the
LIBRARY_PATH

