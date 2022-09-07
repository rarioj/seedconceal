<?php

declare(strict_types=1);
error_reporting(E_ALL ^ E_DEPRECATED);
set_time_limit(0);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../SeedConceal.php';

$sc = new SeedConcealCli();
$default_hash_salt = $sc->config('default_hash_salt');
$default_hash_iteration = $sc->config('default_hash_iteration');
$default_split = $sc->config('default_split');
$languages = $sc->config('languages');
$random_language = $sc->config('random_language');
$default_language = $sc->config('default_language');

echo '[?] Enter the seed phrase.' . PHP_EOL;
$input_mnemonic = readline('[>] ');
if (empty($input_mnemonic)) {
  echo '[E] A seed phrase is required.' . PHP_EOL;
  exit;
}

$input_translated = $sc->parse([$input_mnemonic]);
if (empty($input_translated[0]['lang_id'])) {
  echo '[E] Unable to determine the seed phrase language.' . PHP_EOL;
  exit;
}
if (empty($input_translated[0]['entropy'])) {
  echo '[E] The input seed phrase is not valid.' . PHP_EOL;
  exit;
}

echo '[?] Enter a password (optional but strongly recommended).' . PHP_EOL;
$input_password = readline('[>] ');
$input_salt = '';
$input_iteration = 0;
if (!empty($input_password)) {
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
}

echo '[?] Enter the number of the split (default ' . $default_split . ').' . PHP_EOL;
$input_split = (int) readline('[>] ');
if (empty($input_split) || $input_split < 0) {
  echo '[I] Using default split: ' . $default_split . PHP_EOL;
  $input_split = $default_split;
}

echo '[?] Enter the language output [' . $random_language . '|' . implode('|', array_keys($languages)) . '] (default ' . $default_language . ').' . PHP_EOL;
$input_language = readline('[>] ');
if (empty($input_language) || empty($languages[$input_language])) {
  if ($input_language !== $random_language) {
    echo '[I] Using default language: ' . $default_language . PHP_EOL;
    $input_language = $default_language;
  }
}

$translated = $input_translated[0];
$sc->size($translated['byte_size']);
if (!empty($input_password)) {
  $input_password = $sc->hash($input_password, $input_salt, $input_iteration);
}
$entropies = $sc->obscure($translated['entropy'], $input_password, $input_split);
$mnemonics = [];
foreach ($entropies as $index => $entropy) {
  $details = $sc->details($entropy);
  $mnemonics[] = $sc->translate($details['Seed Phrase'], $input_language);
  $sc->print($details, 'D E T A I L S  # ' . ($index + 1));
}

$sc->print($mnemonics, 'O U T P U T');
