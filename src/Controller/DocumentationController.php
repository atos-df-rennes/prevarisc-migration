<?php

declare(strict_types=1);

namespace App\Controller;

use App\Enum\DocumentationSpace;
use App\Service\DocumentationBrowser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;

final class DocumentationController extends AbstractController
{
    public function __construct(private readonly DocumentationBrowser $documentationBrowser)
    {
    }

    #[Route(path: '/documentation', name: 'documentation_index', methods: ['GET'])]
    public function index(): RedirectResponse
    {
        return $this->redirectToRoute('documentation_document', [
            'space' => DocumentationSpace::Functional->value,
            'path' => 'INDEX.md',
        ]);
    }

    #[Route(
        path: '/documentation/{space}/{path}',
        name: 'documentation_document',
        requirements: ['path' => Requirement::CATCH_ALL],
        methods: ['GET'],
    )]
    public function document(string $space, string $path): Response
    {
        $documentationSpace = DocumentationSpace::tryFrom($space);
        if (null === $documentationSpace) {
            throw $this->createNotFoundException('Espace de documentation introuvable.');
        }

        if ($documentationSpace->requiresAdmin()) {
            $this->denyAccessUnlessGranted('admin');
        }

        try {
            $document = $this->documentationBrowser->getDocument($space, $path);
        } catch (\InvalidArgumentException $invalidArgumentException) {
            throw $this->createNotFoundException('Document de documentation introuvable.', $invalidArgumentException);
        }

        if ('html' === $document['format']) {
            return new Response($document['content'], Response::HTTP_OK, [
                'Content-Type' => 'text/html; charset=UTF-8',
                'Content-Security-Policy' => "sandbox allow-scripts; default-src 'none'; script-src 'self' 'unsafe-inline' https://code.jquery.com https://maxcdn.bootstrapcdn.com; style-src 'self' 'unsafe-inline' https://maxcdn.bootstrapcdn.com; font-src 'self' https://maxcdn.bootstrapcdn.com; img-src 'self' data:; connect-src 'none'; form-action 'none'; object-src 'none'; base-uri 'none'; frame-ancestors 'none'",
                'Referrer-Policy' => 'no-referrer',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        return $this->render('documentation/index.html.twig', [
            'document' => $document,
            'navigation' => $this->documentationBrowser->getNavigation($this->isGranted('admin')),
        ]);
    }
}
