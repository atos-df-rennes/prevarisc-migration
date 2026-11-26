<?php

declare(strict_types=1);

namespace App\Platau;

use App\Platau\Dto\DownloadedPiece;
use App\Platau\Dto\Piece;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class PieceDownloader
{
    public function __construct(
        #[Autowire(service: 'platau.piece_transport')] private HttpClientInterface $transport,
        private PlatauHttp $http,
    ) {
    }

    public function download(#[\SensitiveParameter] Piece $piece): DownloadedPiece
    {
        $url = parse_url($piece->url);
        if (false === filter_var($piece->url, \FILTER_VALIDATE_URL) || !is_array($url)
            || 'https' !== strtolower($url['scheme'] ?? '') || empty($url['host'])
            || isset($url['user']) || isset($url['fragment']) || str_contains($piece->url, '\\')
            || !preg_match('#^[A-Za-z0-9._~+/-]+=*$#D', $piece->token)) {
            throw new ReceptionException('Paramètres de téléchargement incorrects.');
        }

        try {
            $response = $this->transport->request('GET', $piece->url, [
                'headers' => ['Authorization' => 'Bearer '.$piece->token],
                'max_redirects' => 0,
                'timeout' => 30,
                'buffer' => false,
                'extra' => ['trace_content' => false],
            ] + $this->http->options());
            $status = $response->getStatusCode();
            if (200 !== $status) {
                throw new ReceptionException(sprintf('Téléchargement refusé (HTTP %d).', $status));
            }

            $extension = $this->extension($response->getHeaders(false)['content-disposition'][0] ?? '', $piece->txFileExtension);
            $stream = tmpfile();
            if (false === $stream) {
                throw new ReceptionException('Stockage temporaire indisponible.');
            }

            $size = 0;
            foreach ($this->transport->stream($response) as $chunk) {
                $bytes = $chunk->getContent();
                if (fwrite($stream, $bytes) !== strlen($bytes)) {
                    throw new ReceptionException('Stockage temporaire insuffisant.');
                }
                $size += strlen($bytes);
            }

            return new DownloadedPiece($extension, $stream, $size);
        } catch (ReceptionException $exception) {
            throw $exception;
        } catch (\Throwable) {
            // Les exceptions du client HTTP peuvent contenir l'URL signée.
            throw new ReceptionException('Échec du téléchargement de la pièce.');
        }
    }

    private function extension(string $disposition, ?string $declaredExtension): string
    {
        if ('' === trim($disposition)) {
            $extension = $declaredExtension ?? '';

            return $this->safeExtension(str_starts_with($extension, '.') ? substr($extension, 1) : $extension);
        }

        $parameters = HeaderUtils::combine(HeaderUtils::split($disposition, ';='));
        $filename = $parameters['filename'] ?? null;
        if (isset($parameters['filename*'])) {
            if (!is_string($parameters['filename*']) || !preg_match("/^UTF-8'[^']*'(.*)$/iD", $parameters['filename*'], $matches)) {
                return '.bin';
            }
            $filename = rawurldecode($matches[1]);
        }
        if (!is_string($filename) || preg_match('~[/\\\\\x00-\x1f\x7f]~', $filename)) {
            return '.bin';
        }

        return $this->safeExtension(pathinfo($filename, PATHINFO_EXTENSION));
    }

    private function safeExtension(string $extension): string
    {
        // Le stockage peut être servi par Apache : ne jamais y publier un script.
        $extension = strtolower($extension);
        if (!in_array($extension, ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'tif', 'tiff', 'bmp', 'txt', 'csv', 'odt', 'ods', 'odp', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'rtf', 'dwg', 'dxf', 'ifc', 'zip'], true)) {
            return '.bin';
        }

        return '.'.$extension;
    }
}
