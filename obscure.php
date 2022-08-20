<?php

declare(strict_types=1);
error_reporting(E_ALL ^ E_DEPRECATED);

require_once __DIR__ . '/vendor/autoload.php';

use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Mnemonic\MnemonicFactory;
use Milon\Barcode\DNS2D;

$config = require_once __DIR__ . '/config.php';
$label = filter_input(INPUT_POST, 'label', FILTER_DEFAULT);
$mnemonic = filter_input(INPUT_POST, 'mnemonic', FILTER_DEFAULT);
$password = filter_input(INPUT_POST, 'password', FILTER_DEFAULT);
$split = (int) filter_input(INPUT_POST, 'split', FILTER_SANITIZE_NUMBER_INT);
$lang = filter_input(INPUT_POST, 'lang', FILTER_DEFAULT);

$random = new Random();
$logs = $final = $final_en = $images = $images_en = [];
$image = $image_en = null;
$error = false;

if (empty($mnemonic)) {
  $logs[] = '<strong>ERROR:</strong> Valid mnemonic is empty';
  $error = true;
}
if (empty($password)) {
  $logs[] = '<strong>ERROR:</strong> Password is empty';
  $error = true;
}
if (!empty($mnemonic) && !empty($password)) {
  $logs[] = 'Extracting mnemonic';
  $mnemonic_input = preg_split('/[\s]+/', $mnemonic);
  $mnemonic_input = array_filter($mnemonic_input);

  $logs[] = 'Populating dictionaries';
  $dictionaries = [];
  $dictionaries['en'] = explode(PHP_EOL, file_get_contents(__DIR__ . '/bip39/en.txt'));
  foreach ($config['languages'] as $lang_id => $lang_label) {
    if ($lang_id === 'en') {
      continue;
    }
    $wordlists = explode(PHP_EOL, file_get_contents(__DIR__ . '/bip39/' . $lang_id . '.txt'));
    $dictionaries[$lang_id] = array_combine($wordlists, $dictionaries['en']);
  }
  $dictionaries['en'] = array_combine($dictionaries['en'], $dictionaries['en']);

  $logs[] = 'Determining mnemonic language';
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
      $logs[] = 'Mnemonic language found: <em>' . $mnemonic_lang_label . '</em>';
      break;
    }
  }

  if (empty($mnemonic_words)) {
    $logs[] = '<strong>ERROR:</strong> Unable to determine mnemonic language';
    $error = true;
  } else {
    $available_sizes = array_flip($config['sizes']);
    $byte_size = $available_sizes[count($mnemonic_words)] * 2;

    $mnemonic_input = implode(' ', $mnemonic_words);
    $bip39 = MnemonicFactory::bip39();
    $entropy_mnemonic = $bip39->mnemonicToEntropy($mnemonic_input)->getHex();
    $logs[] = 'Mnemonic input hex: ' . $entropy_mnemonic;

    $entropy_password = bin2hex($password);
    $entropy_password = str_pad($entropy_password, $byte_size, $entropy_password, STR_PAD_LEFT);
    $entropy_password = substr(hash('sha256', $entropy_password), 0, $byte_size);
    $logs[] = 'Mnemonic password hex #1: ' . $entropy_password;

    $entropy_xored = gmp_strval(gmp_xor(gmp_init('0x' . $entropy_mnemonic), gmp_init('0x' . $entropy_password)), 16);
    $entropy_xored = str_pad($entropy_xored, $byte_size, '0', STR_PAD_LEFT);
    $logs[] = 'Mnemonic XOR hex #1: ' . $entropy_xored;

    $entropy_buffer = Buffer::hex($entropy_xored, $byte_size / 2);
    $final_en[0] = $bip39->entropyToMnemonic($entropy_buffer);

    for ($i = 1; $i < $split; $i++) {
      $entropy_password = substr(hash('sha256', $entropy_password), 0, $byte_size);
      $logs[] = 'Mnemonic password hex #' . ($i + 1) . ': ' . $entropy_password;
      $entropy_xored = gmp_strval(gmp_xor(gmp_init('0x' . $entropy_xored), gmp_init('0x' . $entropy_password)), 16);
      $entropy_xored = str_pad($entropy_xored, $byte_size, '0', STR_PAD_LEFT);
      $logs[] = 'Mnemonic XOR hex #' . ($i + 1) . ': ' . $entropy_xored;
      $entropy_buffer = Buffer::hex($entropy_xored, $byte_size / 2);
      $final_en[$i] = $bip39->entropyToMnemonic($entropy_buffer);
    }

    if (!empty($final_en)) {
      if ($lang !== 'en') {
        $logs[] = 'Translating mnemonic output';
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
          $logs[] = 'Mnemonic #' . ($split_mnemonic_index + 1) . ' translated into <em>' . $config['languages'][$mlang] . '</em>';
        }
      } else {
        $final = $final_en;
      }

      $logs[] = 'Generating QR code';
      $barcode = new DNS2D();
      $barcode->setStorPath(__DIR__ . '/cache/');
      $image = $barcode->getBarcodePNG(implode(PHP_EOL, $final), 'QRCODE');
      foreach ($final as $index => $final_mnemonic) {
        $images[$index] = $barcode->getBarcodePNG($final_mnemonic, 'QRCODE');
      }
      $image_en = $barcode->getBarcodePNG(implode(PHP_EOL, $final_en), 'QRCODE');
      foreach ($final_en as $index => $final_mnemonic) {
        $images_en[$index] = $barcode->getBarcodePNG($final_mnemonic, 'QRCODE');
      }

      $logs[] = 'All done!';
    }
  }
}

