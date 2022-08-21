<?php

declare(strict_types=1);
error_reporting(E_ALL ^ E_DEPRECATED);

require_once __DIR__ . '/../vendor/autoload.php';

use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Mnemonic\MnemonicFactory;

$config = require_once __DIR__ . '/../config.php';
$default_split = 1;
$default_lang = 'en';
$cbold = "\033[1m";
$cnorm = "\033[0m";
$random = new Random();
$bip39 = MnemonicFactory::bip39();
$final = $final_en = [];

echo PHP_EOL;
$mnemonic = readline('Enter valid mnemonic: ');
if (empty($mnemonic)) {
  echo 'ERROR: Valid mnemonic is empty' . PHP_EOL;
  exit;
}

echo PHP_EOL;
$password = readline('Enter password: ');
if (empty($password)) {
  echo 'ERROR: Password is empty' . PHP_EOL;
  exit;
}

echo PHP_EOL;
$split = readline('Split into? (default 1): ');
if (empty($split) || empty($config['splits'][$split])) {
  echo 'Using default split: ' . $default_split . PHP_EOL;
  $split = $default_split;
}

echo PHP_EOL;
echo 'Enter "-" excluding the quote to use a random language' . PHP_EOL;
$lang = readline('Language output [-|' . implode('|', array_keys($config['languages'])) . '] (default en): ');
if (empty($lang) || empty($config['languages'][$lang])) {
  if ($lang !== '-') {
    echo 'Using default language: ' . $default_lang . PHP_EOL;
    $lang = $default_lang;
  }
}

$mnemonic_input = preg_split('/[\s]+/', $mnemonic);
$mnemonic_input = array_filter($mnemonic_input);

$dictionaries = [];
$dictionaries['en'] = explode(PHP_EOL, file_get_contents(__DIR__ . '/../bip39/en.txt'));
foreach ($config['languages'] as $lang_id => $lang_label) {
  if ($lang_id === 'en') {
    continue;
  }
  $wordlists = explode(PHP_EOL, file_get_contents(__DIR__ . '/../bip39/' . $lang_id . '.txt'));
  $dictionaries[$lang_id] = array_combine($wordlists, $dictionaries['en']);
}
$dictionaries['en'] = array_combine($dictionaries['en'], $dictionaries['en']);

$mnemonic_lang_id = $mnemonic_lang_label = '';
$mnemonic_lang_count = 0;
$mnemonic_words = [];
foreach ($config['languages'] as $lang_id => $lang_label) {
  $mnemonic_lang_id = $mnemonic_lang_label = '';
  $mnemonic_lang_count = 0;
  $mnemonic_words = [];
  foreach ($mnemonic_input as $mnemonic_word) {
    if (!empty($dictionaries[$lang_id][$mnemonic_word])) {
      $mnemonic_lang_id = $lang_id;
      $mnemonic_lang_label = $lang_label;
      $mnemonic_lang_count++;
      $mnemonic_words[] = $dictionaries[$lang_id][$mnemonic_word];
    } else {
      break;
    }
  }
  if ($mnemonic_lang_count === count($mnemonic_input)) {
    break;
  }
}

if (empty($mnemonic_words)) {
  echo 'ERROR: Unable to determine mnemonic language';
  exit;
}

$available_sizes = array_flip($config['sizes']);
$byte_size = $available_sizes[count($mnemonic_words)] * 2;

$mnemonic_input = implode(' ', $mnemonic_words);
$entropy_mnemonic = $bip39->mnemonicToEntropy($mnemonic_input)->getHex();

$entropy_password = bin2hex($password);
$entropy_password = str_pad($entropy_password, $byte_size, $entropy_password, STR_PAD_LEFT);
$entropy_password = substr(hash('sha256', $entropy_password), 0, $byte_size);

$entropy_xored = gmp_strval(gmp_xor(gmp_init('0x' . $entropy_mnemonic), gmp_init('0x' . $entropy_password)), 16);
$entropy_xored = str_pad($entropy_xored, $byte_size, '0', STR_PAD_LEFT);

$entropy_buffer = Buffer::hex($entropy_xored, $byte_size / 2);
$final_en[0] = $bip39->entropyToMnemonic($entropy_buffer);

for ($i = 1; $i < $split; $i++) {
  $entropy_password = substr(hash('sha256', $entropy_password), 0, $byte_size);
  $entropy_xored = gmp_strval(gmp_xor(gmp_init('0x' . $entropy_xored), gmp_init('0x' . $entropy_password)), 16);
  $entropy_xored = str_pad($entropy_xored, $byte_size, '0', STR_PAD_LEFT);
  $entropy_buffer = Buffer::hex($entropy_xored, $byte_size / 2);
  $final_en[$i] = $bip39->entropyToMnemonic($entropy_buffer);
}

if (!empty($final_en)) {
  if ($lang !== 'en') {
    foreach ($final_en as $split_mnemonic_index => $split_mnemonic_string) {
      $mstring = preg_split('/[\s]+/', $split_mnemonic_string);
      $mstring = array_filter($mstring);
      $mlang = $lang;
      if ($mlang === '-') {
        $mlang = array_rand($config['languages'], 1);
      }
      $fdictionaries = array_flip($dictionaries[$mlang]);
      $mnemonic_final = [];
      foreach ($mstring as $mstring_val) {
        $mnemonic_final[] = $fdictionaries[$mstring_val];
      }
      $final[$split_mnemonic_index] = implode(' ', $mnemonic_final);
    }
  } else {
    $final = $final_en;
  }
}

if (!empty($final)) {
  echo PHP_EOL;
  echo 'Mnemonic words: ' . PHP_EOL;
  foreach ($final as $index => $mnemonic) {
    echo PHP_EOL;
    echo 'Sequence #' . ($index + 1) . ':' . PHP_EOL;
    echo $cbold . $mnemonic . $cnorm . PHP_EOL;
  }
  echo PHP_EOL;
}
