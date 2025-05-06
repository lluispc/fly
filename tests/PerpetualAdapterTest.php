<?php

namespace Tests;

use App\Adapter\Perpetual\PerpetualAdapter;
use League\Flysystem\Config;
use League\Flysystem\FilesystemAdapter;
use PHPUnit\Framework\TestCase;

class PerpetualAdapterTest extends TestCase
{
    /**
     * @var PerpetualAdapter
     */
    private $adapter;
    
    /**
     * @var string
     */
    private $tempDir;
    
    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/perpetual_test_' . uniqid();
        mkdir($this->tempDir, 0777, true);
        $this->adapter = new PerpetualAdapter($this->tempDir);
    }
    
    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }
    
    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }
        
        rmdir($path);
    }
    
    public function testImplementsFilesystemAdapter(): void
    {
        $this->assertInstanceOf(FilesystemAdapter::class, $this->adapter);
    }
    
    public function testWriteAndRead(): void
    {
        $this->adapter->write('test.txt', 'content', new Config());
        $this->assertEquals('content', $this->adapter->read('test.txt'));
    }
    
    public function testFileExists(): void
    {
        $this->adapter->write('test.txt', 'content', new Config());
        $this->assertTrue($this->adapter->fileExists('test.txt'));
        $this->assertFalse($this->adapter->fileExists('nonexistent.txt'));
    }
    
    public function testDirectoryExists(): void
    {
        $this->adapter->createDirectory('testdir', new Config());
        $this->assertTrue($this->adapter->directoryExists('testdir'));
        $this->assertFalse($this->adapter->directoryExists('nonexistent'));
    }
    
    public function testDelete(): void
    {
        $this->adapter->write('delete-me.txt', 'content', new Config());
        $this->assertTrue($this->adapter->fileExists('delete-me.txt'));
        
        $this->adapter->delete('delete-me.txt');
        $this->assertFalse($this->adapter->fileExists('delete-me.txt'));
    }
    
    public function testDeleteDirectory(): void
    {
        $this->adapter->createDirectory('delete-me-dir', new Config());
        $this->adapter->write('delete-me-dir/test.txt', 'content', new Config());
        
        $this->assertTrue($this->adapter->directoryExists('delete-me-dir'));
        
        $this->adapter->deleteDirectory('delete-me-dir');
        $this->assertFalse($this->adapter->directoryExists('delete-me-dir'));
    }
    
    public function testCopy(): void
    {
        $this->adapter->write('source.txt', 'content', new Config());
        $this->adapter->copy('source.txt', 'destination.txt', new Config());
        
        $this->assertTrue($this->adapter->fileExists('source.txt'));
        $this->assertTrue($this->adapter->fileExists('destination.txt'));
        $this->assertEquals('content', $this->adapter->read('destination.txt'));
    }
    
    public function testMove(): void
    {
        $this->adapter->write('source.txt', 'content', new Config());
        $this->adapter->move('source.txt', 'destination.txt', new Config());
        
        $this->assertFalse($this->adapter->fileExists('source.txt'));
        $this->assertTrue($this->adapter->fileExists('destination.txt'));
        $this->assertEquals('content', $this->adapter->read('destination.txt'));
    }
    
    public function testListContents(): void
    {
        $this->adapter->write('file1.txt', 'content', new Config());
        $this->adapter->createDirectory('directory', new Config());
        $this->adapter->write('directory/file2.txt', 'content', new Config());
        
        $contents = iterator_to_array($this->adapter->listContents('', true));
        
        $this->assertCount(3, $contents);
        
        $paths = array_map(function ($item) {
            return $item->path();
        }, $contents);
        
        $this->assertContains('file1.txt', $paths);
        $this->assertContains('directory', $paths);
        $this->assertContains('directory/file2.txt', $paths);
    }
} 