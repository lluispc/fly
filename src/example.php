<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Adapter\Perpetual\PerpetualAdapter;
use League\Flysystem\Filesystem;

// Create a storage directory for our example
$storagePath = __DIR__ . '/../storage/perpetual';

// Create the adapter instance
$adapter = new PerpetualAdapter($storagePath);

// Create the filesystem with our adapter
$filesystem = new Filesystem($adapter);

// Example operations

// Write a file
$filesystem->write('example.txt', 'Hello, this is a test file!');
echo "File written successfully.\n";

// Read a file
$content = $filesystem->read('example.txt');
echo "File content: " . $content . "\n";

// Check if file exists
$exists = $filesystem->fileExists('example.txt');
echo "File exists: " . ($exists ? 'Yes' : 'No') . "\n";

// Get file size
$size = $filesystem->fileSize('example.txt');
echo "File size: " . $size . " bytes\n";

// Create a directory
$filesystem->createDirectory('test-directory');
echo "Directory created successfully.\n";

// Write a file in the directory
$filesystem->write('test-directory/nested-file.txt', 'This is a nested file!');
echo "Nested file written successfully.\n";

// List contents
echo "Listing contents:\n";
$listing = $filesystem->listContents('', true);

foreach ($listing as $item) {
    $type = $item instanceof \League\Flysystem\FileAttributes ? 'File' : 'Directory';
    $path = $item->path();
    echo "- [$type] $path\n";
}

// Copy a file
$filesystem->copy('example.txt', 'example-copy.txt');
echo "File copied successfully.\n";

// Move a file
$filesystem->move('example-copy.txt', 'moved-file.txt');
echo "File moved successfully.\n";

// Delete a file
$filesystem->delete('moved-file.txt');
echo "File deleted successfully.\n";

// Clean up - delete the test directory and its contents
$filesystem->deleteDirectory('test-directory');
echo "Directory deleted successfully.\n";

echo "Example completed successfully!\n"; 