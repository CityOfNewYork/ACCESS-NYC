<?php

namespace NYCO\Config;

/**
 * Dependencies
 */

use Illuminate;
use Exception;

/**
 * Config
 */

require_once __DIR__ . '/vendor/autoload.php';

const FILE_NAME = 'env.php';

/**
 * Functions
 */

try {
  $secret = Illuminate\Support\Str::random(16);
  $fileName = FILE_NAME;
  file_put_contents(
    $fileName,
    "<?php\n\n" .
    "return [\n" .
    "  'key' => '$secret'\n" .
    "];\n\n"
  );
  echo "New secret generated in $fileName\n";
  return $secret;
} catch (\Exception $error) {
  echo "Caught exception: $error->getMessage()\n";
}

exit;
