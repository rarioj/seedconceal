<?php
$languages = ['en', 'cs', 'es', 'fr', 'it', 'ja', 'ko', 'pt', 'zh_cn', 'zh_tw'];

echo '[' . PHP_EOL;
foreach ($languages as $language) {
  $wordlists = explode(PHP_EOL, file_get_contents(__DIR__ . '/' . $language . '.txt'));
  $wordlists = array_filter($wordlists);
  echo '\'' . $language .  '\' => [\'' . implode('\', \'', $wordlists) . '\'],' . PHP_EOL;
}
echo '];' . PHP_EOL;
