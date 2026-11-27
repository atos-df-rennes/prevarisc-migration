<?php

declare(strict_types=1);

namespace App\Platau;

use App\Enum\PieceJointeStatutEnum as Status;
use App\Utils\PathNormalizer;
use Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Exception\ExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

final readonly class ExportDocuments
{
    public function __construct(
        private Connection $db,
        private SyncplicityUpload $upload,
        private Filesystem $filesystem,
        private string $realDataPath,
        #[Autowire(env: 'PLATAU_ID_ACTEUR_APPELANT')] private string $actor,
    ) {
    }

    /**
     * @param callable(string): void $report
     *
     * @return array<int, array<string, int|string>> dépôts réussis, indexés par ID local dans l'ordre d'envoi
     */
    public function prepare(int $dossierId, callable $report, int $documentType): array
    {
        if (!$this->upload->configured()) {
            return [];
        }
        /** @var list<array{ID_PIECEJOINTE: int|string, NOM_PIECEJOINTE: string, EXTENSION_PIECEJOINTE: string}> $pieces */
        $pieces = $this->db->fetchAllAssociative(
            'SELECT p.ID_PIECEJOINTE, p.NOM_PIECEJOINTE, p.EXTENSION_PIECEJOINTE FROM piecejointe p
             JOIN dossierpj d ON d.ID_PIECEJOINTE = p.ID_PIECEJOINTE
             JOIN piecejointestatut s ON s.ID_PIECEJOINTESTATUT = p.ID_PIECEJOINTESTATUT
             WHERE d.ID_DOSSIER = ? AND s.NOM_STATUT = ? ORDER BY p.ID_PIECEJOINTE',
            [$dossierId, Status::EnAttenteEnvoi->value],
        );
        $prepared = [];
        foreach ($pieces as $piece) {
            $id = (int) $piece['ID_PIECEJOINTE'];
            $extension = $piece['EXTENSION_PIECEJOINTE'];
            $name = str_replace(['/', '\\'], '-', $piece['NOM_PIECEJOINTE']).'_'.$id.$extension;
            try {
                if ('' === trim($this->realDataPath) || preg_match('~[/\\\\\x00-\x1f\x7f]~', $name)) {
                    throw new PlatauException('Chemin de pièce incorrect.');
                }
                $path = PathNormalizer::join($this->realDataPath, 'uploads/pieces-jointes', $id.$extension);
                $contents = $this->filesystem->readFile($path);
                $document = $this->upload->upload($name, $contents) + [
                    'dtProduction' => new \DateTimeImmutable()->format('Y-m-d'),
                    'idActeurProducteur' => $this->actor,
                    'algoHash' => 'SHA-512',
                    'hash' => hash('sha512', $contents),
                    'nomTypeDocument' => $documentType,
                    'nomTypeProducteurDoc' => 1,
                ];
            } catch (\Exception $exception) {
                // Ne jamais persister une exception de transport ou de filesystem (URL, ticket, chemin).
                $message = match (true) {
                    $exception instanceof PlatauException => $exception->getMessage(),
                    $exception instanceof ExceptionInterface => 'Impossible de récupérer le contenu du fichier.',
                    default => 'Échec du dépôt Syncplicity.',
                };
                $this->status($id, Status::Erreur, $message);
                $report(sprintf('Pièce %d : %s', $id, $message));
                continue;
            }
            $this->status($id, Status::VerificationStatut);
            $prepared[$id] = $document;
        }

        return $prepared;
    }

    /**
     * @param array<int, array<string, mixed>> $prepared
     * @param array<int, mixed>                $remoteDocuments documents dans l'ordre du versement
     * @param callable(string): void           $report
     */
    public function accepted(array $prepared, array $remoteDocuments, callable $report): void
    {
        if (count($remoteDocuments) !== count($prepared) || !array_is_list($remoteDocuments)) {
            $report(sprintf('Versement accepté : appariement impossible (%d dépôt(s), %d document(s) retourné(s)). Aucun identifiant associé.', count($prepared), count($remoteDocuments)));

            return;
        }
        foreach (array_keys($prepared) as $position => $id) {
            $remote = $remoteDocuments[$position] ?? null;
            $documentId = is_array($remote) ? ($remote['idDocument'] ?? null) : null;
            if (!is_string($documentId) || '' === trim($documentId)) {
                $report(sprintf('Pièce %d : identifiant documentaire absent du retour de versement.', $id));
                continue;
            }
            $this->db->update('piecejointe', ['ID_PLATAU' => $documentId], ['ID_PIECEJOINTE' => $id]);
        }
    }

    /** @param array<int, array<string, mixed>> $prepared */
    public function failed(array $prepared): void
    {
        foreach (array_keys($prepared) as $id) {
            $this->status($id, Status::EnAttenteEnvoi);
        }
    }

    private function status(int $id, Status $status, ?string $message = null): void
    {
        $this->db->executeStatement(
            'UPDATE piecejointe SET ID_PIECEJOINTESTATUT = (SELECT ID_PIECEJOINTESTATUT FROM piecejointestatut WHERE NOM_STATUT = ?),
             MESSAGE_ERREUR = ? WHERE ID_PIECEJOINTE = ?',
            [$status->value, $message, $id],
        );
    }
}
