<?php

declare(strict_types=1);
error_reporting(E_ALL ^ E_DEPRECATED);
set_time_limit(0);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/SeedConceal.php';

$sc = new SeedConcealWeb();
$default_hash_salt = $sc->config('default_hash_salt');
$default_hash_iteration = $sc->config('default_hash_iteration');

$input_label = filter_input(INPUT_POST, 'label', FILTER_DEFAULT);
$input_mnemonic = filter_input(INPUT_POST, 'mnemonic', FILTER_DEFAULT);
$input_password = filter_input(INPUT_POST, 'password', FILTER_DEFAULT);
$input_salt = filter_input(INPUT_POST, 'salt', FILTER_DEFAULT);
$input_iteration = (int) filter_input(INPUT_POST, 'iteration', FILTER_SANITIZE_NUMBER_INT);

if (empty($input_mnemonic)) {
  die('[E] A seed phrase is required.');
}

$input_translated_all = $sc->parse(explode(PHP_EOL, $input_mnemonic));
$output_key = '';
foreach ($input_translated_all as $index => $input_translated) {
  if (empty($input_translated['lang_id'])) {
    die('[E] Unable to determine the seed phrase language.');
    exit;
  }
  if (empty($input_translated['entropy'])) {
    die('[E] The input seed phrase is not valid.');
    exit;
  }
  $sc->size($input_translated['byte_size']);
  if (empty($output_key)) {
    $output_key = $input_translated['entropy'];
  } else {
    $output_key = $sc->xor($output_key, $input_translated['entropy']);
  }
}

if (!empty($input_password)) {
  $input_password = $sc->hash($input_password, $input_salt, $input_iteration);
  $entropy = $sc->xor($output_key, $input_password);
} else {
  $entropy = $output_key;
}

$details = $sc->details($entropy);

?>
<!doctype html>
<html>

<head>
  <meta charset="utf-8">
  <title>Seed Conceal - Reveal</title>
  <link rel="stylesheet" href="/style.css">
</head>

<body>
  <div class="sc-container sc-first">
    <div class="sc-inner">
      <?php $sc->print($details, 'Details'); ?>
    </div>
  </div>
  <div id="capture1" class="sc-container">
    <div class="sc-inner">
      <?php if (!empty($input_label)) { ?>
        <div class="sc-heading"><?php echo htmlspecialchars($input_label); ?></div>
      <?php } ?>
      <p class="sc-click" onclick="javascript: html2canvas(document.querySelector('#capture1')).then(canvas => { document.getElementsByTagName('canvas')[0].replaceWith(canvas) });"><?php echo $details['Seed Phrase']; ?></p>
    </div>
    <img src="data:image/png;base64,<?php echo $sc->qrcode($details['Seed Phrase']); ?>" class="sc-qrcode sc-click" onclick="javascript: html2canvas(document.querySelector('#capture1')).then(canvas => { document.getElementsByTagName('canvas')[0].replaceWith(canvas) });" />
  </div>
  <div class="sc-canvas">
    <canvas></canvas>
  </div>
  <div class="sc-footer">
    <p><a href="https://github.com/rarioj/seedconceal">GitHub</a> &bull; <a href="/">Seed Conceal</a> &bull; Reveal</p>
  </div>
  <script type="text/javascript" src="/html2canvas.min.js"></script>
</body>

</html>
