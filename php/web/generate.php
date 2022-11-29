<?php

declare(strict_types=1);
error_reporting(E_ALL ^ E_DEPRECATED);
set_time_limit(0);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../SeedConceal.php';

$sc = new SeedConcealWeb();
$sizes = $sc->config('sizes');
$default_size = $sc->config('default_size');
$default_hash_salt = $sc->config('default_hash_salt');
$default_hash_iteration = $sc->config('default_hash_iteration');

$input_label = filter_input(INPUT_POST, 'label', FILTER_DEFAULT);
$input_passphrase = filter_input(INPUT_POST, 'passphrase', FILTER_DEFAULT);
$input_salt = filter_input(INPUT_POST, 'salt', FILTER_DEFAULT);
$input_iteration = (int) filter_input(INPUT_POST, 'iteration', FILTER_SANITIZE_NUMBER_INT);
$input_password = filter_input(INPUT_POST, 'password', FILTER_DEFAULT);
$input_size = (int) filter_input(INPUT_POST, 'size', FILTER_SANITIZE_NUMBER_INT);

$sc->size($input_size);
$entropy = $sc->entropy();
if (!empty($input_passphrase)) {
  $input_size = $default_size;
  $input_passphrase = $sc->hash($input_passphrase, $input_salt, $input_iteration);
  if (!empty($input_password)) {
    $input_password = $sc->hash($input_password, $input_salt, $input_iteration);
    $entropy = $sc->xor($input_passphrase, $input_password);
  } else {
    $entropy = $input_passphrase;
  }
}
$details = $sc->details($entropy);

?>
<!doctype html>
<html>

<head>
  <title>Seed Conceal - Generate</title>
  <link rel="stylesheet" href="/mini.css">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta charset="utf-8">
</head>

<body>
  <header class="sticky" style="height: auto;">
    <p><span class="icon-link"></span> <a href="https://github.com/rarioj/seedconceal">GitHub</a> &bull; <span class="icon-home"></span> <a href="/">Seed Conceal</a> &bull; <span class="icon-info"></span> Generate</p>
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