<?php

/**
 * CLI build script:
 * - Updates the manifest with the given version number and today's date
 * - Minifies all JS files ending in .min.js
 * - Adds all files from the manifest (plus the manifest itself) to a zip file
 *
 * Usage:
 * - Add script to the plugin's directory
 * - Install the requirements (below)
 * - Update the consts to suit your needs
 * - call this php file and pass the version number. e.g. `php build.php 4.4.0`
 *
 * Requirements:
 * - PHP                (installed to the local OS, not just XAMPP etc)
 * - Node               (https://nodejs.org/en/download/)
 * - UglifyJS           (run `npm i -g uglify-js`)
 */

const BUILD_PATH_TEMPLATE = '%s%sreleases/plg_rc_gallery_v%s.zip';
const XML_FILE_NAME = 'rc_gallery.xml';

$newVersion = $argv[1];

/** @var SimpleXMLElement $xml */
$xml = simplexml_load_file('rc_gallery.xml');

// update the creation date to today
$newCreatedDate = (new DateTime())->format('d/m/Y');
$oldCreatedDate = $xml->creationDate->__toString();
$xml->creationDate = $newCreatedDate;

echo sprintf(
    "Date updated:\n    \e[0;31m%s\e[0m -> \e[0;32m%s\e[0m\n",
    $oldCreatedDate,
    $newCreatedDate
);

// update the version to that given
$oldVersion = $xml->version->__toString();

if ($oldVersion > $newVersion) {
    throw new Exception("New version is older than the version currently listed in the XML file");
}

$xml->version = $newVersion;

echo sprintf(
    "Version updated:\n    \e[0;31m%s\e[0m -> \e[0;32m%s\e[0m\n",
    $oldVersion,
    $newVersion
);

// save the XML
$xml->asXML(getcwd() . '/' . XML_FILE_NAME);

$outputPath = sprintf(
    BUILD_PATH_TEMPLATE,
    getcwd(),
    '/',
    $newVersion
);

$zipArchive = new ZipArchive();
$zipArchive->open($outputPath, ZipArchive::CREATE);

// add all the files specified in the manifest
foreach ($xml->files->filename as $filename) {
    addFileToArchive($filename, $zipArchive);
}

foreach ($xml->languages->language as $languageFileName) {
    addFileToArchive($languageFileName, $zipArchive);
}

// add the xml file too
$zipArchive->addFile(
    getcwd() . '/' . XML_FILE_NAME,
    XML_FILE_NAME
);

$zipArchive->close();

function addFileToArchive(string $filename, ZipArchive $zipArchive): void
{
    $filePath = getcwd() . '/' . $filename;

    // minify the *.min.js files
    if (preg_match('/\.min\.js$/', $filename)) {
        $oldSize = filesize($filePath);

        exec(sprintf(
            "uglifyjs %s -o %s",
            $filePath,
            $filePath
        ));

        clearstatcache();

        $newSize = filesize($filePath);

        echo sprintf(
            "Minified %s\n    \e[0;31m%d\e[0m -> \e[0;32m%d\e[0m\n",
            $filename,
            $oldSize,
            $newSize
        );
    }

    $zipArchive->addFile(
        $filePath,
        $filename
    );
}
