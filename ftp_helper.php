<?php
require_once 'env_loader.php';
$env = loadEnv(__DIR__. '/.env');

function createFTPConnection()
{
    global $env;
    
    // Get FTP settings from environment
    $ftpHost = $env['FTP_HOST'] ?? '';
    $ftpUsername = $env['FTP_USERNAME'] ?? '';
    $ftpPassword = $env['FTP_PASSWORD'] ?? '';
    $ftpPort = $env['FTP_PORT'] ?? 21;
    
    if (empty($ftpHost) || empty($ftpUsername) || empty($ftpPassword)) {
        throw new Exception("FTP credentials not found in .env file");
    }
    
    echo "Connecting to FTP server: $ftpHost:$ftpPort\n";
    
    // Create FTP connection
    $ftpConnection = ftp_connect($ftpHost, $ftpPort);
    if (!$ftpConnection) {
        throw new Exception("Could not connect to FTP server: $ftpHost:$ftpPort");
    }
    
    // Login to FTP server
    if (!ftp_login($ftpConnection, $ftpUsername, $ftpPassword)) {
        ftp_close($ftpConnection);
        throw new Exception("FTP login failed for user: $ftpUsername");
    }
    
    // Set passive mode (often required for data transfers)
    ftp_pasv($ftpConnection, true);
    
    echo "✓ Successfully connected and logged in to FTP server\n";
    
    return $ftpConnection;
}

function uploadFileToFTP($ftpConnection, $localFilePath, $remoteFileName = null)
{
    // Validate local file
    if (!file_exists($localFilePath)) {
        throw new Exception("Local file not found: $localFilePath");
    }
    
    if (!is_readable($localFilePath)) {
        throw new Exception("Local file is not readable: $localFilePath");
    }
    
    // Use original filename if remote name not specified
    if ($remoteFileName === null) {
        $remoteFileName = basename($localFilePath);
    }
    
    echo "Uploading $localFilePath as $remoteFileName...\n";
    
    // Upload file
    if (ftp_put($ftpConnection, $remoteFileName, $localFilePath, FTP_BINARY)) {
        echo "✓ File uploaded successfully: $remoteFileName\n";
        return true;
    } else {
        throw new Exception("FTP upload failed for: $localFilePath");
    }
}

function uploadToFTP($localFilePath, $remoteFileName = null)
{
    $ftpConnection = createFTPConnection();
    
    try {
        return uploadFileToFTP($ftpConnection, $localFilePath, $remoteFileName);
    } catch (Exception $e) {
        ftp_close($ftpConnection);
        throw $e;
    }
    
    ftp_close($ftpConnection);
}

function uploadMultipleToFTP($filePaths)
{
    if (empty($filePaths)) {
        echo "No files to upload\n";
        return;
    }
    
    $ftpConnection = createFTPConnection();
    
    try {
        $successCount = 0;
        $totalFiles = count($filePaths);
        
        echo "Uploading $totalFiles files using shared FTP connection...\n";
        
        foreach ($filePaths as $filePath) {
            try {
                uploadFileToFTP($ftpConnection, $filePath);
                $successCount++;
            } catch (Exception $e) {
                echo "✗ Failed to upload " . basename($filePath) . ": " . $e->getMessage() . "\n";
            }
        }
        
        echo "Upload completed: $successCount/$totalFiles files uploaded successfully\n";
        
    } catch (Exception $e) {
        ftp_close($ftpConnection);
        throw $e;
    }
    
    ftp_close($ftpConnection);
}

function testFTPConnection()
{
    global $env;
    
    $ftpHost = $env['FTP_HOST'] ?? '';
    $ftpUsername = $env['FTP_USERNAME'] ?? '';
    $ftpPassword = $env['FTP_PASSWORD'] ?? '';
    $ftpPort = $env['FTP_PORT'] ?? 21;
    
    echo "Testing FTP connection...\n";
    echo "Host: $ftpHost:$ftpPort\n";
    echo "Username: $ftpUsername\n";
    echo "Password: " . str_repeat('*', strlen($ftpPassword)) . "\n";
    
    $ftpConnection = ftp_connect($ftpHost, $ftpPort);
    if (!$ftpConnection) {
        throw new Exception("Could not connect to FTP server");
    }
    
    try {
        if (!ftp_login($ftpConnection, $ftpUsername, $ftpPassword)) {
            throw new Exception("FTP login failed");
        }
        
        echo "✓ FTP connection test successful!\n";
        return true;
        
    } catch (Exception $e) {
        ftp_close($ftpConnection);
        throw $e;
    }
    
    ftp_close($ftpConnection);
}
