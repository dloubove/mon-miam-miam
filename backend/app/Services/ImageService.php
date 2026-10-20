<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Enregistrement des images (photos d'articles, affiches) sur le disque « public ».
 * En base on ne garde que le chemin relatif (ex. articles/abc123.jpg) ; l'URL complète
 * est fabriquée au moment de répondre (voir url()).
 */
class ImageService
{
    private const DISQUE = 'public';

    /** Enregistre le fichier et renvoie son chemin relatif. Le nom est aléatoire (pas de collision, pas de nom dangereux). */
    public function enregistrer(UploadedFile $fichier, string $dossier): string
    {
        return $fichier->store($dossier, self::DISQUE);
    }

    /** Supprime un fichier s'il existe (sans erreur s'il a déjà disparu). */
    public function supprimer(?string $chemin): void
    {
        if ($chemin) {
            Storage::disk(self::DISQUE)->delete($chemin);
        }
    }

    /** URL publique d'un chemin enregistré (null s'il n'y a pas d'image). */
    public function url(?string $chemin): ?string
    {
        return $chemin ? Storage::disk(self::DISQUE)->url($chemin) : null;
    }
}
