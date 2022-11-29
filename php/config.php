<?php

return [

  // Default wallet label (web version only).
  'default_label' => 'My Wallet',

  // Available byte sizes.
  // Key: byte size => Value: number of words in mnemonic.
  'sizes' => [
    16 => 12,
    32 => 24,
  ],

  // Default byte size.
  'default_size' => 32,

  // Available mnemonic languages.
  // Key: language code => Value: language name/label.
  'languages' => [
    'en' => 'English',
    'cs' => 'Czech',
    'es' => 'Spanish',
    'fr' => 'French',
    'it' => 'Italian',
    'ja' => 'Japanese',
    'ko' => 'Korean',
    'pt' => 'Portuguese',
    'zh_cn' => 'Chinese (Simplified)',
    'zh_tw' => 'Chinese (Traditional)',
  ],

  // BIP39 wordlists.
  'wordlists' => [
    'en' => function () {
      $wordlists = explode(PHP_EOL, file_get_contents(__DIR__ . '/../bip39/en.txt'));
      $wordlists = array_filter($wordlists);
      return $wordlists;
    },
    'cs' => function () {
      $wordlists = explode(PHP_EOL, file_get_contents(__DIR__ . '/../bip39/cs.txt'));
      $wordlists = array_filter($wordlists);
      return $wordlists;
    },
    'es' => function () {
      $wordlists = explode(PHP_EOL, file_get_contents(__DIR__ . '/../bip39/es.txt'));
      $wordlists = array_filter($wordlists);
      return $wordlists;
    },
    'fr' => function () {
      $wordlists = explode(PHP_EOL, file_get_contents(__DIR__ . '/../bip39/fr.txt'));
      $wordlists = array_filter($wordlists);
      return $wordlists;
    },
    'it' => function () {
      $wordlists = explode(PHP_EOL, file_get_contents(__DIR__ . '/../bip39/it.txt'));
      $wordlists = array_filter($wordlists);
      return $wordlists;
    },
    'ja' => function () {
      $wordlists = explode(PHP_EOL, file_get_contents(__DIR__ . '/../bip39/ja.txt'));
      $wordlists = array_filter($wordlists);
      return $wordlists;
    },
    'ko' => function () {
      $wordlists = explode(PHP_EOL, file_get_contents(__DIR__ . '/../bip39/ko.txt'));
      $wordlists = array_filter($wordlists);
      return $wordlists;
    },
    'pt' => function () {
      $wordlists = explode(PHP_EOL, file_get_contents(__DIR__ . '/../bip39/pt.txt'));
      $wordlists = array_filter($wordlists);
      return $wordlists;
    },
    'zh_cn' => function () {
      $wordlists = explode(PHP_EOL, file_get_contents(__DIR__ . '/../bip39/zh_cn.txt'));
      $wordlists = array_filter($wordlists);
      return $wordlists;
    },
    'zh_tw' => function () {
      $wordlists = explode(PHP_EOL, file_get_contents(__DIR__ . '/../bip39/zh_tw.txt'));
      $wordlists = array_filter($wordlists);
      return $wordlists;
    },
  ],

  // Random language code.
  'random_language' => 'random',

  // Default language.
  'default_language' => 'en',

  // Default split.
  'default_split' => 2,

  // Blockchain explorer link.
  // %s => BTC address.
  'explorer' => 'https://www.blockchain.com/btc/address/%s',

  // Default salt for hashing.
  'default_hash_salt' => '',

  // Default hashing iteration count.
  'default_hash_iteration' => 2048,

];
