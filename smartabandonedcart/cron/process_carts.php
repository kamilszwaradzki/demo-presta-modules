<?php
require_once('../../../config/config.inc.php');
$module = Module::getInstanceByName('smartabandonedcart');
$module->processAbandonedCarts();
echo "Done\n";