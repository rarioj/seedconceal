<?php

declare(strict_types=1);
error_reporting(E_ALL ^ E_DEPRECATED);
set_time_limit(0);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../SeedConceal.php';

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
  $mnemonics[] = $sc->translate($detail[0], $input_language);
  $details[] = $detail;
}

?>
<!doctype html>
<html>

<head>
  <title>Seed Conceal - Obscure</title>
  <link rel="stylesheet" href="/mini.css">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta charset="utf-8">
</head>

<body>
  <header class="sticky" style="height: auto;">
    <h1><span class="icon-home"></span> <a href="/">Seed Conceal</a> &bull; Obscure</h1>
  </header>
  <input type="checkbox" id="modal-control" class="modal">
  <div>
    <div class="card" style="max-height: none; width: fit-content;">
      <label for="modal-control" class="modal-close"></label>
      <canvas></canvas>
    </div>
  </div>
  <div class="container">
    <div class="row">
      <div class="col-sm-9">
        <div class="section">
          <h2>Details</h2>
        </div>
        <?php
        foreach ($details as $index => $detail) {
          $sc->print($detail, 'Output  #' . ($index + 1));
        }
        ?>
      </div>
      <div class="col-sm-3">
        <div id="capture1" class="card fluid" style="border: none;">
          <?php if (!empty($input_label)) { ?>
            <div class="section dark">
              <h3><?php echo htmlspecialchars($input_label); ?></h3>
            </div>
          <?php } ?>
          <img style="cursor: pointer;" class="section" onclick="javascript: html2canvas(document.querySelector('#capture1')).then(canvas => { document.getElementsByTagName('canvas')[0].replaceWith(canvas); document.getElementById('modal-control').checked = true; });" src="data:image/png;base64,<?php echo $sc->qrcode(implode(PHP_EOL, $mnemonics)); ?>" />
          <?php foreach ($details as $index => $detail) { ?>
            <p style="cursor: pointer;" onclick="javascript: html2canvas(document.querySelector('#capture1_<?php echo $index; ?>'), { onclone: function(cloned) { cloned.getElementById('capture1_<?php echo $index; ?>').classList.remove('hidden'); }}).then(canvas => { document.getElementsByTagName('canvas')[0].replaceWith(canvas); document.getElementById('modal-control').checked = true; });"><?php echo $mnemonics[$index]; ?></p>
          <?php } ?>
        </div>
        <?php foreach ($details as $index => $detail) { ?>
          <div id="capture1_<?php echo $index; ?>" class="card fluid hidden" style="border: none;">
            <?php if (!empty($input_label)) { ?>
              <div class="section dark">
                <h3><?php echo htmlspecialchars($input_label); ?> &bull; <?php echo ($index + 1) . "/" . count($details); ?></h3>
              </div>
            <?php } ?>
            <img class="section" src="data:image/png;base64,<?php echo $sc->qrcode($mnemonics[$index]); ?>" />
            <p><?php echo $mnemonics[$index]; ?></p>
          </div>
        <?php } ?>
      </div>
    </div>
  </div>
  <script type="text/javascript" src="/html2canvas.min.js"></script>
</body>

</html>