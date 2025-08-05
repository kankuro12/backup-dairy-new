<?php
require 'vendor/autoload.php';
require_once 'ftp_helper.php';
require_once 'env_loader.php';
$env = loadEnv(__DIR__. '/.env');

$zipFileDir = __DIR__ . DIRECTORY_SEPARATOR . 'dumps';
if (!is_dir($zipFileDir)) {
    mkdir($zipFileDir, 0777, true);
}
$csvFile = 'db_list.csv';
$dateStr = date('Y_m_d');
$dumpDir = __DIR__ . DIRECTORY_SEPARATOR . 'dumps' . DIRECTORY_SEPARATOR . $dateStr;
if (!is_dir($dumpDir)) {
    mkdir($dumpDir, 0777, true);
}


$zipFile = __DIR__ . DIRECTORY_SEPARATOR . 'dumps' . DIRECTORY_SEPARATOR . "{$dateStr}.zip";
$dumpFiles = []; // Array to store all dump file paths

if (($handle = fopen($csvFile, "r")) !== FALSE) {
    $header = fgetcsv($handle); // skip header
    while (($data = fgetcsv($handle)) !== FALSE) {
        list($dbname, $user, $password) = $data;
        $dumpFile = $dumpDir . DIRECTORY_SEPARATOR . "{$dbname}.sql";

        // Dump database
        if (!empty($password)) {
            $cmd = "mysqldump -u{$user} -p{$password} {$dbname} > \"{$dumpFile}\"";
        } else {
            $cmd = "mysqldump -u{$user} {$dbname} > \"{$dumpFile}\"";
        }

        echo "Dumping database: $dbname\n";
        system($cmd, $retval);
        if ($retval !== 0) {
            echo "Failed to dump database: $dbname\n";
            continue;
        }

        // Add successful dump to array
        $dumpFiles[] = $dumpFile;
        echo "Successfully dumped: $dbname\n";
    }
    fclose($handle);

    // Create single zip file with all dumps
    if (!empty($dumpFiles)) {
        echo "Creating password-protected zip file with " . count($dumpFiles) . " database dumps...\n";
        
        // Generate or use a fixed password for zip file
        $zipPassword = $env['ZIP_PASSWORD'] ?? 'default_password';
        echo "Zip password: $zipPassword\n";
        
        $zip = new ZipArchive();
        if ($zip->open($zipFile, ZipArchive::CREATE) === TRUE) {
            // Set password for the entire archive
            $zip->setPassword($zipPassword);
            
            foreach ($dumpFiles as $dumpFile) {
                $zip->addFile($dumpFile, basename($dumpFile));
                // Encrypt each file individually
                $zip->setEncryptionName(basename($dumpFile), ZipArchive::EM_AES_256);
            }
            $zip->close();
            echo "Password-protected zip file created: $zipFile\n";
            
            foreach ($dumpFiles as $dumpFile) {
                unlink($dumpFile);
            }
            rmdir($dumpDir); // Remove the dump directory
            echo "Cleanup completed.\n";
        } else {
            echo "Failed to create zip file: $zipFile\n";
        }
    } else {
        echo "No databases were successfully dumped.\n";
    }
} else {
    echo "Could not open CSV file. Checking for existing zip files...\n";
    // Read all existing zip files and upload them using shared connection
}

$zipFiles = glob($zipFileDir . DIRECTORY_SEPARATOR . '*.zip');

if (!empty($zipFiles)) {
    echo "Found " . count($zipFiles) . " zip files to upload\n";
    
    try {
        uploadMultipleToFTP($zipFiles);
        echo "✓ Successfully uploaded all zip files via FTP\n";
        foreach ($zipFiles as $zipFile) {
            unlink($zipFile); // Clean up zip files after upload
        }
    } catch (Exception $e) {
        echo "✗ Failed to upload files via FTP: " . $e->getMessage() . "\n";
    }   
} else {
    echo "No zip files found to upload\n";
}