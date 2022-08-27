<?php

return [

  // Available byte sizes.
  // Key: byte size => Value: number of words in mnemonic.
  'sizes' => [
    16 => 12,
    32 => 24,
  ],

  // Default byte size.
  'default_size' => 16,

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

  // Random language code.
  'random_language' => 'random',

  // Default language.
  'default_language' => 'en',

  // Default split.
  'default_split' => 1,

  // Blockchain explorer link.
  // %s => BTC address.
  'explorer' => 'https://www.blockchain.com/btc/address/%s',

  // Default salt for hashing.
  'default_hash_salt' => 'SeedConceal',

  // Default hashing iteration count.
  'default_hash_iteration' => 1024,

];
