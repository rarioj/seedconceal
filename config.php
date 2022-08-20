<?php

return [
  // Mnemonic size: 12 words or 24 words.
  // Key: byte size => Value: number of words.
  'sizes' => [
    16 => 12,
    32 => 24,
  ],
  // Available mnemonic languages.
  // Key: language code => Value: language name/label.
  'languages' => [
    'en' => 'English',
    'cs' => 'Czech',
    'es' => 'Spanish',
    'fr' => 'French',
    'id' => 'Indonesian',
    'it' => 'Italian',
  ],
  // Available mnemonic split sizes.
  // Key: split size => Value: split option label.
  'splits' => [
    1 => 'No split',
    2 => '2 mnemonics',
    3 => '3 mnemonics',
    4 => '4 mnemonics',
    5 => '5 mnemonics',
  ],
  // Debugging mode.
  'debug' => TRUE,
];
