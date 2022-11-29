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
    <p><span class="icon-link"></span> <a href="https://github.com/rarioj/seedconceal">GitHub</a> &bull; <span class="icon-home"></span> <a href="/">Seed Conceal</a> &bull; <span class="icon-share"></span> Obscure</p>
  </header>
  <div class="container">
    <div class="row">
      <div class="col-sm-8">
        <div class="section">
          <h2>Mnemonic<?php if (count($details) > 1) echo 's'; ?></h2>
        </div>
        <div class="section rounded shadowed responsive-margin responsive-padding">
          <p><?php echo implode('<br /><br />', $mnemonics); ?></p>
        </div>
        <p>&nbsp;</p>
        <div class="section">
          <h2>Addresses</h2>
        </div>
        <?php
        foreach ($details as $index => $detail) {
          $sc->print($detail, 'Mnemonic  #' . ($index + 1));
        }
        ?>
      </div>
      <div class="col-sm-4">
        <div class="section">
          <h2>QR Card<?php if (count($details) > 1) echo 's'; ?></h2>
        </div>
        <div class="section responsive-margin responsive-padding" id="qrcodes"></div>
        <div id="capture_all" class="card fluid hidden" style="border: none;">
          <?php if (!empty($input_label)) { ?>
            <div class="section dark">
              <h3><?php echo htmlspecialchars($input_label); ?></h3>
            </div>
          <?php } ?>
          <img class="section" src="data:image/png;base64,<?php echo $sc->qrcode(implode(PHP_EOL, $mnemonics)); ?>" />
          <button class="inverse"><?php echo implode('<br /><br />', $mnemonics); ?></button>
        </div>
        <?php foreach ($details as $index => $detail) { ?>
          <div id="capture_<?php echo $index; ?>" class="card fluid hidden" style="border: none;">
            <?php if (!empty($input_label)) { ?>
              <div class="section dark">
                <h3><?php echo htmlspecialchars($input_label); ?><small>Part <?php echo ($index + 1) . "/" . count($details); ?></small></h3>
              </div>
            <?php } ?>
            <img class="section" src="data:image/png;base64,<?php echo $sc->qrcode($mnemonics[$index]); ?>" />
            <button class="inverse"><?php echo $mnemonics[$index]; ?></button>
          </div>
        <?php } ?>
      </div>
    </div>
  </div>
  <script type="text/javascript" src="/html2canvas.min.js"></script>
  <script type="text/javascript">
    html2canvas(document.querySelector('#capture_all'), {
      onclone: (cloned) => {
        cloned.getElementById('capture_all').classList.remove('hidden');
      }
    }).then((canvas) => {
      var image = new Image();
      image.src = canvas.toDataURL('image/png');
      document.getElementById('qrcodes').appendChild(image);
    });
    <?php foreach ($details as $index => $detail) { ?>
      html2canvas(document.querySelector('#capture_<?php echo $index; ?>'), {
        onclone: (cloned) => {
          cloned.getElementById('capture_<?php echo $index; ?>').classList.remove('hidden');
        }
      }).then((canvas) => {
        var image = new Image();
        image.src = canvas.toDataURL('image/png');
        image.style.marginTop = '25px';
        document.getElementById('qrcodes').appendChild(image);
      });
    <?php } ?>
  </script>
</body>

</html>