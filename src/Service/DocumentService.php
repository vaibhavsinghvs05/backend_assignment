<?php

namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;
use Psr\Log\LoggerInterface;

class DocumentService
{
    private $httpClient;
    private $logger;
    private $storageDirectory;

    public function __construct(LoggerInterface $logger, string $storageDirectory)
    {
        $this->httpClient = HttpClient::create();
        $this->logger = $logger;
        $this->storageDirectory = $storageDirectory;

        if (!is_dir($this->storageDirectory)) {
            mkdir($this->storageDirectory, 0777, true);
        }
    }

    public function fetchAndStoreDocuments(string $apiUrl): array
    {
        $results = ['success' => [], 'failed' => []];

        try {
            $response = $this->httpClient->request('GET', $apiUrl);

            if ($response->getStatusCode() !== 200) {
                throw new \Exception('Failed to fetch documents. API returned: ' . $response->getStatusCode());
            }

            $documents = $response->toArray();

            foreach ($documents as $document) {
                try {
                    $this->processDocument($document);
                    $results['success'][] = $document['doc_no'];
                } catch (\Exception $e) {
                    $this->logger->error('Document processing error: ' . $e->getMessage());
                    $results['failed'][] = ['doc_no' => $document['doc_no'], 'error' => $e->getMessage()];
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Error fetching documents: ' . $e->getMessage());
        }

        return $results;
    }

    private function processDocument(array $document): void
    {
        if (!isset($document['certificate'], $document['doc_no'], $document['description'])) {
            throw new \InvalidArgumentException('Invalid document data. Missing required fields.');
        }

        $decodedContent = base64_decode($document['certificate']);
        if ($decodedContent === false) {
            throw new \RuntimeException('Failed to decode certificate data.');
        }

        $fileName = sprintf('%s_%s.pdf', $this->sanitize($document['description']), $document['doc_no']);
        $filePath = $this->storageDirectory . DIRECTORY_SEPARATOR . $fileName;

        if (file_put_contents($filePath, $decodedContent) === false) {
            throw new \RuntimeException('Failed to save file: ' . $filePath);
        }
    }

    private function sanitize(string $name): string
    {
        return preg_replace('/[^a-zA-Z0-9_-]/', '_', $name);
    }
}
