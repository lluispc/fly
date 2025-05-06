<?php
/**
 * Example usage of the PerpetualAdapter with Autonomi integration
 * 
 * This file demonstrates how to:
 * 1. Initialize the adapter with support for Autonomi
 * 2. Perform basic file operations
 * 3. Perform directory operations using Autonomi
 */

// Attempt to load the autoloader from different possible locations
$possiblePaths = [
    __DIR__ . '/../../../../vendor/autoload.php',
    __DIR__ . '/../../../vendor/autoload.php',
    __DIR__ . '/../../vendor/autoload.php',
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/vendor/autoload.php',
];

$autoloaderLoaded = false;
foreach ($possiblePaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $autoloaderLoaded = true;
        break;
    }
}

if (!$autoloaderLoaded) {
    die("Error: Could not find the vendor/autoload.php file. Please run 'composer install' in the project's root directory.\n");
}

// Check that the necessary dependencies are installed
if (!class_exists('GuzzleHttp\Client')) {
    die("Error: The GuzzleHttp\\Client class was not found. Please run 'composer require guzzlehttp/guzzle:^7.0' to install the dependency.\n");
}

use App\Adapter\Perpetual\PerpetualAdapter;
use League\Flysystem\Filesystem;

// Configure paths
$basePath = __DIR__ . '/../../../../storage/perpetual';
// Ensure the base directory exists
if (!is_dir($basePath)) {
    mkdir($basePath, 0755, true);
}

$testDirectoryPath = 'test-directory';
$testFilePath = 'test-directory/example.txt';

// Create a test directory if it does not exist
if (!is_dir($basePath . '/' . $testDirectoryPath)) {
    mkdir($basePath . '/' . $testDirectoryPath, 0755, true);
}

// Function to run examples
function runExample($title, $callable) {
    echo "\n";
    echo "=== {$title} ===\n";
    try {
        $result = $callable();
        if ($result) {
            echo "Result: ";
            print_r($result);
        }
        echo "Completed successfully\n";
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        echo "In file: " . $e->getFile() . ", line " . $e->getLine() . "\n";
    } catch (Throwable $t) {
        echo "Critical error: " . $t->getMessage() . "\n";
        echo "In file: " . $t->getFile() . ", line " . $t->getLine() . "\n";
    }
}

// 1. Initialize adapter without Autonomi (local filesystem only)
runExample("Initializing standard adapter", function() use ($basePath) {
    $adapter = new PerpetualAdapter($basePath);
    $filesystem = new Filesystem($adapter);
    echo "Adapter initialized successfully (without Autonomi)\n";
    return null;
});

// 2. Basic file operations with the standard adapter
runExample("Basic file operations", function() use ($basePath, $testFilePath) {
    $adapter = new PerpetualAdapter($basePath);
    $filesystem = new Filesystem($adapter);
    
    // Write a file - use array for config instead of Config object
    $filesystem->write($testFilePath, "This is a test file for the Perpetual adapter.", []);
    echo "File created\n";
    
    // Read the file
    $content = $filesystem->read($testFilePath);
    echo "File content: {$content}\n";
    
    // Get metadata
    $size = $filesystem->fileSize($testFilePath);
    echo "File size: {$size['size']} bytes\n";
    
    return null;
});

echo "\n";
echo "=== Checking dependencies for Autonomi ===\n";
if (!class_exists('GuzzleHttp\Client')) {
    echo "Error: The GuzzleHttp\\Client class is not available. Autonomi functionalities will not work.\n";
    echo "Please run 'composer require guzzlehttp/guzzle:^7.0' to enable integration with Autonomi.\n";
    exit(1);
} else {
    echo "GuzzleHttp Client found - OK\n";
}

// 3. Initialize adapter with Autonomi support
runExample("Initializing adapter with Autonomi support", function() use ($basePath) {
    // Enable support for Autonomi
    $adapter = new PerpetualAdapter(
        $basePath,
        true,                     // Enable integration with Autonomi
        'http://localhost:8000'   // URL of the Autonomi API
    );
    
    echo "Adapter initialized successfully (with Autonomi support)\n";
    return null;
});

