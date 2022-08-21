<?php

declare(strict_types=1);
error_reporting(E_ALL ^ E_DEPRECATED);

require_once __DIR__ . '/../vendor/autoload.php';

use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Mnemonic\MnemonicFactory;

$config = require_once __DIR__ . '/../config.php';
$cbold = "\033[1m";
$cnorm = "\033[0m";
$bip39 = MnemonicFactory::bip39();
$mnemonic_lines = [];
$final_en = '';

echo PHP_EOL;
$mnemonic = readline('Enter obscured mnemonic: ');
if (empty($mnemonic)) {
  echo 'ERROR: Obscured mnemonic is empty' . PHP_EOL;
  exit;
}
$mnemonic_lines[] = $mnemonic;

echo PHP_EOL;
echo 'Input empty mnemonic once all sequences are entered' . PHP_EOL;

do {
  echo PHP_EOL;
  $mnemonic = readline('Enter the next mnemonic: ');
  $mnemonic_lines[] = $mnemonic;
} while (!empty($mnemonic));

echo PHP_EOL;
$password = readline('Enter password: ');
if (empty($password)) {
  echo 'ERROR: Password is empty' . PHP_EOL;
  exit;
}

$mnemonic_lines = array_filter($mnemonic_lines);
$mnemonic_input_all = [];
foreach ($mnemonic_lines as $mnemonic_line) {
  $mnemonic_temp = preg_split('/[\s]+/', $mnemonic_line);
  $mnemonic_temp = array_filter($mnemonic_temp);
  $mnemonic_input_all[] = $mnemonic_temp;
}
$mnemonic_input_all = array_filter($mnemonic_input_all);
$mnemonic_input_all = array_values($mnemonic_input_all);

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

$mnemonic_lang_id = $mnemonic_lang_label = $mnemonic_lang_count = $mnemonic_words = [];
foreach ($mnemonic_input_all as $index => $mnemonic_input) {
  foreach ($config['languages'] as $lang_id => $lang_label) {
    $mnemonic_lang_id[$index] = '';
    $mnemonic_lang_label[$index] = '';
    $mnemonic_lang_count[$index] = 0;
    $mnemonic_words[$index] = [];
    foreach ($mnemonic_input as $mnemonic_word) {
      if (!empty($dictionaries[$lang_id][$mnemonic_word])) {
        $mnemonic_lang_id[$index] = $lang_id;
        $mnemonic_lang_label[$index] = $lang_label;
        $mnemonic_lang_count[$index]++;
        $mnemonic_words[$index][] = $dictionaries[$lang_id][$mnemonic_word];
      } else {
        break;
      }
    }
    if ($mnemonic_lang_count[$index] === count($mnemonic_input_all[$index])) {
      break;
    }
  }
}

if (empty($mnemonic_words)) {
  echo 'ERROR: Unable to determine mnemonic language';
  exit;
}

$available_sizes = array_flip($config['sizes']);
$byte_size = $available_sizes[count($mnemonic_words[0])] * 2;

$entropy_password = bin2hex($password);
$entropy_password = str_pad($entropy_password, $byte_size, $entropy_password, STR_PAD_LEFT);
$entropy_passwords = [];
for ($i = 0; $i < count($mnemonic_words); $i++) {
  $entropy_password = substr(hash('sha256', $entropy_password), 0, $byte_size);
  $entropy_passwords[] = $entropy_password;
}

$mnemonic_words = array_reverse($mnemonic_words);
$entropy_passwords = array_reverse($entropy_passwords);

$entropy_xored = null;
foreach ($mnemonic_words as $index => $mnemonic_word) {
  $mnemonic_input = implode(' ', $mnemonic_word);
  $entropy_mnemonic = $bip39->mnemonicToEntropy($mnemonic_input)->getHex();
  $entropy_xored = gmp_strval(gmp_xor(gmp_init('0x' . $entropy_mnemonic), gmp_init('0x' . $entropy_passwords[$index])), 16);
  $entropy_xored = str_pad($entropy_xored, $byte_size, '0', STR_PAD_LEFT);
}

$entropy_buffer = Buffer::hex($entropy_xored, $byte_size / 2);
$final_en = $bip39->entropyToMnemonic($entropy_buffer);

if (!empty($final_en)) {
  echo PHP_EOL;
  echo 'Mnemonic words: ' . PHP_EOL;
  echo $cbold . $final_en . $cnorm . PHP_EOL;
  echo PHP_EOL;
}
