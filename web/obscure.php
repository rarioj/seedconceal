<?php

declare(strict_types=1);
error_reporting(E_ALL ^ E_DEPRECATED);
set_time_limit(0);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/SeedConceal.php';

$sc = new SeedConcealWeb();
$default_hash_salt = $sc->getConfig('default_hash_salt');
$default_hash_iteration = $sc->getConfig('default_hash_iteration');
$default_split = $sc->getConfig('default_split');
$languages = $sc->getConfig('languages');
$random_language = $sc->getConfig('random_language');
$default_language = $sc->getConfig('default_language');

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

$input_translated = $sc->parseMnemonicInput([$input_mnemonic]);
if (empty($input_translated[0]['lang_id'])) {
  die('[E] Unable to determine the seed phrase language.');
}
if (empty($input_translated[0]['private_key'])) {
  die('[E] The input seed phrase is not valid.');
}

$translated = $input_translated[0];
$sc->setSize($translated['byte_size']);
if (!empty($input_password)) {
  $input_password = $sc->hashText($input_password, $input_salt, $input_iteration);
}
$output_keys = $sc->obscureKeys($translated['private_key'], $input_password, $input_split);
$details = [];
foreach ($output_keys as $private_key) {
  $details[] = $sc->getKeyDetails($private_key, $input_language);
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
      <div class="sc-heading">Details</div>
      <?php
      $mnemonic_all = [];
      foreach ($details as $detail) {
        $mnemonic_all[] = $detail['seed_phrase'];
        $sc->printDetails($detail);
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
        <p class="sc-click" onclick="javascript: html2canvas(document.querySelector('#capture1_<?php echo $index; ?>'), { onclone: function(cloned) { cloned.getElementById('capture1_<?php echo $index; ?>').style.display = 'block'; }}).then(canvas => { document.getElementsByTagName('canvas')[0].replaceWith(canvas) });"><?php echo $detail['seed_phrase']; ?></p>
      <?php } ?>
    </div>
    <img src="data:image/png;base64,<?php echo $sc->getQrcode(implode(PHP_EOL, $mnemonic_all)); ?>" class="sc-qrcode sc-click" onclick="javascript: html2canvas(document.querySelector('#capture1')).then(canvas => { document.getElementsByTagName('canvas')[0].replaceWith(canvas) });" />
  </div>
  <?php foreach ($details as $index => $detail) { ?>
    <div id="capture1_<?php echo $index; ?>" class="sc-container sc-hidden">
      <div class="sc-inner">
        <?php if (!empty($input_label)) { ?>
          <div class="sc-heading"><?php echo htmlspecialchars($input_label); ?> &bull; <?php echo ($index + 1) . "/" . count($details); ?></div>
        <?php } ?>
        <p><?php echo $detail['seed_phrase']; ?></p>
      </div>
      <img src="data:image/png;base64,<?php echo $sc->getQrcode($detail['seed_phrase']); ?>" class="sc-qrcode" />
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
