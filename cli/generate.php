<?php

declare(strict_types=1);
error_reporting(E_ALL ^ E_DEPRECATED);
set_time_limit(0);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/SeedConceal.php';

$sc = new SeedConcealCli();
$sizes = $sc->config('sizes');
$default_size = $sc->config('default_size');
$default_hash_salt = $sc->config('default_hash_salt');
$default_hash_iteration = $sc->config('default_hash_iteration');

echo '[?] Enter a passphrase to generate a deterministic wallet.' . PHP_EOL;
echo '    Leave blank to generate a random wallet.' . PHP_EOL;
$input_passphrase = readline('[>] ');
$input_salt = $input_password = '';
$input_iteration = 0;
if (!empty($input_passphrase)) {
  echo '[?] Enter a salt (optional but strongly recommended).' . PHP_EOL;
  $input_salt = readline('[>] ');
  if (!empty($input_salt)) {
    echo '[?] Enter the number of hashing iteration (default ' . $default_hash_iteration . ').' . PHP_EOL;
    $input_iteration = (int) readline('[>] ');
    if (empty($input_iteration) || $input_iteration <= 0) {
      echo '[I] Using default iteration: ' . $default_hash_iteration . PHP_EOL;
      $input_iteration = $default_hash_iteration;
    }
  }
  echo '[?] Enter a password (optional but strongly recommended).' . PHP_EOL;
  $input_password = readline('[>] ');
}

echo '[?] Enter the byte size [' . implode('|', array_keys($sizes)) . '] (default ' . $default_size . ').' . PHP_EOL;
$input_size = (int) readline('[>] ');
if (empty($input_size) || empty($sizes[$input_size])) {
  echo '[I] Using default byte size: ' . $default_size . PHP_EOL;
  $input_size = $default_size;
}

$sc->size($input_size);
$entropy = $sc->entropy();
if (!empty($input_passphrase)) {
  $input_passphrase = $sc->hash($input_passphrase, $input_salt, $input_iteration);
  if (!empty($input_password)) {
    $input_password = $sc->hash($input_password, $input_salt, $input_iteration);
    $entropy = $sc->xor($input_passphrase, $input_password);
  } else {
    $entropy = $input_passphrase;
  }
}
$details = $sc->details($entropy);
$sc->print($details, 'D E T A I L S');

$sc->print([$details['Seed Phrase']], 'O U T P U T');
