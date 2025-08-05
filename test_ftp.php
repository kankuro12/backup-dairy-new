<?php
require_once 'ftp_helper.php';

echo "Testing FTP Connection to BunnyCDN...\n";
echo "=====================================\n";

try {
    testFTPConnection();
    echo "\n✓ FTP connection test completed successfully!\n";
    echo "Your BunnyCDN storage is ready for backups.\n";
    
} catch (Exception $e) {
    echo "\n✗ FTP connection test failed: " . $e->getMessage() . "\n";
    echo "Please check your FTP credentials in the .env file.\n";
}

echo "\nFTP Configuration:\n";
echo "Host: " . ($env['FTP_HOST'] ?? 'Not set') . "\n";
echo "Port: " . ($env['FTP_PORT'] ?? 'Not set') . "\n";
echo "Username: " . ($env['FTP_USERNAME'] ?? 'Not set') . "\n";
echo "Password: " . (isset($env['FTP_PASSWORD']) ? str_repeat('*', strlen($env['FTP_PASSWORD'])) : 'Not set') . "\n";
?>