?>
<!doctype html>
<html>

<head>
  <meta charset="utf-8">
  <title>Seed Conceal - Obscure</title>
  <link rel="stylesheet" href="/asset/style.css">
</head>

<body>
  <?php if (!empty($final)) { ?>
    <div id="capture1" class="sc-container sc-first">
      <div class="sc-inner">
        <?php if (!empty($label)) { ?>
          <div class="sc-heading"><?php echo htmlspecialchars($label); ?></div>
        <?php } ?>
        <?php foreach ($final as $index => $final_mnemonic) { ?>
          <p class="sc-click" onclick="javascript: html2canvas(document.querySelector('#capture1_<?php echo $index; ?>'), { onclone: function(cloned) { cloned.getElementById('capture1_<?php echo $index; ?>').style.display = 'block'; }}).then(canvas => { document.getElementsByTagName('canvas')[0].replaceWith(canvas) });"><?php echo $final_mnemonic; ?></p>
        <?php } ?>
      </div>
      <img src="data:image/png;base64,<?php echo $image; ?>" class="sc-qrcode sc-click" onclick="javascript: html2canvas(document.querySelector('#capture1')).then(canvas => { document.getElementsByTagName('canvas')[0].replaceWith(canvas) });" />
    </div>
    <?php foreach ($final as $index => $final_mnemonic) { ?>
      <div id="capture1_<?php echo $index; ?>" class="sc-container sc-hidden">
        <div class="sc-inner">
          <?php if (!empty($label)) { ?>
            <div class="sc-heading"><?php echo htmlspecialchars($label); ?> &bull; <?php echo ($index + 1) . "/" . count($final); ?></div>
          <?php } ?>
          <p><?php echo $final_mnemonic; ?></p>
        </div>
        <img src="data:image/png;base64,<?php echo $images[$index]; ?>" class="sc-qrcode" />
      </div>
    <?php } ?>
    <?php if ($lang !== 'en') { ?>
      <div id="capture2" class="sc-container">
        <div class="sc-inner">
          <?php if (!empty($label)) { ?>
            <div class="sc-heading"><?php echo htmlspecialchars($label); ?> &bull; English</div>
          <?php } ?>
          <?php foreach ($final_en as $index => $final_mnemonic) { ?>
            <p class="sc-click" onclick="javascript: html2canvas(document.querySelector('#capture2_<?php echo $index; ?>'), { onclone: function(cloned) { cloned.getElementById('capture2_<?php echo $index; ?>').style.display = 'block'; }}).then(canvas => { document.getElementsByTagName('canvas')[0].replaceWith(canvas) });"><?php echo $final_mnemonic; ?></p>
          <?php } ?>
        </div>
        <img src="data:image/png;base64,<?php echo $image_en; ?>" class="sc-qrcode sc-click" onclick="javascript: html2canvas(document.querySelector('#capture2')).then(canvas => { document.getElementsByTagName('canvas')[0].replaceWith(canvas) });" />
      </div>
      <?php foreach ($final_en as $index => $final_mnemonic) { ?>
        <div id="capture2_<?php echo $index; ?>" class="sc-container sc-hidden">
          <div class="sc-inner">
            <?php if (!empty($label)) { ?>
              <div class="sc-heading"><?php echo htmlspecialchars($label); ?> &bull; English &bull; <?php echo ($index + 1) . "/" . count($final_en); ?></div>
            <?php } ?>
            <p><?php echo $final_mnemonic; ?></p>
          </div>
          <img src="data:image/png;base64,<?php echo $images_en[$index]; ?>" class="sc-qrcode" />
        </div>
      <?php } ?>
    <?php } ?>
    <?php if ($config['debug'] === true) { ?>
      <div class="sc-container">
        <div class="sc-inner">
          <div class="sc-heading">Debug</div>
          <div class="sc-log">
            <?php foreach ($logs as $log) { ?>
              <p><?php echo $log; ?></p>
            <?php } ?>
          </div>
        </div>
      </div>
    <?php } ?>
    <div class="sc-canvas">
      <canvas></canvas>
    </div>
  <?php } ?>
  <div class="sc-footer">
    <p><a href="https://github.com/rarioj">GitHub</a> &bull; <a href="/">Seed Conceal</a> &bull; Obscure</p>
  </div>
  <script type="text/javascript" src="/asset/html2canvas.min.js"></script>
</body>

</html>
