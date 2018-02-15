<?php

$cfg = require __DIR__ . '/../../vendor/mediawiki/mediawiki-phan-config/src/config.php';

// TODO: Remove once using a default config that
// includes https://gerrit.wikimedia.org/r/#/c/411081/
$cfg['directory_list'][] = 'maintenance/';

return $cfg;
