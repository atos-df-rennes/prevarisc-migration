<?php

declare(strict_types=1);

namespace App\Platau;

use App\Platau\Dto\DownloadedPiece;
use App\Platau\Dto\Piece;
use App\Utils\PathNormalizer;
use Doctrine\DBAL\Connection;
use Symfony\Component\Filesystem\Filesystem;

final readonly class PieceStorage
{
    public function __construct(private Connection $db, private Filesystem $filesystem, private string $realDataPath)
    {
    }

    public function dossierId(string $consultation): ?int
    {
        $id = $this->db->fetchOne('SELECT d.ID_DOSSIER FROM dossier d JOIN platauconsultation c ON d.ID_PLATAU = c.ID_PLATAU WHERE c.ID_PLATAU = ? LIMIT 1', [$consultation]);

        return is_numeric($id) ? (int) $id : null;
    }

    public function exists(int $dossierId, Piece $piece): bool
    {
        return false !== $this->db->fetchOne(
            'SELECT 1 FROM dossierpj l JOIN piecejointe p ON p.ID_PIECEJOINTE = l.ID_PIECEJOINTE
             WHERE l.ID_DOSSIER = ? AND ((p.ID_PLATAU = ? AND p.no_version = ?) OR p.NOM_PIECEJOINTE IN (?, ?)) LIMIT 1',
            [$dossierId, $piece->idPiece, $piece->noVersion, $piece->filename(), $piece->legacyFilename()],
        );
    }

    public function attach(int $dossierId, Piece $piece, DownloadedPiece $download): void
    {
        $createdFile = null;
        try {
            $this->db->transactional(function (Connection $db) use ($dossierId, $piece, $download, &$createdFile): void {
                // Sérialise les imports concurrents du même dossier, sans verrou pendant le téléchargement.
                if (false === $db->fetchOne('SELECT ID_DOSSIER FROM dossier WHERE ID_DOSSIER = ? FOR UPDATE', [$dossierId])) {
                    throw new ReceptionException('Dossier introuvable.');
                }
                if ($this->exists($dossierId, $piece)) {
                    return;
                }

                $now = new \DateTimeImmutable();
                $db->insert('piecejointe', [
                    'NOM_PIECEJOINTE' => $piece->filename(),
                    'EXTENSION_PIECEJOINTE' => $download->extension,
                    'DATE_PIECEJOINTE' => $now->format('Y-m-d'),
                    'DATE_NOTIFICATION' => $now->format('Y-m-d H:i:s'),
                    'ID_PLATAU' => $piece->idPiece,
                    'no_version' => $piece->noVersion,
                    'TYPE' => $piece->nomTypePiece?->libNom,
                    'SOUS_TYPE' => $piece->libAutreTypePiece,
                    'NATURE' => $piece->nomNaturePiece?->libNom,
                    'DATE_DEPOT' => $piece->dtDepot,
                ]);
                $id = $db->lastInsertId();
                $db->insert('dossierpj', ['ID_DOSSIER' => $dossierId, 'ID_PIECEJOINTE' => $id, 'PJ_COMMISSION' => 0]);

                if ('' === trim($this->realDataPath)) {
                    throw new ReceptionException('Répertoire de stockage incorrect.');
                }
                $directory = PathNormalizer::join($this->realDataPath, 'uploads/pieces-jointes');
                $this->filesystem->mkdir($directory);
                $path = PathNormalizer::join($directory, $id.$download->extension);
                $file = @fopen($path, 'xb');
                if (false === $file) {
                    throw new ReceptionException('Création du fichier impossible.');
                }
                $createdFile = $path;
                try {
                    $download->copyTo($file);
                } finally {
                    fclose($file);
                }
            });
        } catch (\Throwable) {
            if (null !== $createdFile) {
                $this->filesystem->remove($createdFile);
            }
            throw new ReceptionException('Échec du stockage de la pièce.');
        }
    }
}
