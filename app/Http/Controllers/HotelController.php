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