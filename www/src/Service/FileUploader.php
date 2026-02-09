<?php

namespace App\Service;


use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileUploader
{
    public function __construct(private readonly string $targetDirectory, private readonly SluggerInterface $slugger)
    {
    }


    /**
     * Upload un fichier et retourne le nom unique du fichier
     * @params UploadedFile $file
     * @params string|null $subdirectory - sous dossier
     * @return string le nom du fichier uplodé
     * @throws FileException
     */
    public function upload(UploadedFile $file, ?string $subdirectory = null): string
    {
        $allowedMimeTypes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/svg+xml',
            'image/webp',
            'image/avif'
        ];

        if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
            throw new FileException(
                sprintf(
                    "Type de fichier non autorisé. Type accpecter: %s",
                    implode(", ", $allowedMimeTypes)
                )
            );
        }

        //validation de la taille(5MB max)
        $maxSize = 5 * 1024 * 1024;
        if ($file->getSize() > $maxSize) {
            throw new FileException(
                "Le fichier est trop volumineux. Taille maximale: 5MB"
            );
        }


        //géneration dun nom unique
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFileName = $this->slugger->slug($originalFilename);
        $extension = $file->guessExtension() ?: $file->getClientOriginalExtension();
        $newFilename = $safeFileName . '-' . uniqid() . '.' . $extension;

        //determination du chemin de destination
        $uploadPath = $this->targetDirectory;
        if ($subdirectory) {
            $uploadPath = $this->targetDirectory . '/' . $subdirectory;
        }

        // création du dossier si il existe pas
        if (!is_dir($uploadPath)) {
            if (!mkdir($uploadPath, 0777, true) && !is_dir($uploadPath)) {
                throw new FileException("impossible de crée le dossier d'uploads : " . $uploadPath);
            }

        }

        if (!is_writable($uploadPath)) {
            throw new FileException("le dossier d'uploads n'est pas accésible en écriture : " . $uploadPath);
        }

        // on déplace le fichier physique dans sa cible
        try {
            $file->move($uploadPath, $newFilename);
        } catch (FileException $e) {
            throw new FileException("Erreur lors du déplacement du fichier: " . $e->getMessage());
        }

        // retourner le chemin avec le préfixe /uploads/
        if ($subdirectory) {
            return '/uploads/' . $subdirectory . '/' . $newFilename;
        }
        return '/uploads/' . $newFilename;
    }

    /**
     * Supprime un fichier
     *
     * @param string $filename Nom du fichier ou chemin relatif (peut commencer par /uploads/)
     * @return bool True si le fichier a été supprimé, false sinon
     */
    public function delete(string $filename): bool
    {
        // Retirer le préfixe /uploads/ si présent
        $relativePath = $this->getRelativePath($filename);
        $filePath = $this->targetDirectory . '/' . $relativePath;

        if (file_exists($filePath)) {
            return unlink($filePath);
        }

        return false;
    }

    /**
     * Retourne le chemin complet du fichier
     *
     * @param string $filename Nom du fichier ou chemin relatif (peut commencer par /uploads/)
     * @return string Chemin complet
     */
    public function getFilePath(string $filename): string
    {
        // Retirer le préfixe /uploads/ si présent
        $relativePath = $this->getRelativePath($filename);
        return $this->targetDirectory . '/' . $relativePath;
    }

    /**
     * Vérifie si un fichier existe
     *
     * @param string $filename Nom du fichier ou chemin relatif (peut commencer par /uploads/)
     * @return bool
     */
    public function fileExists(string $filename): bool
    {
        // Retirer le préfixe /uploads/ si présent
        $relativePath = $this->getRelativePath($filename);
        return file_exists($this->targetDirectory . '/' . $relativePath);
    }

    /**
     * Retire le préfixe /uploads/ d'un chemin si présent
     *
     * @param string $path Chemin avec ou sans préfixe /uploads/
     * @return string Chemin relatif sans préfixe
     */
    private function getRelativePath(string $path): string
    {
        // Retirer le préfixe /uploads/ si présent
        if (str_starts_with($path, '/uploads/')) {
            return substr($path, strlen('/uploads/'));
        }
        return $path;
    }
}
