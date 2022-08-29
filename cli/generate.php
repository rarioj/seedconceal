<?php

declare(strict_types=1);
error_reporting(E_ALL ^ E_DEPRECATED);
set_time_limit(0);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/SeedConceal.php';

$sc = new SeedConcealCli();
$sizes = $sc->getConfig('sizes');
$default_size = $sc->getConfig('default_size');
$default_hash_salt = $sc->getConfig('default_hash_salt');
$default_hash_iteration = $sc->getConfig('default_hash_iteration');

$sc->printHeading('INPUT');

echo '[?] Enter a passphrase to generate a deterministic wallet.' . PHP_EOL;
echo '    Leave blank to generate a random wallet.' . PHP_EOL;
$input_passphrase = readline('[>] ');
$input_salt = $input_password = '';
$input_iteration = 0;
if (!empty($input_passphrase)) {
  echo '[?] Enter a salt (default ' . $default_hash_salt . ').' . PHP_EOL;
  $input_salt = readline('[>] ');
  if (empty($input_salt)) {
    echo '[I] Using default salt: ' . $default_hash_salt . PHP_EOL;
    $input_salt = $default_hash_salt;
  }
  echo '[?] Enter the number of hashing iteration (default ' . $default_hash_iteration . ').' . PHP_EOL;
  $input_iteration = (int) readline('[>] ');
  if (empty($input_iteration) || $input_iteration <= 0) {
    echo '[I] Using default iteration: ' . $default_hash_iteration . PHP_EOL;
    $input_iteration = $default_hash_iteration;
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

$sc->printHeading('OUTPUT');

$sc->setSize($input_size);
$private_key = $sc->getRandomKey();
if (!empty($input_passphrase)) {
  $input_passphrase = $sc->hashText($input_passphrase, $input_salt, $input_iteration);
  if (!empty($input_password)) {
    $input_password = $sc->hashText($input_password, $input_salt, $input_iteration);
    $private_key = $sc->xorKeys($input_passphrase, $input_password);
  } else {
    $private_key = $input_passphrase;
  }
}

$sc->printDetails($sc->getKeyDetails($private_key));
