#!/bin/bash

# Compatibility generator for NetCurl v1.0
# Remove content after namespace.
# sed -e '1,/namespace/d' module_*.php >network.php

src=`grep source composer.json | sed 's/[\"|,$]//g'`
mergeTo="source/netcurl.php"

for mergeFile in $src
do
    if [ ! $firstFile ] ; then
        firstFile=1
        echo "Initializing merge with ${mergeFile}"
        cat $mergeFile >${mergeTo}
    else
        echo "Merging ${mergeFile} into ${mergeTo}"
        sed -e '1,/namespace/d' $mergeFile >>${mergeTo}
    fi
done
