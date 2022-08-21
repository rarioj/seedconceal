<?php

declare(strict_types=1);
error_reporting(E_ALL ^ E_DEPRECATED);

require_once __DIR__ . '/../vendor/autoload.php';

use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Mnemonic\MnemonicFactory;
use Milon\Barcode\DNS2D;

$config = require_once __DIR__ . '/../config.php';
$label = filter_input(INPUT_POST, 'label', FILTER_DEFAULT);
$size = (int) filter_input(INPUT_POST, 'size', FILTER_SANITIZE_NUMBER_INT);
$logs = [];
$error = false;

$random = new Random();
$bip39 = MnemonicFactory::bip39();

$logs[] = 'Generating mnemonic';
$entropy = $random->bytes($size);
$mnemonic = $bip39->entropyToMnemonic($entropy);

$mnemonic_hex = $entropy->getHex();
$mnemonic_en = $mnemonic;
$logs[] = 'Mnemonic hex: ' . $mnemonic_hex;

$barcode = new DNS2D();
$barcode->setStorPath(__DIR__ . '/../cache/');

$logs[] = 'Generating QR code';
$image = $barcode->getBarcodePNG($mnemonic, 'QRCODE');

$logs[] = 'All done!';

?>
<!doctype html>
<html>

<head>
  <meta charset="utf-8">
  <title>Seed Conceal - Generate</title>
  <link rel="stylesheet" href="/style.css">
</head>

<body>
  <div id="capture1" class="sc-container sc-first">
    <div class="sc-inner">
      <?php if (!empty($label)) { ?>
        <div class="sc-heading"><?php echo htmlspecialchars($label); ?></div>
      <?php } ?>
      <p class="sc-click" onclick="javascript: html2canvas(document.querySelector('#capture1')).then(canvas => { document.getElementsByTagName('canvas')[0].replaceWith(canvas) });"><?php echo $mnemonic; ?></p>
    </div>
    <img src="data:image/png;base64,<?php echo $image; ?>" class="sc-qrcode sc-click" onclick="javascript: html2canvas(document.querySelector('#capture1')).then(canvas => { document.getElementsByTagName('canvas')[0].replaceWith(canvas) });" />
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
    <p><a href="https://github.com/rarioj/seedconceal">GitHub</a> &bull; <a href="/">Seed Conceal</a> &bull; Generate</p>
  </div>
  <script type="text/javascript" src="/html2canvas.min.js"></script>
</body>

</html>
