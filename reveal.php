<?php

declare(strict_types=1);
error_reporting(E_ALL ^ E_DEPRECATED);

require_once __DIR__ . '/vendor/autoload.php';

use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Mnemonic\MnemonicFactory;
use Milon\Barcode\DNS2D;

$config = require_once __DIR__ . '/config.php';
$label = filter_input(INPUT_POST, 'label', FILTER_DEFAULT);
$mnemonic = filter_input(INPUT_POST, 'mnemonic', FILTER_DEFAULT);
$password = filter_input(INPUT_POST, 'password', FILTER_DEFAULT);

$bip39 = MnemonicFactory::bip39();
$final_en = '';
$image_en = null;
$error = false;

if (empty($mnemonic)) {
  $logs[] = '<strong>ERROR:</strong> Obscured mnemonic is empty';
  $error = true;
}
if (empty($password)) {
  $logs[] = '<strong>ERROR:</strong> Password is empty';
  $error = true;
}
if (!empty($mnemonic) && !empty($password)) {
  $logs[] = 'Extracting obscured mnemonic';
  $mnemonic_lines = explode(PHP_EOL, $mnemonic);
  $mnemonic_lines = array_filter($mnemonic_lines);
  $mnemonic_input_all = [];
  foreach ($mnemonic_lines as $mnemonic_line) {
    $mnemonic_temp = preg_split('/[\s]+/', $mnemonic_line);
    $mnemonic_temp = array_filter($mnemonic_temp);
    $mnemonic_input_all[] = $mnemonic_temp;
  }
  $mnemonic_input_all = array_filter($mnemonic_input_all);
  $mnemonic_input_all = array_values($mnemonic_input_all);

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
        $logs[] = 'Mnemonic language #' . ($index + 1) . ' found: <em>' . $mnemonic_lang_label[$index] . '</em>';
        break;
      }
    }
  }

  if (empty($mnemonic_words)) {
    $logs[] = '<strong>ERROR:</strong> Unable to determine mnemonic language';
    $error = true;
  } else {
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
      $logs[] = 'Mnemonic password hex #' . ($index + 1) . ': ' . $entropy_passwords[$index];
      $entropy_xored = gmp_strval(gmp_xor(gmp_init('0x' . $entropy_mnemonic), gmp_init('0x' . $entropy_passwords[$index])), 16);
      $entropy_xored = str_pad($entropy_xored, $byte_size, '0', STR_PAD_LEFT);
      $logs[] = 'Mnemonic XOR hex #' . ($index + 1) . ': ' . $entropy_xored;
    }

    $entropy_buffer = Buffer::hex($entropy_xored, $byte_size / 2);
    $final_en = $bip39->entropyToMnemonic($entropy_buffer);

    if (!empty($final_en)) {
      $logs[] = 'Generating QR code';
      $barcode = new DNS2D();
      $barcode->setStorPath(__DIR__ . '/cache/');
      $image_en = $barcode->getBarcodePNG($final_en, 'QRCODE');
    }

    $logs[] = 'All done!';
  }
}

?>
<!doctype html>
<html>

<head>
  <meta charset="utf-8">
  <title>Seed Conceal - Reveal</title>
  <link rel="stylesheet" href="/asset/style.css">
</head>

<body>
  <div id="capture1" class="sc-container sc-first">
    <div class="sc-inner">
      <?php if (!empty($label)) { ?>
        <div class="sc-heading"><?php echo htmlspecialchars($label); ?></div>
      <?php } ?>
      <p class="sc-click" onclick="javascript: html2canvas(document.querySelector('#capture1')).then(canvas => { document.getElementsByTagName('canvas')[0].replaceWith(canvas) });"><?php echo $final_en; ?></p>
    </div>
    <img src="data:image/png;base64,<?php echo $image_en; ?>" class="sc-qrcode sc-click" onclick="javascript: html2canvas(document.querySelector('#capture1')).then(canvas => { document.getElementsByTagName('canvas')[0].replaceWith(canvas) });" />
  </div>
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
  <div class="sc-footer">
    <p><a href="https://github.com/rarioj">GitHub</a> &bull; <a href="/">Seed Conceal</a> &bull; Reveal</p>
  </div>
  <script type="text/javascript" src="/asset/html2canvas.min.js"></script>
</body>

</html>
