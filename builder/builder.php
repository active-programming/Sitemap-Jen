<?php
/**
 * MCS package builder
 */

// +++ functions +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
require 'helper.php';

// check
if (!extension_loaded('zip')) {
    out("The Zip php extension not installed!\n", 'red');
    exit;
}
if (!function_exists('simplexml_load_file')) {
    out("The SimpleXml php extension not installed!\n", 'red');
    exit;
}

// +++ defines +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
$version = getVersion();
$ver = str_replace('.', '', $version);
$myDir = dirname(__FILE__);
$compName = 'com_sitemapjen';
$siteDir = $myDir . '/../components/' . $compName;
$adminDir = $myDir . '/../administrator/components/' . $compName;
$copyDir = $myDir . '/src_copy/' . date('dmY_His') . (isset($argv[1]) ? '_' . $argv[1] : '');
$copySiteDir = $copyDir . '/site';
$copyAdminDir = $copyDir . '/admin';
$pkgDir = $myDir . '/package/';
$zipPackageFile = 'com_sitemapjen-v' . $ver . '-j25j3x.zip';


// +++ COPY SRC +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

if (!createDir($copyDir) || !copyDir($siteDir, $copySiteDir) || !copyDir($adminDir, $copyAdminDir)){
    exit;
}
createDir($copyDir);
clearDir($pkgDir);


// +++ PACKING FRONTEND +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
out("Scan frontend files ...\n", 'green');

// listing files
$siteFiles = glob($copySiteDir . '/*') + glob($copySiteDir . '/*.*');
// prepare files list
foreach ($siteFiles as $k => &$file) {
    $name = basename($file);
    if (is_dir($file)) {
        $file = ['tag' => 'folder', 'attr' => [], 'value' => $name]; // if directory
    } else {
        // if file
        $file = ['tag' => 'filename', 'attr' => [], 'value' => $name];
    }
}

// +++ BACKEND +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
out("Scan backend files ...\n", 'green');

// listing files
$adminFiles = glob($copyAdminDir.'/*') + glob($copyAdminDir.'/*.*');
// prepare files list
foreach ($adminFiles as $k => &$file) {
    $name = basename($file);
    if (is_dir($file)) {
        $file = ['tag' => 'folder', 'attr' => [], 'value' => $name]; // if directory
    } else {
        // if file
        $file = ['tag' => 'filename', 'attr' => [], 'value' => $name];
    }
}

// update the data in manifest file
$upd = updateManifest($myDir . '/install.xml', [
    'creationDate' => date('M Y'),
    'version' => $version,
    'files' => $siteFiles,
    'administration/files' => $adminFiles,
], $copyDir . '/install.xml');
$instContent = file_get_contents($copyDir . '/install.xml');
file_put_contents($copyDir . '/install.xml', str_replace('{version}', $version, $instContent));

// +++ PACKING TO PACKAGE +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
out("Packing package ...\n", 'green');

copy($myDir . '/license.txt', $copyDir . '/license.txt');
zipping($copyDir, $pkgDir . $zipPackageFile);

out("Done ", 'green');
out("({$zipPackageFile})\n", 'gray');
out("Building complete.\n", 'green');