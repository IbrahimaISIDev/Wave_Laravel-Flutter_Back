<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddFavoriRequest;
use App\Services\FavoriService;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class FavoriController extends Controller
{
    protected $favoriService;

    public function __construct(FavoriService $favoriService)
    {
        $this->favoriService = $favoriService;
    }

    /**
     * Ajouter un contact aux favoris
     */
    public function add(AddFavoriRequest $request)
    {
        try {
            $result = $this->favoriService->addFavori(
                $request->user()->id,
                $request->telephone,
                $request->nom_complet
            );

            return response()->json($result, $result['status'] ? 200 : 400);

        } catch (\Exception $e) {
            Log::error('Erreur ajout favori : ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Erreur lors de l\'ajout aux favoris',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lister tous les favoris
     */
    public function list(Request $request)
    {
        try {
            $result = $this->favoriService->getFavoris($request->user()->id);
            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Erreur liste favoris : ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Erreur lors de la récupération des favoris',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer un favori
     */
    public function delete(Request $request, $id)
    {
        try {
            $result = $this->favoriService->deleteFavori($request->user()->id, $id);
            return response()->json($result, $result['status'] ? 200 : 400);

        } catch (\Exception $e) {
            Log::error('Erreur suppression favori : ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Erreur lors de la suppression du favori',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vérifier si un numéro est déjà en favori
     */
    public function checkFavori(Request $request)
    {
        try {
            $validated = $request->validate([
                'telephone' => 'required|string'
            ]);

            $result = $this->favoriService->isFavori(
                $request->user()->id, 
                $validated['telephone']
            );

            return response()->json($result);

        } catch (\Exception $e) {
            Log::error('Erreur vérification favori : ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Erreur lors de la vérification du favori',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}