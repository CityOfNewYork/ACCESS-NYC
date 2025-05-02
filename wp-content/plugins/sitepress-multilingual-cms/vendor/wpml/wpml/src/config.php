<?php

return [
  // Mapping of interfaces to implementations.
  'interfaceMappings' => require __DIR__ . '/config-interface-mappings.php',

  // Class specific mappings.
  'classDefinitions' => require __DIR__ . '/config-class-definitions.php',

  // Admin pages.
  'adminPages' => require __DIR__ . '/config-admin-pages.php',

  // Admin notices.
  'adminNotices' => require __DIR__ . '/config-admin-notices.php',

  // General endpoints. Page specific endpoints are defined in config-admin-pages.php.
  'endpoints' => require __DIR__ . '/config-endpoints.php',

  // General scripts. Page specific scripts are defined in config-admin-pages.php.
  'scripts' => require __DIR__ . '/config-scripts.php',

  // Updates to db schema or other data.
  'updates' => require __DIR__ . '/config-updates.php',

  // EVENTS
  // Events which are triggered by a 3rd party (WordPress other plugin) AND
  // which are triggering the start of some WPML code, goes into
  // UserInterface/Web/Infrastructure/WordPress/CompositionRoot/Config/ConfigEvents.php.
];
