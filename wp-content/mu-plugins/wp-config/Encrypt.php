<?php

namespace NYCO\Config;

/**
 * Dependencies
 */

require_once __DIR__ . '/vendor/autoload.php';

use Spyc;
use Illuminate;

/**
 * Functions
 */

$path = realpath(__DIR__ . '/../config/config.yml');

if (file_exists($path)) {
  $config = Spyc::YAMLLoad($path);
  $secret = require_once('env.php');
  $encrypter = new \Illuminate\Encryption\Encrypter($secret['key']);
  foreach ($config as $environment => $settings) {
    foreach ($settings as $key => $value) {
      $settings[$key] = $encrypter->encrypt($value);
    }
    $config[$environment] = $settings;
  }
  file_put_contents($path, Spyc::YAMLDump($config, false, false, false));
}
