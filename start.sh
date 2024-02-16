echo "Deleting Crashdumps"
rm -r crashdumps/

echo "Deleting server.log"
rm -r server.log

echo "Building Resource Packs"
bin/php7/bin/php resource_packs/build_pack.php

echo "Starting Server..."
bin/php7/bin/php PocketMine*.phar