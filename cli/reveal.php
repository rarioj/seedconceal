<?php

declare(strict_types=1);
error_reporting(E_ALL ^ E_DEPRECATED);
set_time_limit(0);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/SeedConceal.php';

$sc = new SeedConcealCli();
$default_hash_salt = $sc->getConfig('default_hash_salt');
$default_hash_iteration = $sc->getConfig('default_hash_iteration');

$sc->printHeading('INPUT');

$input_mnemonic_all = [];
echo '[?] Enter the seed phrase.' . PHP_EOL;
$input_mnemonic = readline('[>] ');
if (empty($input_mnemonic)) {
  echo '[E] A seed phrase is required.' . PHP_EOL;
  exit;
}
$input_mnemonic_all[] = $input_mnemonic;

do {
  echo '[?] Enter the next seed phrase.' . PHP_EOL;
  echo '    Leave blank once all seed phrases are entered.' . PHP_EOL;
  $input_mnemonic = readline('[>] ');
  $input_mnemonic_all[] = $input_mnemonic;
} while (!empty($input_mnemonic));

$input_translated_all = $sc->parseMnemonicInput($input_mnemonic_all);
$output_key = '';
foreach ($input_translated_all as $index => $input_translated) {
  if (empty($input_translated['lang_id'])) {
    echo '[E] Unable to determine the seed phrase language.' . PHP_EOL;
    exit;
  }
  if (empty($input_translated['private_key'])) {
    echo '[E] The input seed phrase is not valid.' . PHP_EOL;
    exit;
  }
  $sc->setSize($input_translated['byte_size']);
  if (empty($output_key)) {
    $output_key = $input_translated['private_key'];
  } else {
    $output_key = $sc->xorKeys($output_key, $input_translated['private_key']);
  }
}

echo '[?] Enter the password obscuring the seed phrase.' . PHP_EOL;
$input_password = readline('[>] ');
$input_salt = '';
$input_iteration = 0;
if (!empty($input_password)) {
  echo '[?] Enter a salt (default ' . $default_hash_salt . ').' . PHP_EOL;
  $input_salt = readline('[>] ');
  if (empty($input_salt)) {
    echo '[I] Using default salt: ' . $default_hash_salt . PHP_EOL;
    $input_salt = $default_hash_salt;
  }
  echo '[?] Enter the number of hashing iteration (default ' . $default_hash_iteration . ').' . PHP_EOL;
  $input_iteration = (int) readline('[>] ');
  if (empty($input_iteration) || $input_iteration < 0) {
    echo '[I] Using default iteration: ' . $default_hash_iteration . PHP_EOL;
    $input_iteration = $default_hash_iteration;
  }
}

$sc->printHeading('OUTPUT');

if (!empty($input_password)) {
  $input_password = $sc->hashText($input_password, $input_salt, $input_iteration);
  $private_key = $sc->xorKeys($output_key, $input_password);
} else {
  $private_key = $output_key;
}

$sc->printDetails($sc->getKeyDetails($private_key));
