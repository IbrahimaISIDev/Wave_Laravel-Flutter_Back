<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ContactService;
use Illuminate\Support\Facades\Log;
use App\Repositories\Interfaces\UserRepositoryInterface;

class ContactController extends Controller
{
    protected $contactService;
    protected $userRepository;

    public function __construct(
        ContactService $contactService,
        UserRepositoryInterface $userRepository
    ) {
        $this->contactService = $contactService;
        $this->userRepository = $userRepository;
    }

    public function list(Request $request)
    {
        try {
            $result = $this->contactService->listContacts($request->user()->id);
            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Erreur liste contacts : ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Erreur lors de la récupération des contacts',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function toggleFavori(Request $request)
    {
        try {
            $validated = $request->validate([
                'contact_id' => 'required|exists:users,id'
            ]);

            // On récupère les infos complètes du contact pour le front
            $contactUser = $this->userRepository->findById($validated['contact_id']);
            if (!$contactUser) {
                return response()->json([
                    'status' => false,
                    'message' => 'Contact non trouvé'
                ], 404);
            }

            $result = $this->contactService->toggleFavori(
                $request->user()->id,
                $validated['contact_id']
            );

            // On enrichit la réponse avec les infos du contact
            $result['contact'] = [
                'id' => $contactUser->id,
                'nom' => $contactUser->nom,
                'prenom' => $contactUser->prenom,
                'telephone' => $contactUser->telephone,
                'email' => $contactUser->email,
            ];

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Erreur toggle favori : ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Erreur lors de la modification du statut favori',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}