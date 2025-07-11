<?php

namespace App\Http\Controllers;

use App\Models\Meme;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class MemeController extends Controller
{
    /**
     * Afficher la liste des mèmes de l'utilisateur connecté
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $memes = Meme::forUser($request->user()->id)
                         ->recent()
                         ->with('user:id,name')
                         ->get();
            
            return response()->json($memes, 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la récupération des mèmes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Créer un nouveau mème
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'image_path' => 'required|string',
                'text_top' => 'nullable|string|max:500',
                'text_bottom' => 'nullable|string|max:500',
                'font_size' => 'nullable|integer|min:10|max:200',
                'font_color' => 'nullable|string|max:7',
                'stroke_color' => 'nullable|string|max:7',
                'stroke_width' => 'nullable|integer|min:0|max:20',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Données de validation invalides',
                    'errors' => $validator->errors()
                ], 422);
            }

            $meme = Meme::create(array_merge($request->only([
                'title',
                'image_path',
                'text_top',
                'text_bottom',
                'font_size',
                'font_color',
                'stroke_color',
                'stroke_width'
            ]), [
                'user_id' => $request->user()->id
            ]));

            $meme->load('user:id,name');

            return response()->json($meme, 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la création du mème',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher un mème spécifique de l'utilisateur connecté
     */
    public function show(Request $request, string $id): JsonResponse
    {
        try {
            $meme = Meme::where('id', $id)
                        ->where('user_id', $request->user()->id)
                        ->with('user:id,name')
                        ->firstOrFail();
            
            return response()->json($meme, 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Mème non trouvé ou accès non autorisé',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Mettre à jour un mème de l'utilisateur connecté
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $meme = Meme::where('id', $id)
                        ->where('user_id', $request->user()->id)
                        ->firstOrFail();

            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|string|max:255',
                'image_path' => 'sometimes|string',
                'text_top' => 'nullable|string|max:500',
                'text_bottom' => 'nullable|string|max:500',
                'font_size' => 'nullable|integer|min:10|max:200',
                'font_color' => 'nullable|string|max:7',
                'stroke_color' => 'nullable|string|max:7',
                'stroke_width' => 'nullable|integer|min:0|max:20',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Données de validation invalides',
                    'errors' => $validator->errors()
                ], 422);
            }

            $meme->update($request->only([
                'title',
                'image_path',
                'text_top',
                'text_bottom',
                'font_size',
                'font_color',
                'stroke_color',
                'stroke_width'
            ]));

            $meme->load('user:id,name');

            return response()->json($meme, 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la mise à jour du mème ou accès non autorisé',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer un mème de l'utilisateur connecté
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        try {
            $meme = Meme::where('id', $id)
                        ->where('user_id', $request->user()->id)
                        ->firstOrFail();
            
            // Supprimer le fichier image du stockage
            if (Storage::disk('public')->exists($meme->image_path)) {
                Storage::disk('public')->delete($meme->image_path);
            }
            
            $meme->delete();

            return response()->json([
                'message' => 'Mème supprimé avec succès'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la suppression du mème ou accès non autorisé',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Télécharger une image
     */
    public function uploadImage(Request $request): JsonResponse
    {
        try {
            
            $validator = Validator::make($request->all(), [
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:10240', // Max 10MB
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Fichier image invalide',
                    'errors' => $validator->errors()
                ], 422);
            }

            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $filename = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                $path = $image->storeAs('memes', $filename, 'public');

                return response()->json([
                    'path' => $path,
                    'url' => asset('storage/' . $path),
                    'filename' => $filename
                ], 200);
            }

            return response()->json([
                'message' => 'Aucun fichier image trouvé'
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors du téléchargement de l\'image',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les statistiques des mèmes de l'utilisateur connecté
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            $userId = $request->user()->id;
            
            $totalMemes = Meme::forUser($userId)->count();
            $recentMemes = Meme::forUser($userId)
                              ->where('created_at', '>=', now()->subDays(7))
                              ->count();
            $popularMemes = Meme::forUser($userId)
                               ->orderBy('created_at', 'desc')
                               ->limit(5)
                               ->with('user:id,name')
                               ->get();

            return response()->json([
                'total_memes' => $totalMemes,
                'recent_memes' => $recentMemes,
                'popular_memes' => $popularMemes
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la récupération des statistiques',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 