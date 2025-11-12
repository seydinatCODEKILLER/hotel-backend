<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use App\Enums\StatutHotel;
use App\Enums\Device;
use App\Filters\HotelFilter;
use App\Services\FileUploadService;
use App\Services\PaginationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Routing\Controller as BaseController;
use Exception;

/**
 * @OA\Tag(
 *     name="Hotels",
 *     description="Gestion des hôtels"
 * )
 */
class HotelController extends BaseController
{
    protected $fileUploadService;
    protected $hotelFilter;
    protected $paginationService;

    public function __construct(FileUploadService $fileUploadService, HotelFilter $hotelFilter, PaginationService $paginationService)
    {
        $this->fileUploadService = $fileUploadService;
        $this->hotelFilter = $hotelFilter;
        $this->paginationService = $paginationService;
        $this->middleware('auth:sanctum');
    }

    /**
     * @OA\Get(
     *     path="/hotels",
     *     summary="Lister les hôtels avec pagination et filtres",
     *     tags={"Hotels"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Numéro de page",
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Nombre d'éléments par page (5-100)",
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Parameter(
     *         name="statut",
     *         in="query",
     *         description="Filtrer par statut",
     *         @OA\Schema(type="string", enum={"actif", "inactif"})
     *     ),
     *     @OA\Parameter(
     *         name="device",
     *         in="query",
     *         description="Filtrer par devise",
     *         @OA\Schema(type="string", enum={"FCFA", "EURO", "DOLLARS"})
     *     ),
     *     @OA\Parameter(
     *         name="prix_min",
     *         in="query",
     *         description="Prix minimum",
     *         @OA\Schema(type="number", format="float")
     *     ),
     *     @OA\Parameter(
     *         name="prix_max",
     *         in="query",
     *         description="Prix maximum",
     *         @OA\Schema(type="number", format="float")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Recherche textuelle (nom, adresse, téléphone, email)",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sort_field",
     *         in="query",
     *         description="Champ de tri",
     *         @OA\Schema(type="string", enum={"nom", "prix_par_nuit", "statut", "device", "created_at", "updated_at"})
     *     ),
     *     @OA\Parameter(
     *         name="sort_direction",
     *         in="query",
     *         description="Direction du tri",
     *         @OA\Schema(type="string", enum={"asc", "desc"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des hôtels paginée",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Hotel")),
     *             @OA\Property(property="pagination", type="object",
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=5),
     *                 @OA\Property(property="per_page", type="integer", example=15),
     *                 @OA\Property(property="total", type="integer", example=75)
     *             )
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            
            // Construction de la requête de base
            $query = Hotel::withTrashed()->where('user_id', $user->id);

            // Application des filtres
            $this->hotelFilter->apply($query, $request);

            // Pagination
            $perPage = $this->paginationService->validatePerPage($request->get('per_page'));
            $hotels = $query->paginate($perPage);

            // Données de réponse
            $response = [
                'success' => true,
                'data' => $hotels->items(),
                'pagination' => $this->paginationService->getPaginationData($hotels),
                'filters' => $this->hotelFilter->getAppliedFilters($request),
                'meta' => [
                    'total' => $hotels->total(),
                    'current_count' => count($hotels->items()),
                    'has_more' => $hotels->hasMorePages(),
                ]
            ];

            Log::info('Hotels fetched successfully', [
                'user_id' => $user->id,
                'total_results' => $hotels->total(),
                'applied_filters' => $response['filters']
            ]);

            return response()->json($response);

        } catch (Exception $e) {
            Log::error('Error fetching hotels', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'filters' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des hôtels'
            ], 500);
        }
    }

    public function getFilterOptions()
    {
        try {
            $options = [
                'statuts' => collect(StatutHotel::cases())->map(fn($case) => [
                    'value' => $case->value,
                    'label' => $case->label(),
                ]),
                'devices' => collect(Device::cases())->map(fn($case) => [
                    'value' => $case->value,
                    'label' => $case->label(),
                    'symbol' => $case->symbol(),
                ]),
                'sort_fields' => [
                    ['value' => 'nom', 'label' => 'Nom'],
                    ['value' => 'prix_par_nuit', 'label' => 'Prix par nuit'],
                    ['value' => 'statut', 'label' => 'Statut'],
                    ['value' => 'created_at', 'label' => 'Date de création'],
                ],
                'sort_directions' => [
                    ['value' => 'asc', 'label' => 'Croissant'],
                    ['value' => 'desc', 'label' => 'Décroissant'],
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $options
            ]);

        } catch (Exception $e) {
            Log::error('Error fetching filter options', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des options'
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/hotels",
     *     summary="Créer un nouvel hôtel",
     *     tags={"Hotels"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"nom", "adresse", "mail", "telephone", "prix_par_nuit", "device", "statut"},
     *                 @OA\Property(property="nom", type="string", maxLength=255, example="Hôtel Paradise"),
     *                 @OA\Property(property="adresse", type="string", example="123 Avenue des Champs-Élysées, Paris"),
     *                 @OA\Property(property="mail", type="string", format="email", example="contact@hotelparadise.com"),
     *                 @OA\Property(property="telephone", type="string", example="+33123456789"),
     *                 @OA\Property(property="prix_par_nuit", type="number", format="float", minimum=0, example=150.00),
     *                 @OA\Property(property="device", type="string", enum={"FCFA", "EURO", "DOLLARS"}, example="EURO"),
     *                 @OA\Property(property="statut", type="string", enum={"actif", "inactif"}, example="actif"),
     *                 @OA\Property(property="photo", type="string", format="binary", description="Photo de l'hôtel")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Hôtel créé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Hôtel créé avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/Hotel")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'nom' => 'required|string|max:255',
                'adresse' => 'required|string',
                'mail' => 'required|email',
                'telephone' => 'required|string',
                'prix_par_nuit' => 'required|numeric|min:0',
                'device' => 'required|in:' . implode(',', Device::values()),
                'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240', // 10MB
                'statut' => 'sometimes|in:' . implode(',', StatutHotel::values()),
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $hotelData = $request->all();
            
            // Statut par défaut à Actif
            if (!isset($hotelData['statut'])) {
                $hotelData['statut'] = StatutHotel::ACTIF->value;
            }

            // Upload de la photo si présente
            if ($request->hasFile('photo')) {
                $hotelData['photo'] = $this->fileUploadService->uploadHotelPhoto($request->file('photo'));
            }

            $hotel = $request->user()->hotels()->create($hotelData);

            Log::info('Hotel created successfully', [
                'hotel_id' => $hotel->id,
                'user_id' => $request->user()->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Hôtel créé avec succès',
                'data' => $hotel
            ], 201);

        } catch (Exception $e) {
            Log::error('Error creating hotel', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'data' => $request->except(['photo']) // Exclure le fichier pour les logs
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l\'hôtel: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/hotels/{id}",
     *     summary="Récupérer un hôtel spécifique",
     *     tags={"Hotels"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de l'hôtel",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Détails de l'hôtel",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Hotel")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Hôtel non trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Hôtel non trouvé")
     *         )
     *     )
     * )
     */
    public function show($id)
    {
        try {
            $hotel = Hotel::withTrashed()->find($id);

            if (!$hotel) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hôtel non trouvé'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $hotel
            ]);

        } catch (Exception $e) {
            Log::error('Error fetching hotel', [
                'hotel_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'hôtel'
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/hotels/{id}",
     *     summary="Modifier un hôtel",
     *     tags={"Hotels"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de l'hôtel",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="nom", type="string", maxLength=255, example="Hôtel Paradise Modifié"),
     *                 @OA\Property(property="adresse", type="string", example="456 Nouvelle Adresse, Paris"),
     *                 @OA\Property(property="mail", type="string", format="email", example="nouveau@hotelparadise.com"),
     *                 @OA\Property(property="telephone", type="string", example="+33987654321"),
     *                 @OA\Property(property="prix_par_nuit", type="number", format="float", minimum=0, example=200.00),
     *                 @OA\Property(property="device", type="string", enum={"FCFA", "EURO", "DOLLARS"}, example="EURO"),
     *                 @OA\Property(property="statut", type="string", enum={"actif", "inactif"}, example="actif"),
     *                 @OA\Property(property="photo", type="string", format="binary", description="Nouvelle photo de l'hôtel")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Hôtel modifié avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Hôtel modifié avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/Hotel")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Non autorisé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Non autorisé à modifier cet hôtel")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Hôtel non trouvé"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        try {
            $hotel = Hotel::find($id);

            if (!$hotel) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hôtel non trouvé'
                ], 404);
            }

            if ($hotel->user_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non autorisé à modifier cet hôtel'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'nom' => 'sometimes|string|max:255',
                'adresse' => 'sometimes|string',
                'mail' => 'sometimes|email',
                'telephone' => 'sometimes|string',
                'prix_par_nuit' => 'sometimes|numeric|min:0',
                'device' => 'sometimes|in:' . implode(',', Device::values()),
                'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
                'statut' => 'sometimes|in:' . implode(',', StatutHotel::values()),
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $hotelData = $request->all();
            $oldPhoto = $hotel->photo;

            // Upload de la nouvelle photo si présente
            if ($request->hasFile('photo')) {
                $hotelData['photo'] = $this->fileUploadService->uploadHotelPhoto($request->file('photo'));
                
                // Supprimer l'ancienne photo après le nouvel upload réussi
                if ($oldPhoto) {
                    $this->fileUploadService->deleteFile($oldPhoto);
                }
            }

            $hotel->update($hotelData);

            Log::info('Hotel updated successfully', [
                'hotel_id' => $hotel->id,
                'user_id' => $request->user()->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Hôtel modifié avec succès',
                'data' => $hotel
            ]);

        } catch (Exception $e) {
            Log::error('Error updating hotel', [
                'hotel_id' => $id,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification de l\'hôtel: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/hotels/{id}/update-photo",
     *     summary="Mettre à jour uniquement la photo d'un hôtel",
     *     tags={"Hotels"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de l'hôtel",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"photo"},
     *                 @OA\Property(property="photo", type="string", format="binary", description="Nouvelle photo de l'hôtel")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Photo mise à jour avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Photo mise à jour avec succès"),
     *             @OA\Property(property="photo", type="string", example="https://cloudinary.com/new-photo.jpg")
     *         )
     *     )
     * )
     */
    public function updatePhoto(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:10240',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $hotel = Hotel::find($id);

            if (!$hotel) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hôtel non trouvé'
                ], 404);
            }

            if ($hotel->user_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non autorisé à modifier cet hôtel'
                ], 403);
            }

            $oldPhoto = $hotel->photo;

            // Upload de la nouvelle photo
            $photoUrl = $this->fileUploadService->uploadHotelPhoto($request->file('photo'));
            $hotel->update(['photo' => $photoUrl]);

            // Supprimer l'ancienne photo après mise à jour réussie
            if ($oldPhoto) {
                $this->fileUploadService->deleteFile($oldPhoto);
            }

            Log::info('Hotel photo updated successfully', [
                'hotel_id' => $hotel->id,
                'user_id' => $request->user()->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Photo mise à jour avec succès',
                'photo' => $photoUrl
            ]);

        } catch (Exception $e) {
            Log::error('Error updating hotel photo', [
                'hotel_id' => $id,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de la photo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/hotels/{id}",
     *     summary="Supprimer un hôtel (soft delete)",
     *     tags={"Hotels"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de l'hôtel",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Hôtel supprimé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Hôtel supprimé avec succès")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Hôtel non trouvé"
     *     )
     * )
     */
    public function destroy($id)
    {
        try {
            $hotel = Hotel::find($id);

            if (!$hotel) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hôtel non trouvé'
                ], 404);
            }

            $hotel->update(['statut' => StatutHotel::INACTIF]);
            $hotel->delete();

            Log::info('Hotel soft deleted successfully', [
                'hotel_id' => $hotel->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Hôtel supprimé avec succès'
            ]);

        } catch (Exception $e) {
            Log::error('Error deleting hotel', [
                'hotel_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de l\'hôtel: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Patch(
     *     path="/hotels/{id}/restore",
     *     summary="Restaurer un hôtel supprimé",
     *     tags={"Hotels"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de l'hôtel",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Hôtel restauré avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Hôtel restauré avec succès")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Hôtel non trouvé"
     *     )
     * )
     */
    public function restore($id)
    {
        try {
            $hotel = Hotel::withTrashed()->find($id);

            if (!$hotel) {
                return response()->json([
                    'success' => false,
                    'message' => 'Hôtel non trouvé'
                ], 404);
            }

            $hotel->update(['statut' => StatutHotel::ACTIF]);
            $hotel->restore();

            Log::info('Hotel restored successfully', [
                'hotel_id' => $hotel->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Hôtel restauré avec succès'
            ]);

        } catch (Exception $e) {
            Log::error('Error restoring hotel', [
                'hotel_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la restauration de l\'hôtel: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/hotels/statistiques",
     *     summary="Récupérer les statistiques des hôtels",
     *     tags={"Hotels"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Statistiques des hôtels",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="total_hotels", type="integer", example=25),
     *                 @OA\Property(property="hotels_actifs", type="integer", example=20),
     *                 @OA\Property(property="hotels_inactifs", type="integer", example=3),
     *                 @OA\Property(property="hotels_supprimes", type="integer", example=2)
     *             )
     *         )
     *     )
     * )
     */
    public function statistiques(Request $request)
    {
        try {
            $user = $request->user();

            $totalHotels = $user->hotels()->count();
            $hotelsActifs = $user->hotels()->where('statut', StatutHotel::ACTIF->value)->count();
            $hotelsInactifs = $user->hotels()->where('statut', StatutHotel::INACTIF->value)->count();
            $hotelsSupprimes = $user->hotels()->onlyTrashed()->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_hotels' => $totalHotels,
                    'hotels_actifs' => $hotelsActifs,
                    'hotels_inactifs' => $hotelsInactifs,
                    'hotels_supprimes' => $hotelsSupprimes,
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Error fetching statistics', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/hotels/statistiques/graphiques",
     *     summary="Récupérer les statistiques pour graphiques (hôtels créés par mois)",
     *     tags={"Hotels"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Statistiques pour graphiques",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="mois", type="string", example="January 2024"),
     *                     @OA\Property(property="total", type="integer", example=5)
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function statistiquesGraphiques(Request $request)
    {
        try {
            $user = $request->user();

            $hotelsParMois = $user->hotels()
                ->select(
                    DB::raw("TO_CHAR(created_at, 'YYYY-MM') as mois"),
                    DB::raw('COUNT(*) as total')
                )
                ->groupBy(DB::raw("TO_CHAR(created_at, 'YYYY-MM')"))
                ->orderBy('mois')
                ->get()
                ->map(function ($item) {
                    return [
                        'mois' => Carbon::createFromFormat('Y-m', $item->mois)->format('F Y'),
                        'total' => $item->total
                    ];
                })
                ->values();

            return response()->json([
                'success' => true,
                'data' => $hotelsParMois
            ]);

        } catch (Exception $e) {
            Log::error('Error fetching graph statistics', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques graphiques'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/enums",
     *     summary="Récupérer les valeurs des enums utilisés dans l'application",
     *     tags={"Utils"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Valeurs des enums",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="statut_hotel", type="object",
     *                     @OA\Property(property="ACTIF", type="string", example="actif"),
     *                     @OA\Property(property="INACTIF", type="string", example="inactif")
     *                 ),
     *                 @OA\Property(property="devices", type="object",
     *                     @OA\Property(property="FCFA", type="string", example="FCFA"),
     *                     @OA\Property(property="EURO", type="string", example="EURO"),
     *                     @OA\Property(property="DOLLARS", type="string", example="DOLLARS")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function getEnums()
    {
        try {
            return response()->json([
                'success' => true,
                'data' => [
                    'statut_hotel' => [
                        'ACTIF' => StatutHotel::ACTIF->value,
                        'INACTIF' => StatutHotel::INACTIF->value,
                    ],
                    'devices' => [
                        'FCFA' => Device::FCFA->value,
                        'EURO' => Device::EURO->value,
                        'DOLLARS' => Device::DOLLARS->value,
                    ]
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Error fetching enums', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des enums'
            ], 500);
        }
    }
}

