<?php
require_once 'vendor/autoload.php';
require_once 'env_loader.php';
$env = loadEnv(__DIR__. '/.env');
$folderToUpload = $env['folder'] ?? '';
use Google\Client as Google_Client;
use Google\Service\Drive as Google_Service_Drive;
use Google\Service\Drive\DriveFile as Google_Service_Drive_DriveFile;

function getGoogleClient()
{
    $client = new Google_Client();
    $client->setApplicationName('DB Backup Upload');
    $client->setScopes(Google_Service_Drive::DRIVE_FILE);
    $client->setAuthConfig(__DIR__ . '/credentials.json');
    $client->setAccessType('offline');
    $client->setSubject(null); // Not impersonating any user
    return $client;
}

function uploadToGoogleDrive($filePath, $mimeType = 'application/zip')
{
    global $folderToUpload;
    $client = getGoogleClient();
    $service = new Google_Service_Drive($client);
    $file = new Google_Service_Drive_DriveFile();
    $file->setName(basename($filePath));
    if (!empty($folderToUpload)) {
        $file->setParents([$folderToUpload]);
    }
    $result = $service->files->create($file, [
        'data' => file_get_contents($filePath),
        'mimeType' => $mimeType,
        'uploadType' => 'multipart'
    ]);
    return $result->id;
}
