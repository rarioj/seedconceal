<?php

declare(strict_types=1);
error_reporting(E_ALL ^ E_DEPRECATED);
set_time_limit(0);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/SeedConceal.php';

$sc = new SeedConcealWeb();
$default_hash_salt = $sc->config('default_hash_salt');
$default_hash_iteration = $sc->config('default_hash_iteration');
$default_split = $sc->config('default_split');
$languages = $sc->config('languages');
$random_language = $sc->config('random_language');
$default_language = $sc->config('default_language');

$input_label = filter_input(INPUT_POST, 'label', FILTER_DEFAULT);
$input_mnemonic = filter_input(INPUT_POST, 'mnemonic', FILTER_DEFAULT);
$input_password = filter_input(INPUT_POST, 'password', FILTER_DEFAULT);
$input_salt = filter_input(INPUT_POST, 'salt', FILTER_DEFAULT);
$input_iteration = (int) filter_input(INPUT_POST, 'iteration', FILTER_SANITIZE_NUMBER_INT);
$input_split = (int) filter_input(INPUT_POST, 'split', FILTER_SANITIZE_NUMBER_INT);
$input_language = filter_input(INPUT_POST, 'lang', FILTER_DEFAULT);

if (empty($input_mnemonic)) {
  die('[E] A seed phrase is required.');
}

$input_translated = $sc->parse([$input_mnemonic]);
if (empty($input_translated[0]['lang_id'])) {
  die('[E] Unable to determine the seed phrase language.');
}
if (empty($input_translated[0]['entropy'])) {
  die('[E] The input seed phrase is not valid.');
}

$translated = $input_translated[0];
$sc->size($translated['byte_size']);
if (!empty($input_password)) {
  $input_password = $sc->hash($input_password, $input_salt, $input_iteration);
}
$entropies = $sc->obscure($translated['entropy'], $input_password, $input_split);
$mnemonics = $details = [];
foreach ($entropies as $entropy) {
  $detail = $sc->details($entropy);
  $mnemonics[] = $sc->translate($detail['Seed Phrase'], $input_language);
  $details[] = $detail;
}

?>
<!doctype html>
<html>

<head>
  <meta charset="utf-8">
  <title>Seed Conceal - Obscure</title>
  <link rel="stylesheet" href="/style.css">
</head>

<body>
  <div class="sc-container sc-first">
    <div class="sc-inner">
      <?php
      foreach ($details as $index => $detail) {
        $sc->print($detail, 'Details  #' . ($index + 1));
      }
      ?>
    </div>
  </div>
  <div id="capture1" class="sc-container">
    <div class="sc-inner">
      <?php if (!empty($input_label)) { ?>
        <div class="sc-heading"><?php echo htmlspecialchars($input_label); ?></div>
      <?php } ?>
      <?php foreach ($details as $index => $detail) { ?>
        <p class="sc-click" onclick="javascript: html2canvas(document.querySelector('#capture1_<?php echo $index; ?>'), { onclone: function(cloned) { cloned.getElementById('capture1_<?php echo $index; ?>').style.display = 'block'; }}).then(canvas => { document.getElementsByTagName('canvas')[0].replaceWith(canvas) });"><?php echo $mnemonics[$index]; ?></p>
      <?php } ?>
    </div>
    <img src="data:image/png;base64,<?php echo $sc->qrcode(implode(PHP_EOL, $mnemonics)); ?>" class="sc-qrcode sc-click" onclick="javascript: html2canvas(document.querySelector('#capture1')).then(canvas => { document.getElementsByTagName('canvas')[0].replaceWith(canvas) });" />
  </div>
  <?php foreach ($details as $index => $detail) { ?>
    <div id="capture1_<?php echo $index; ?>" class="sc-container sc-hidden">
      <div class="sc-inner">
        <?php if (!empty($input_label)) { ?>
          <div class="sc-heading"><?php echo htmlspecialchars($input_label); ?> &bull; <?php echo ($index + 1) . "/" . count($details); ?></div>
        <?php } ?>
        <p><?php echo $mnemonics[$index]; ?></p>
      </div>
      <img src="data:image/png;base64,<?php echo $sc->qrcode($mnemonics[$index]); ?>" class="sc-qrcode" />
    </div>
  <?php } ?>
  <div class="sc-canvas">
    <canvas></canvas>
  </div>
  <div class="sc-footer">
    <p><a href="https://github.com/rarioj/seedconceal">GitHub</a> &bull; <a href="/">Seed Conceal</a> &bull; Obscure</p>
  </div>
  <script type="text/javascript" src="/html2canvas.min.js"></script>
</body>

</html>
