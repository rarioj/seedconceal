<?php

declare(strict_types=1);

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Key\Deterministic\HierarchicalKeyFactory;
use BitWasp\Bitcoin\Mnemonic\Bip39\Bip39SeedGenerator;
use BitWasp\Bitcoin\Mnemonic\MnemonicFactory;
use BitWasp\Buffertools\Buffer;
use Milon\Barcode\DNS2D;

class SeedConceal
{
  protected $_address;
  protected $_config;
  protected $_size;
  protected $_wordlists;

  public function __construct()
  {
    $this->_address = require_once __DIR__ . '/address.php';
    $this->_config = require_once __DIR__ . '/config.php';
    $this->_wordlists = $this->wordlists(__DIR__ . '/bip39/');
    $this->size($this->config('default_size'));
  }

  public function config($name = '')
  {
    if (!empty($name)) {
      if (isset($this->_config[$name])) {
        return $this->_config[$name];
      } else {
        return null;
      }
    }
    return $this->_config;
  }

  public function size($size = 0)
  {
    $sizes = $this->config('sizes');
    if (!empty($size) && !empty($sizes[$size])) {
      $this->_size = $size;
    }
    return $this->_size;
  }

  public function entropy()
  {
    $adapter = Bitcoin::getEcAdapter();
    $random = new Random();
    do {
      $buffer = $random->bytes($this->size());
    } while (!$adapter->validatePrivateKey($buffer));
    return $buffer->getHex();
  }

  public function details($entropy)
  {
    $details = [];
    $buffer = Buffer::hex($entropy, $this->size());
    $mnemonic = MnemonicFactory::bip39()->entropyToMnemonic($buffer);
    $seed = (new Bip39SeedGenerator())->getSeed($mnemonic);
    $root = HierarchicalKeyFactory::fromEntropy($seed);
    $xpriv = $root->toExtendedPrivateKey();
    $details['Seed Phrase'] = $mnemonic;
    $details['Seed Hex'] = $seed->getHex();
    $details['Entropy'] = $entropy;
    $details['Root Ext. Private'] = $xpriv;
    $details['Root Ext. Public'] = $root->toExtendedPublicKey();
    $details['Root Private'] = $root->getPrivateKey()->getHex();
    $details['Root Public'] = $root->getPublicKey()->getHex();
    $details['Root WIF'] = $root->getPrivateKey()->toWif();
    foreach ($this->_address as $label => $derivation) {
      $derived = HierarchicalKeyFactory::fromExtended($xpriv)->derivePath($derivation['path']);
      $hash = $derived->getPublicKey()->getPubKeyHash();
      $format = $derivation['format'];
      $details[$label][$format . ' Ext. Private'] = $derived->toExtendedPrivateKey();
      $details[$label][$format . ' Ext. Public'] = $derived->toExtendedPublicKey();
      $details[$label][$format . ' Private'] = $derived->getPrivateKey()->getHex();
      $details[$label][$format . ' Public'] = $derived->getPublicKey()->getHex();
      $details[$label][$format . ' WIF'] = $derived->getPrivateKey()->toWif();
      $details[$label][$format . ' Address'] = $derivation['callback']($hash);
    }
    return $details;
  }

