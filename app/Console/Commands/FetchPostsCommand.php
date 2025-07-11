<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Site;
use App\Models\Post;
use Illuminate\Support\Facades\Http;

class FetchPostsCommand extends Command
{
    protected $signature = 'fetch:posts';
    protected $description = 'Récupère automatiquement des posts depuis un service externe.';

    public function handle()
    {
        // Exemple : pour chaque site, on appelle un endpoint fictif
        $sites = Site::all();
        foreach ($sites as $site) {
            // Admettons qu’on ait un endpoint : $site->url.'/api/posts'
            try {
                $response = Http::get($site->url . '/api/posts');
                if ($response->successful()) {
                    $data = $response->json();
                    // on suppose que $data est un array de posts
                    foreach ($data as $postData) {
                        // Vérifier si le post existe déjà (selon titre, lien, etc.)
                        $exists = Post::where('link', $postData['link'])->first();
                        if (!$exists) {
                            Post::create([
                                'site_id'           => $site->id,
                                'title'             => $postData['title'],
                                'image'             => $postData['image'] ?? null,
                                'short_description' => $postData['short_description'] ?? '',
                                'long_description'  => $postData['long_description'] ?? '',
                                'link'              => $postData['link'],
                            ]);
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->error("Erreur lors de la récupération des posts pour le site: {$site->name}. Détails: " . $e->getMessage());
            }
        }

        $this->info('Récupération des posts terminée.');
    }
}
