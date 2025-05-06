<?php

namespace App\Adapter\Perpetual;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\MultipartStream;

/**
 * Bridge for connecting PHP Perpetual adapter with Python Autonomi API
 */
class AutonomiBridge
{
    /**
     * @var Client HTTP client instance
     */
    private $httpClient;

    /**
     * @var string Base URL for the API
     */
    private $apiUrl;

    /**
     * Constructor
     * 
     * @param string $apiUrl Base URL for the Autonomi API (default: http://localhost:8000)
     */
    public function __construct(string $apiUrl = 'http://localhost:8000')
    {
        $this->apiUrl = rtrim($apiUrl, '/');
        $this->httpClient = new Client([
            'base_uri' => $this->apiUrl,
            'timeout' => 120.0,
        ]);
    }

    /**
     * Upload a directory to Autonomi network
     * 
     * @param string $directoryPath Local path to the directory
     * @param bool $isPublic Whether to make the directory publicly accessible
     * 
     * @return array Result of the operation containing status, cost and identifiers
     * @throws \Exception If there's an API error
     */
    public function uploadDirectory(string $directoryPath, bool $isPublic = false): array
    {
        try {
            if (!is_dir($directoryPath)) {
                throw new \Exception("Directory not found: {$directoryPath}");
            }

            $response = $this->httpClient->post('/dirs/upload', [
                'multipart' => [
                    [
                        'name' => 'directory_path',
                        'contents' => $directoryPath
                    ],
                    [
                        'name' => 'public',
                        'contents' => $isPublic ? 'true' : 'false'
                    ]
                ]
            ]);

            $result = json_decode($response->getBody()->getContents(), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Error decoding API response: ' . json_last_error_msg());
            }
            
            return $result;
        } catch (GuzzleException $e) {
            throw new \Exception('Error connecting to Autonomi API: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Download a directory from Autonomi network
     * 
     * @param string $destinationPath Local path where to save the directory
     * @param string|null $dataMap For private directories, the data map string
     * @param string|null $publicAddress For public directories, the public address
     * 
     * @return array Result of the operation
     * @throws \Exception If there's an API error
     */
    public function downloadDirectory(string $destinationPath, ?string $dataMap = null, ?string $publicAddress = null): array
    {
        try {
            if (!$dataMap && !$publicAddress) {
                throw new \Exception('Either dataMap or publicAddress must be provided');
            }

            $multipart = [
                [
                    'name' => 'destination_path',
                    'contents' => $destinationPath
                ]
            ];

            if ($dataMap) {
                $multipart[] = [
                    'name' => 'data_map',
                    'contents' => $dataMap
                ];
            }

            if ($publicAddress) {
                $multipart[] = [
                    'name' => 'public_address',
                    'contents' => $publicAddress
                ];
            }

            $response = $this->httpClient->post('/dirs/download', [
                'multipart' => $multipart
            ]);

            $result = json_decode($response->getBody()->getContents(), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Error decoding API response: ' . json_last_error_msg());
            }
            
            return $result;
        } catch (GuzzleException $e) {
            throw new \Exception('Error connecting to Autonomi API: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get list of directory operations/transactions
     * 
     * @param string|null $date Optional date filter in YYYY-MM-DD format
     * @param string|null $operationType Filter by operation type: 'upload' or 'download'
     * 
     * @return array List of directory operations
     * @throws \Exception If there's an API error
     */
    public function getDirectoryTransactions(?string $date = null, ?string $operationType = null): array
    {
        try {
            $query = [];
            
            if ($date) {
                $query['date'] = $date;
            }
            
            if ($operationType) {
                $query['operation_type'] = $operationType;
            }
            
            $response = $this->httpClient->get('/dirs/transactions', [
                'query' => $query
            ]);
            
            $result = json_decode($response->getBody()->getContents(), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Error decoding API response: ' . json_last_error_msg());
            }
            
            return $result;
        } catch (GuzzleException $e) {
            throw new \Exception('Error connecting to Autonomi API: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get statistics for directory operations
     * 
     * @param int $days Number of days to include in statistics (1-365)
     * 
     * @return array Statistics data
     * @throws \Exception If there's an API error
     */
    public function getDirectoryStats(int $days = 30): array
    {
        try {
            $response = $this->httpClient->get('/dirs/stats', [
                'query' => [
                    'days' => $days
                ]
            ]);
            
            $result = json_decode($response->getBody()->getContents(), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Error decoding API response: ' . json_last_error_msg());
            }
            
            return $result;
        } catch (GuzzleException $e) {
            throw new \Exception('Error connecting to Autonomi API: ' . $e->getMessage(), 0, $e);
        }
    }
} 