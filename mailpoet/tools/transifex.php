<?php declare(strict_types = 1);

// OS & architecture
$os = strtolower(php_uname('s'));
$unameM = php_uname('m');
if ($unameM === 'x86_64') {
  $arch = 'amd64';
} elseif (preg_match('/^(aarch|arm)/i', $unameM)) {
  $arch = 'arm64';
} elseif ($unameM === 'i386') {
  $arch = '386';
} else {
  throw new Exception('Unknown architecture');
}

// ensure vendor dir
$vendorDir = __DIR__ . '/vendor';
if (!file_exists($vendorDir)) {
  mkdir($vendorDir);
}

// paths
$name = "tx-$os-$arch";
$url = "https://github.com/transifex/cli/releases/download/v1.0.3/$name.tar.gz";
$filePath = __DIR__ . "/vendor/$name";
$fileInfoPath = "$filePath.info";

// download Transifex CLI if it doesn't exist
if (!file_exists($filePath) || !file_exists($fileInfoPath) || file_get_contents($fileInfoPath) !== $url) {
  fwrite(STDERR, "Downloading '$url'...");
  $resource = fopen($url, 'r');
  if ($resource === false) {
    throw new \RuntimeException("Could not connect to '$url'");
  }

  $archivePath = __DIR__ . '/vendor/' . basename($url);
  file_put_contents($archivePath, $resource);
  (new PharData($archivePath))->extractTo($filePath, ['tx'], true);
  file_put_contents($fileInfoPath, $url);
  chmod($filePath, 0755);
  unlink($archivePath);
  fwrite(STDERR, " done.\n");
}

// run Transifex CLI
$args = array_map(function ($arg) {
  return escapeshellarg($arg);
}, array_slice($argv, 1));

$result = null;
passthru(escapeshellcmd("$filePath/tx") . ' ' . implode(' ', $args), $result);
exit((int)$result);
