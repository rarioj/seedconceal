<?php

use BitWasp\Bitcoin\Address\PayToPubKeyHashAddress;
use BitWasp\Bitcoin\Address\SegwitAddress;
use BitWasp\Bitcoin\Script\WitnessProgram;

return [

  // Legacy Address Format
  'Legacy' => [
    'format' => 'P2PKH',
    'path' => "m/44'/0'/0'/0/0",
    'callback' => function ($hash) {
      return (new PayToPubKeyHashAddress($hash))->getAddress();
    },
  ],

  // Native Segwit Address Format
  'Native Segwit' => [
    'format' => 'Bech32',
    'path' => "m/84'/0'/0'/0/0",
    'callback' => function ($hash) {
      return (new SegwitAddress(WitnessProgram::v0($hash)))->getAddress();
    },
  ],

];
