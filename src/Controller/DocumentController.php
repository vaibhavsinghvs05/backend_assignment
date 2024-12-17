<?php

namespace App\Controller;

use App\Service\DocumentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class DocumentController extends AbstractController
{
    private $documentService;

    public function __construct(DocumentService $documentService)
    {
        $this->documentService = $documentService;
    }

    #[Route('/fetch-documents', name: 'fetch_documents', methods: ['GET'])]
    public function fetchDocuments(): JsonResponse
    {
        $apiUrl = 'https://raw.githubusercontent.com/RashitKhamidullin/Educhain-Assignment/refs/heads/main/get-documents'; 

        $results = $this->documentService->fetchAndStoreDocuments($apiUrl);

        return $this->json([
            'message' => 'Document processing completed.',
            'results' => $results,
        ]);
    }
}