  public function parse($mnemonic_array = [])
  {
    $languages = $this->config('languages');
    $sizes = $this->config('sizes');
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
        $translated[$index]['entropy'] = '';
        foreach ($mnemonic_item as $mnemonic_word) {
          if (!empty($this->_wordlists[$lang_id][$mnemonic_word])) {
            $translated[$index]['lang_id'] = $lang_id;
            $translated[$index]['lang_label'] = $lang_label;
            $translated[$index]['word_count']++;
            $translated[$index]['mnemonic'][] = $this->_wordlists[$lang_id][$mnemonic_word];
          } else {
            break;
          }
        }
        if ($translated[$index]['word_count'] === count($mnemonic_input[$index])) {
          $translated[$index]['mnemonic'] = implode(' ', $translated[$index]['mnemonic']);
          $translated[$index]['byte_size'] = $sizes[$translated[$index]['word_count']];
          $translated[$index]['entropy'] = MnemonicFactory::bip39()->mnemonicToEntropy($translated[$index]['mnemonic'])->getHex();
          break;
        }
      }
    }
    return $translated;
  }

  public function translate($mnemonic, $language)
  {
    $mnemonic = preg_split('/[\s]+/', $mnemonic);
    $mnemonic = array_filter($mnemonic);
    $selected_language = $language;
    if ($selected_language === $this->config('random_language')) {
      $selected_language = array_rand($this->config('languages'), 1);
    }
    $wordlists = array_flip($this->_wordlists[$selected_language]);
    $new_mnemonic = [];
    foreach ($mnemonic as $word) {
      $new_mnemonic[] = $wordlists[$word];
    }
    return implode(' ', $new_mnemonic);
  }

  public function hash($text, $salt = '', $iteration = 0)
  {
    if (!empty($salt) && !empty($iteration)) {
      $hash = hash_pbkdf2('sha256', $text, $salt, $iteration, $this->size() * 2);
    } else {
      $hash = substr(hash('sha256', $text), 0, $this->size() * 2);
    }
    return $hash;
  }

  public function xor($key1, $key2)
  {
    $hex = gmp_strval(gmp_xor(gmp_init('0x' . $key1), gmp_init('0x' . $key2)), 16);
    $hex = str_pad($hex, $this->size() * 2, '0', STR_PAD_LEFT);
    return $hex;
  }

  public function obscure($entropy1, $entropy2 = '', $split = 1)
  {
    $output = [];
    if (!empty($entropy2)) {
      $entropy_xored = $this->xor($entropy1, $entropy2);
    } else {
      $entropy_xored = $entropy1;
    }
    $output[] = $entropy_xored;
    for ($i = 1; $i < $split; $i++) {
      $entropy_random = $this->entropy();
      $output[$i - 1] = $entropy_random;
      $entropy_xored = $this->xor($entropy_xored, $entropy_random);
      $output[$i] = $entropy_xored;
    }
    return $output;
  }

  protected function wordlists($path)
  {
    $languages = $this->config('languages');
    $default_language = $this->config('default_language');
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
  const CLI_BOLD = "\033[1m";
  const CLI_NORMAL = "\033[0m";

  public function __construct()
  {
    parent::__construct();
    echo PHP_EOL;
    register_shutdown_function(function () {
      echo PHP_EOL;
    });
  }

  public function print($data = [], $heading = '')
  {
    if (!empty($heading)) {
      echo PHP_EOL . self::CLI_BOLD . $heading . self::CLI_NORMAL . PHP_EOL;
    }
    if (!empty($data)) {
      if (!empty($heading)) {
        echo PHP_EOL;
      }
      foreach ($data as $label => $value) {
        if (is_array($value)) {
          $this->print($value);
        } else {
          if (!is_numeric($label)) {
            echo $label . ': ';
          }
          echo self::CLI_BOLD . $value . self::CLI_NORMAL . PHP_EOL;
          if (!is_numeric($label) && strpos($label, 'Address') !== false) {
            echo str_replace('Address', 'Explorer', $label) . ': ';
            echo self::CLI_BOLD . sprintf($this->config('explorer'), $value) . self::CLI_NORMAL . PHP_EOL;
          }
        }
      }
    }
  }
}

class SeedConcealWeb extends SeedConceal
{
  public function qrcode($mnemonic)
  {
    $qrcode = new DNS2D();
    $qrcode->setStorPath(__DIR__ . '/cache/');
    $image = $qrcode->getBarcodePNG($mnemonic, 'QRCODE');
    return $image;
  }

  public function print($data = [], $heading = '')
  {
    if (!empty($heading)) {
      echo '<div class="sc-heading">' . htmlspecialchars($heading) . '</div>';
    }
    if (!empty($data)) {
      if (!empty($heading)) {
        echo '<dl class="sc-datalist">';
      }
      foreach ($data as $label => $value) {
        if (is_array($value)) {
          $this->print($value);
        } else {
          if (!is_numeric($label) && strpos($label, 'Address') !== false) {
            $value = '<a href="' . sprintf($this->config('explorer'), $value) .  '" target="_blank">' . $value . '</a>';
          }
          echo '<dt><strong>' . $label . '</strong></dt>';
          echo '<dd>' . $value . '</dd>';
        }
      }
      if (!empty($heading)) {
        echo '</dl>';
      }
    }
  }
}
