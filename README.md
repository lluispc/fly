# Perpetual Filesystem Adapter

This is a custom adapter for Flysystem that implements the `FilesystemAdapter` interface.

## Requirements

- PHP 8.0 or higher
- Fileinfo extension (for MIME type detection)
- [league/flysystem](https://flysystem.thephpleague.com/) v3.x

## Installation

```bash
composer require league/flysystem
```

## Usage

```php
use App\Adapter\Perpetual\PerpetualAdapter;
use League\Flysystem\Filesystem;

// Path where files will be stored
$storagePath = '/path/to/storage/directory';

// Create an instance of the adapter
$adapter = new PerpetualAdapter($storagePath);

// Create the filesystem with our adapter
$filesystem = new Filesystem($adapter);

// Now you can use any operation available in Flysystem
$filesystem->write('file.txt', 'File content');
$content = $filesystem->read('file.txt');
```

## Features

The Perpetual adapter supports all basic file operations:

- Reading and writing files
- Stream handling for large files
- Creating, deleting, and listing directories
- Copying and moving files
- Metadata management (size, modification date, MIME type)
- Visibility control (public/private)

## Usage Example

You can find a complete usage example in the `src/example.php` file.

To run the example:

```bash
php src/example.php
```

## License

MIT 