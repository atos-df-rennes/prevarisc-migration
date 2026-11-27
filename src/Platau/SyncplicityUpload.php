<?php

declare(strict_types=1);

namespace App\Platau;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class SyncplicityUpload
{
    public function __construct(
        #[Autowire(service: 'platau.syncplicity_tokens')] private PisteTokenProvider $tokens,
        #[Autowire(service: 'platau.piece_transport')] private HttpClientInterface $transport,
        private PlatauHttp $http,
        #[Autowire(env: 'SYNCPLICITY_URL')] private string $baseUrl,
    ) {
    }

    public function configured(): bool
    {
        return $this->tokens->configured();
    }

    /** @return array{fileId: string, folderId: string} */
    public function upload(string $filename, #[\SensitiveParameter] string $contents): array
    {
        $options = ['max_redirects' => 0, 'timeout' => 30, 'extra' => ['trace_content' => false]] + $this->http->options();
        for ($attempt = 0; $attempt < 2; ++$attempt) {
            $response = $this->transport->request('GET', rtrim($this->baseUrl, '/').'/pre-upload', [
                'auth_bearer' => $this->tokens->token(),
            ] + $options);
            if (401 !== $response->getStatusCode()) {
                break;
            }
            $this->tokens->invalidate();
        }
        $ticket = $this->http->json($response, 'Pré-dépôt Syncplicity');
        $url = $this->ticketField($ticket['Storage_URL'] ?? null);
        if (!filter_var($url, \FILTER_VALIDATE_URL) || 'https' !== parse_url($url, \PHP_URL_SCHEME)
            || null !== parse_url($url, \PHP_URL_USER) || null !== parse_url($url, \PHP_URL_QUERY)
            || null !== parse_url($url, \PHP_URL_FRAGMENT) || str_contains($url, '\\')) {
            throw new PlatauException('Adresse de stockage Syncplicity incorrecte.');
        }
        $folder = $this->identifier($ticket['VirtualFolderId'] ?? null);
        $session = $this->ticketField($ticket['Authorization_for_upload'] ?? null);
        $form = new FormDataPart([
            'fileData' => new DataPart($contents, $filename),
            'virtualFolderId' => $folder,
            'sha256' => hash('sha256', $contents),
            'sessionKey' => $session,
            'filename' => $filename,
        ]);
        $upload = $this->transport->request('POST', 'v2/mime/files', [
            'base_uri' => $url,
            'query' => ['filepath' => $this->ticketField($ticket['Folder_Name'] ?? null).'/'.urlencode($filename)],
            'headers' => [
                ...$form->getPreparedHeaders()->toArray(),
                'AppKey' => $this->ticketField($ticket['AppKey'] ?? null),
                'Authorization' => $session,
            ],
            'body' => $form->bodyToIterable(),
        ] + $options);
        $file = $this->http->json($upload, 'Dépôt Syncplicity');
        $this->identifier($file['data_file_version_id'] ?? null);

        return ['fileId' => $this->identifier($file['data_file_id'] ?? null), 'folderId' => $folder];
    }

    private function ticketField(#[\SensitiveParameter] mixed $value): string
    {
        if (!is_string($value) || '' === trim($value) || preg_match('/[\x00-\x1f\x7f]/', $value)) {
            throw new PlatauException('Ticket Syncplicity incorrect.');
        }

        return $value;
    }

    private function identifier(mixed $value): string
    {
        if (!is_int($value) && (!is_string($value) || '' === trim($value))) {
            throw new PlatauException('Identifiant manquant dans la réponse Syncplicity.');
        }

        return (string) $value;
    }
}
