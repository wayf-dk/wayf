#!/bin/bash

#######################################
#                                     #
# Fetch metadata from JANUS for NEWCA #
#                                     #
#######################################

RES_COL=$(tput cols)
let RES_COL=RES_COL-8
MOVE_TO_COL="echo -en \\033[${RES_COL}G"
echo_done() {
    $MOVE_TO_COL
    echo "[ DONE ]"
    return 0
}

path="/home/test/newca/config/metadata"
    
echo -n "Get IdP production metadata"
curl -s "https://janus.wayf.dk/module.php/janus/metadataexport.php?id=prod-idp" -o $path/metadata-idp.php
echo "<?php \$metadata = array("|cat - $path/metadata-idp.php > /tmp/out && mv /tmp/out $path/metadata-idp.php
echo ");" >> $path/metadata-idp.php
echo_done

echo -n "Get SP production metadata"
curl -s "https://janus.wayf.dk/module.php/janus/metadataexport.php?id=prod-sp" -o $path/metadata-sp.php
echo "<?php \$metadata = array("|cat - $path/metadata-sp.php > /tmp/out && mv /tmp/out $path/metadata-sp.php
echo ");" >> $path/metadata-sp.php
echo_done