// 4. Upload a directory to Autonomi as private
runExample("Upload directory to Autonomi (private)", function() use ($basePath, $testDirectoryPath) {
    $adapter = new PerpetualAdapter($basePath, true);
    
    // The path is relative to the basePath
    $result = $adapter->uploadDirectoryToAutonomi($testDirectoryPath, false);
    return $result;
});

// 5. Upload a directory to Autonomi as public
runExample("Upload directory to Autonomi (public)", function() use ($basePath, $testDirectoryPath) {
    $adapter = new PerpetualAdapter($basePath, true);
    
    // The path is relative to the basePath
    $result = $adapter->uploadDirectoryToAutonomi($testDirectoryPath, true);
    return $result;
});

// 6. Download a directory from Autonomi (requires a valid data_map or public_address)
runExample("Download directory from Autonomi", function() use ($basePath) {
    $adapter = new PerpetualAdapter($basePath, true);
    
    // These values must be replaced with those obtained during upload
    $dataMap = '81a54669727374939400dc00202b70ccaa330dccac3acc8accbecce3ccd45cccb2cc8a7715ccf15d516d4869cca550cca3cca9cca664345d09cc8edc00200dccca424ccc93cca0cc9ecce3cc8017cc9c4403cca9ccf4ccc93eccaa562acce1cc9a28192a4145cca05bccdc1bcca1cc8a9401dc002062ccd5ccdc3809cce3201d18cc98ccfdccf43177ccc7ccdd1dccdb45cc8a2b0dcc9e30ccb9124bcc91620dccb3ccbfdc00203acca2cc8d2212ccf61751ccf033cce8cc9120cc814b6368ccf90dcce0ccbecc9d68144f067bcc9671061fcce7cc8a9402dc002051217c4dccdb07cccbccfdccf4cce943ccf7cc8125ccf6ccfb47ccdfcc9704511c18ccdc01ccc8ccb66457ccf0ccb8cce6dc0020ccbaccb8ccff797f5f6fccebccbc62cc99064bcc9811cc9a58ccc0ccd068025dccb82338650fccd02305ccdccc96cc8b';  // from a previous private upload
    $destinationPath = 'downloads/private';
    
    // Download a private directory
    $result = $adapter->downloadDirectoryFromAutonomi($destinationPath, $dataMap, null);
    return $result;
});

// 7. Download a public directory from Autonomi (requires a valid public_address)
runExample("Download public directory from Autonomi", function() use ($basePath) {
    $adapter = new PerpetualAdapter($basePath, true);
    
    // These values must be replaced with those obtained during upload
    $publicAddress = 'addb416c96d4e2ece95e7677aa971df1dfc8c53621d922cfcad92e1d217bc75f';  // from a previous public upload
    $destinationPath = 'downloads/public';
    
    // Download a public directory
    $result = $adapter->downloadDirectoryFromAutonomi($destinationPath, null, $publicAddress);
    return $result;
});

// 8. Get transaction history
runExample("Get transaction history", function() use ($basePath) {
    $adapter = new PerpetualAdapter($basePath, true);
    
    // Get all transactions
    $result = $adapter->getDirectoryTransactions();
    return $result;
});

// 9. Get usage statistics
runExample("Get usage statistics", function() use ($basePath) {
    $adapter = new PerpetualAdapter($basePath, true);
    
    // Get statistics for the last 30 days
    $result = $adapter->getDirectoryStats(30);
    return $result;
});

echo "\n";
echo "======================================\n";
echo "Examples completed!\n";
echo "Note: For download operations, make sure to replace 'REPLACE_WITH_REAL_DATA_MAP' \n";
echo "and 'REPLACE_WITH_REAL_PUBLIC_ADDRESS' with actual values obtained from previous upload operations.\n";
echo "======================================\n"; 