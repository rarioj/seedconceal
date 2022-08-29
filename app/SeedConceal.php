<?php

declare(strict_types=1);

use BitWasp\Bitcoin\Address\PayToPubKeyHashAddress;
use BitWasp\Bitcoin\Address\SegwitAddress;
use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Key\Deterministic\HierarchicalKeyFactory;
use BitWasp\Bitcoin\Mnemonic\Bip39\Bip39SeedGenerator;
use BitWasp\Bitcoin\Mnemonic\MnemonicFactory;
use BitWasp\Bitcoin\Network\NetworkFactory;
use BitWasp\Bitcoin\Script\WitnessProgram;
use BitWasp\Buffertools\Buffer;
use Milon\Barcode\DNS2D;

class SeedConceal
{
  protected $config;
  protected $bip39;
  protected $size;
  protected $wordlists;

  public function __construct($config_file = __DIR__ . '/config.php')
  {
    $this->config = require_once $config_file;
    $this->bip39 = MnemonicFactory::bip39();
    $this->wordlists = $this->getWordlists(__DIR__ . '/bip39/');
    $this->setSize($this->getConfig('default_size'));
  }

  public function getConfig($name = '')
  {
    if (!empty($name)) {
      if (isset($this->config[$name])) {
        return $this->config[$name];
      }
    } else {
      return $this->config;
    }
    return null;
  }

  public function getSize()
  {
    return $this->size;
  }

  public function setSize($size)
  {
    $this->size = $size;
  }

  public function getRandomKey()
  {
    $random = new Random();
    $entropy = $random->bytes($this->getSize());
    $private_key = $entropy->getHex();
    return $private_key;
  }

  public function getMnemonicFromKey($key)
  {
    $entropy = Buffer::hex($key, $this->getSize());
    $mnemonic = $this->bip39->entropyToMnemonic($entropy);
    return $mnemonic;
  }

  public function getKeyFromMnemonic($mnemonic)
  {
    $private_key = $this->bip39->mnemonicToEntropy($mnemonic)->getHex();
    return $private_key;
  }

  public function getAllKeyInfo($key)
  {
    $network = NetworkFactory::bitcoin();
    $mnemonic = $this->getMnemonicFromKey($key);
    $root = HierarchicalKeyFactory::fromEntropy((new Bip39SeedGenerator())->getSeed($mnemonic));
    $root->derivePath("m/0/1");
    $public_key_object = $root->getPublicKey($network);
    $public_key_string = $public_key_object->getHex();
    $public_key_hash = $public_key_object->getPubKeyHash();
    //$xpriv = $root->toExtendedPrivateKey();
    //$xpub = $root->toExtendedPublicKey();
    $p2pkh_address = (new PayToPubKeyHashAddress($public_key_hash))->getAddress();
    $bech32_address = (new SegwitAddress(WitnessProgram::v0($public_key_hash)))->getAddress($network);
    return [
      'Seed Phrase' => $mnemonic,
      'Private Key' => $key,
      'Public Key' => $public_key_string,
      //'Extended Private Key' => $xpriv,
      //'Extended Public Key' => $xpub,
      'Legacy Address' => $p2pkh_address,
      'Bech32 Address' => $bech32_address,
    ];
  }

  public function getKeyDetails($key, $language = '')
  {
    $default_language = $this->getConfig('default_language');
    if (empty($language)) {
      $language = $default_language;
    }
    $info = $this->getAllKeyInfo($key);
    if ($language !== $default_language) {
      $info['Seed Phrase'] = $this->translateMnemonic($info['Seed Phrase'], $language);
    }
    return $info;
  }

  public function parseMnemonicInput($mnemonic_array = [])
  {
    $languages = $this->getConfig('languages');
    $sizes = $this->getConfig('sizes');
    $sizes = array_flip($sizes);
    $mnemonic_array = array_filter($mnemonic_array);
    $mnemonic_input = [];
    foreach ($mnemonic_array as $mnemonic_item) {
      $mnemonic_item = preg_split('/[\s]+/', $mnemonic_item);
      $mnemonic_item = array_filter($mnemonic_item);
      $mnemonic_input[] = $mnemonic_item;
    }
    $mnemonic_input = array_filter($mnemonic_input);
    $mnemonic_input = array_values($mnemonic_input);
    $translated = [];
    foreach ($mnemonic_input as $index => $mnemonic_item) {
      foreach ($languages as $lang_id => $lang_label) {
        $translated[$index]['lang_id'] = '';
        $translated[$index]['lang_label'] = '';
        $translated[$index]['word_count'] = 0;
        $translated[$index]['mnemonic'] = [];
        $translated[$index]['byte_size'] = 0;
        $translated[$index]['private_key'] = '';
        foreach ($mnemonic_item as $mnemonic_word) {
          if (!empty($this->wordlists[$lang_id][$mnemonic_word])) {
            $translated[$index]['lang_id'] = $lang_id;
            $translated[$index]['lang_label'] = $lang_label;
            $translated[$index]['word_count']++;
            $translated[$index]['mnemonic'][] = $this->wordlists[$lang_id][$mnemonic_word];
          } else {
            break;
          }
        }
        if ($translated[$index]['word_count'] === count($mnemonic_input[$index])) {
          $translated[$index]['mnemonic'] = implode(' ', $translated[$index]['mnemonic']);
          $translated[$index]['byte_size'] = $sizes[$translated[$index]['word_count']];
          $translated[$index]['private_key'] = $this->getKeyFromMnemonic($translated[$index]['mnemonic']);
          break;
        }
      }
    }
    return $translated;
  }

