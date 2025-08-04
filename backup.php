<?php
require 'vendor/autoload.php';
$csvFile = 'db_list.csv';

if (($handle = fopen($csvFile, "r")) !== FALSE) {
    $header = fgetcsv($handle); // skip header
    while (($data = fgetcsv($handle)) !== FALSE) {
        list($dbname, $user, $password) = $data;
        $dumpDir = __DIR__ . DIRECTORY_SEPARATOR . 'dumps';
        if (!is_dir($dumpDir)) {
            mkdir($dumpDir, 0777, true);
        }
        $dateStr = date('Y_m_d');
        $dumpFile = $dumpDir . DIRECTORY_SEPARATOR . "{$dbname}_{$dateStr}.sql";
        $zipFile = $dumpDir . DIRECTORY_SEPARATOR . "{$dbname}_{$dateStr}.zip";

        // Dump database
        if (!empty($password)) {
            $cmd = "mysqldump -u{$user} -p{$password} {$dbname} > \"{$dumpFile}\"";
        } else {
            $cmd = "mysqldump -u{$user} {$dbname} > \"{$dumpFile}\"";
        }
        system($cmd, $retval);
        if ($retval !== 0) {
            echo "Failed to dump database: $dbname\n";
            continue;
        }

        // Zip the dump
        $zip = new ZipArchive();
        if ($zip->open($zipFile, ZipArchive::CREATE) === TRUE) {
            $zip->addFile($dumpFile, basename($dumpFile));
            $zip->close();
        } else {
            echo "Failed to create zip for: $dumpFile\n";
            continue;
        }

        // Upload to Google Drive
        require_once 'google_drive_helper.php';
        try {
            $fileId = uploadToGoogleDrive($zipFile, 'application/zip');
            echo "Uploaded $zipFile as archive to Google Drive. File ID: $fileId\n";
        } catch (Exception $e) {
            echo "Google Drive upload error: " . $e->getMessage() . "\n";
        }

        // Clean up
        unlink($dumpFile);
        unlink($zipFile);
    }
    fclose($handle);
} else {
    echo "Could not open CSV file.\n";
}
?>
