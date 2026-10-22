<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Enregistrement des images (photos d'articles, affiches) localement ou sur ImageKit.
 * En base on ne garde que le chemin relatif (ex. articles/abc123.jpg) ; l'URL complète
 * est fabriquée au moment de répondre (voir url()).
 */
class ImageService
{
    private function disque(): string
    {
        return config('filesystems.image_disk', 'public');
    }

    /** Enregistre le fichier et renvoie son chemin relatif. Le nom est aléatoire (pas de collision, pas de nom dangereux). */
    public function enregistrer(UploadedFile $fichier, string $dossier): string
    {
        $disque = $this->disque();

        if ($disque === 'imagekit') {
            $privateKey = config('services.imagekit.private_key');
            if (! $privateKey) {
                throw new \RuntimeException('La clé privée ImageKit n’est pas configurée.');
            }

            $nomFichier = $fichier->hashName();
            $reponse = Http::withBasicAuth(config('services.imagekit.private_key'), '')
                ->attach('file', fopen($fichier->getRealPath(), 'r'), $nomFichier)
                ->post('https://upload.imagekit.io/api/v1/files/upload', [
                    'fileName' => $nomFichier,
                    'folder' => '/'.$dossier,
                    'useUniqueFileName' => 'true',
                ]);

            $reponse->throw();
            $fichierImage = $reponse->json();

            if (empty($fichierImage['fileId']) || empty($fichierImage['filePath'])) {
                throw new \RuntimeException('ImageKit n’a pas retourné les informations du fichier téléversé.');
            }

            return 'ik:'.$fichierImage['fileId'].':'.$fichierImage['filePath'];
        }

        $chemin = $fichier->store($dossier, $disque);

        // Local deployments may need a real public/storage copy when symlinks
        // are unavailable; remote disks must never be accessed through path().
        if (config("filesystems.disks.{$disque}.driver") === 'local') {
            $source = Storage::disk($disque)->path($chemin);
            $destination = public_path('storage/'.$chemin);
            if (is_file($source) && realpath($source) !== realpath($destination)) {
                if (! is_dir(dirname($destination))) {
                    mkdir(dirname($destination), 0755, true);
                }
                copy($source, $destination);
            }
        }

        return $chemin;
    }

    /** Supprime un fichier s'il existe (sans erreur s'il a déjà disparu). */
    public function supprimer(?string $chemin): void
    {
        if ($chemin) {
            if (str_starts_with($chemin, 'ik:')) {
                [, $fileId] = explode(':', $chemin, 3);
                $reponse = Http::withBasicAuth(config('services.imagekit.private_key'), '')
                    ->delete('https://api.imagekit.io/v1/files/'.rawurlencode($fileId));

                if ($reponse->failed() && $reponse->status() !== 404) {
                    $reponse->throw();
                }

                return;
            }

            // Older database rows contain local paths, even after switching providers.
            $disque = 'public';
            Storage::disk($disque)->delete($chemin);
            if (config("filesystems.disks.{$disque}.driver") === 'local') {
                @unlink(public_path('storage/'.$chemin));
            }
        }
    }

    /** URL publique d'un chemin enregistré (null s'il n'y a pas d'image). */
    public function url(?string $chemin): ?string
    {
        if (! $chemin) {
            return null;
        }

        if (str_starts_with($chemin, 'ik:')) {
            [, , $filePath] = explode(':', $chemin, 3);
            $endpoint = config('services.imagekit.url_endpoint');
            if (! $endpoint) {
                throw new \RuntimeException('L’URL endpoint ImageKit n’est pas configurée.');
            }

            return rtrim($endpoint, '/').'/'.ltrim($filePath, '/');
        }

        $disque = Storage::disk($this->disque() === 'imagekit' ? 'public' : $this->disque());

        return $disque->url($chemin);
    }
}