  public function translateMnemonic($mnemonic, $language)
  {
    $random_language = $this->getConfig('random_language');
    $mnemonic = preg_split('/[\s]+/', $mnemonic);
    $mnemonic = array_filter($mnemonic);
    $selected_language = $language;
    if ($selected_language === $random_language) {
      $selected_language = array_rand($this->getConfig('languages'), 1);
    }
    $wordlists = array_flip($this->wordlists[$selected_language]);
    $new_mnemonic = [];
    foreach ($mnemonic as $word) {
      $new_mnemonic[] = $wordlists[$word];
    }
    return implode(' ', $new_mnemonic);
  }

  public function hashText($text, $salt = '', $iteration = 0)
  {
    $default_hash_salt = $this->getConfig('default_hash_salt');
    $default_hash_iteration = $this->getConfig('default_hash_iteration');
    if (empty($salt)) {
      $salt = $default_hash_salt;
    }
    if (empty($iteration) || $iteration <= 0) {
      $iteration = $default_hash_iteration;
    }
    $text = hash_pbkdf2('sha256', $text, $salt, $iteration, $this->getSize() * 2);
    $hash = substr(hash('sha256', $text), 0, $this->getSize() * 2);
    return $hash;
  }

  public function xorKeys($key1, $key2)
  {
    $hex = gmp_strval(gmp_xor(gmp_init('0x' . $key1), gmp_init('0x' . $key2)), 16);
    $hex = str_pad($hex, $this->getSize() * 2, '0', STR_PAD_LEFT);
    return $hex;
  }

  public function obscureKeys($key1, $key2 = '', $split = 1)
  {
    $output = [];
    if (!empty($key2)) {
      $current_xored = $this->xorKeys($key1, $key2);
    } else {
      $current_xored = $key1;
    }
    $output[] = $current_xored;
    for ($i = 1; $i < $split; $i++) {
      $current_seed = $this->getRandomKey();
      $output[$i - 1] = $current_seed;
      $current_xored = $this->xorKeys($current_xored, $current_seed);
      $output[$i] = $current_xored;
    }
    return $output;
  }

  protected function getWordlists($path)
  {
    $languages = $this->getConfig('languages');
    $default_language = $this->getConfig('default_language');
    $wordlists = [];
    $wordlist_default = explode(PHP_EOL, file_get_contents($path . $default_language . '.txt'));
    $wordlist_default = array_filter($wordlist_default);
    $wordlist_default = array_map('trim', $wordlist_default);
    $wordlists[$default_language] = $wordlist_default;
    foreach ($languages as $lang_id => $lang_label) {
      if ($lang_id === $default_language) {
        continue;
      }
      $dictionaries = explode(PHP_EOL, file_get_contents($path . $lang_id . '.txt'));
      $dictionaries = array_filter($dictionaries);
      $dictionaries = array_map('trim', $dictionaries);
      $wordlists[$lang_id] = array_combine($dictionaries, $wordlists[$default_language]);
    }
    $wordlists[$default_language] = array_combine($wordlists[$default_language], $wordlists[$default_language]);
    return $wordlists;
  }
}

class SeedConcealCli extends SeedConceal
{
  const CONSOLE_BOLD = "\033[1m";
  const CONSOLE_NORMAL = "\033[0m";

  public function print($label, $value)
  {
    echo $label . ': ';
    echo self::CONSOLE_BOLD . $value . self::CONSOLE_NORMAL . PHP_EOL;
  }

  public function printHeading($title)
  {
    echo PHP_EOL;
    $this->print('::', $title);
    echo PHP_EOL;
  }

  public function printDetails($info)
  {
    foreach ($info as $index => $value) {
      if ($index === 'Legacy Address' || $index === 'Bech32 Address') {
        $value = sprintf($this->getConfig('explorer'), $value);
      }
      $this->print($index, $value);
    }
    echo PHP_EOL;
  }
}

class SeedConcealWeb extends SeedConceal
{
  public function getQrcode($mnemonic)
  {
    $qrcode = new DNS2D();
    $qrcode->setStorPath(__DIR__ . '/cache/');
    $image = $qrcode->getBarcodePNG($mnemonic, 'QRCODE');
    return $image;
  }

  public function printDetails($info)
  {
    echo '<dl>';
    foreach ($info as $index => $value) {
      if ($index === 'Legacy Address' || $index === 'Bech32 Address') {
        $value = '<a href="' . sprintf($this->getConfig('explorer'), $value) .  '" target="_blank">' . $value . '</a>';
      }
      echo '<dt><strong>' . $index . '</strong></dt>';
      echo '<dd>' . $value . '</dd>';
    }
    echo '</dl>';
  }
}
