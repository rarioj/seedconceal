<?php

declare(strict_types=1);
error_reporting(E_ALL ^ E_DEPRECATED);

require_once __DIR__ . '/../vendor/autoload.php';

use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Mnemonic\MnemonicFactory;

$config = require_once __DIR__ . '/../config.php';
$default_size = 16;
$cbold = "\033[1m";
$cnorm = "\033[0m";
$random = new Random();
$bip39 = MnemonicFactory::bip39();

echo PHP_EOL;
echo 'Use 16 bytes to generate a 12-word mnemonic and 32 bytes for a 24-word' . PHP_EOL;
$size = readline('Enter byte size [' . implode('|', array_keys($config['sizes'])) . '] (default 16): ');
if (empty($size) || empty($config['sizes'][$size])) {
  echo 'Using default byte size: ' . $default_size . PHP_EOL;
  $size = $default_size;
}

$entropy = $random->bytes($size);
$mnemonic = $bip39->entropyToMnemonic($entropy);

$mnemonic_hex = $entropy->getHex();
$mnemonic_en = $mnemonic;

echo PHP_EOL;
echo 'Mnemonic words: ' . PHP_EOL;
echo $cbold . $mnemonic_en . $cnorm . PHP_EOL;
echo PHP_EOL;
