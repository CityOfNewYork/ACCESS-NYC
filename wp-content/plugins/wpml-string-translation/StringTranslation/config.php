<?php

$interfaceMappings = require __DIR__ . '/config-interface-mappings.php';
$hookHandlers      = require __DIR__ . '/config-hook-handlers.php';

return [
	'interfaceMappings' => $interfaceMappings,
	'hookHandlers'      => $hookHandlers,
];
