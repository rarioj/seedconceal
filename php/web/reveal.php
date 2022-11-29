<?php

declare(strict_types=1);
error_reporting(E_ALL ^ E_DEPRECATED);
set_time_limit(0);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../SeedConceal.php';

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
  <title>Seed Conceal - Reveal</title>
  <link rel="stylesheet" href="/mini.css">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta charset="utf-8">
</head>

<body>
  <header class="sticky" style="height: auto;">
    <p><span class="icon-link"></span> <a href="https://github.com/rarioj/seedconceal">GitHub</a> &bull; <span class="icon-home"></span> <a href="/">Seed Conceal</a> &bull; <span class="icon-search"></span> Reveal</p>
  </header>
  <div class="container">
    <div class="row">
      <div class="col-sm-8">
        <div class="section">
          <h2>Mnemonic</h2>
        </div>
        <div class="section rounded shadowed responsive-margin responsive-padding">
          <p><?php echo $details[0]; ?></p>
        </div>
        <p>&nbsp;</p>
        <div class="section">
          <h2>Addresses</h2>
        </div>
        <?php $sc->print($details); ?>
      </div>
      <div class="col-sm-4">
        <div class="section">
          <h2>QR Card</h2>
        </div>
        <div class="section responsive-margin responsive-padding" id="qrcodes"></div>
        <div id="capture" class="card fluid hidden" style="border: none;">
          <?php if (!empty($input_label)) { ?>
            <div class="section dark">
              <h3><?php echo htmlspecialchars($input_label); ?></h3>
            </div>
          <?php } ?>
          <img class="section" src="data:image/png;base64,<?php echo $sc->qrcode($details[0]); ?>" />
          <button class="inverse"><?php echo $details[0]; ?></button>
        </div>
      </div>
    </div>
  </div>
  <script type="text/javascript" src="/html2canvas.min.js"></script>
  <script type="text/javascript">
    html2canvas(document.querySelector('#capture'), {
      onclone: (cloned) => {
        cloned.getElementById('capture').classList.remove('hidden');
      }
    }).then((canvas) => {
      var image = new Image();
      image.src = canvas.toDataURL('image/png');
      document.getElementById('qrcodes').appendChild(image);
    });
  </script>
</body>

</html>