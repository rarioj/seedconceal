<?php

declare(strict_types=1);
error_reporting(E_ALL ^ E_DEPRECATED);
set_time_limit(0);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../SeedConceal.php';

$sc = new SeedConcealCli();
$default_hash_salt = $sc->config('default_hash_salt');
$default_hash_iteration = $sc->config('default_hash_iteration');

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

$input_translated_all = $sc->parse($input_mnemonic_all);
$output_key = '';
foreach ($input_translated_all as $index => $input_translated) {
  if (empty($input_translated['lang_id'])) {
    echo '[E] Unable to determine the seed phrase language.' . PHP_EOL;
    exit;
  }
  if (empty($input_translated['entropy'])) {
    echo '[E] The input seed phrase is not valid.' . PHP_EOL;
    exit;
  }
  $sc->size($input_translated['byte_size']);
  if (empty($output_key)) {
    $output_key = $input_translated['entropy'];
  } else {
    $output_key = $sc->xor($output_key, $input_translated['entropy']);
  }
}

echo '[?] Enter the password obscuring the seed phrase.' . PHP_EOL;
$input_password = readline('[>] ');
$input_salt = '';
$input_iteration = 0;
if (!empty($input_password)) {
  echo '[?] Enter the salt (if any).' . PHP_EOL;
  $input_salt = readline('[>] ');
  if (!empty($input_salt)) {
    echo '[?] Enter the number of hashing iteration (default ' . $default_hash_iteration . ').' . PHP_EOL;
    $input_iteration = (int) readline('[>] ');
    if (empty($input_iteration) || $input_iteration <= 0) {
      echo '[I] Using default iteration: ' . $default_hash_iteration . PHP_EOL;
      $input_iteration = $default_hash_iteration;
    }
  }
}

if (!empty($input_password)) {
  $input_password = $sc->hash($input_password, $input_salt, $input_iteration);
  $entropy = $sc->xor($output_key, $input_password);
} else {
  $entropy = $output_key;
}

$details = $sc->details($entropy);
$sc->print($details);
