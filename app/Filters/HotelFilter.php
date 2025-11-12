<?php

namespace App\Filters;

use App\Enums\StatutHotel;
use App\Enums\Device;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;

class HotelFilter
{
    protected $allowedFilters = [
        'statut',
        'device', 
        'prix_min',
        'prix_max',
        'search',
        'date_from',
        'date_to',
        'sort_field',
        'sort_direction'
    ];

    protected $allowedSortFields = [
        'nom', 
        'prix_par_nuit', 
        'statut', 
        'device', 
        'created_at', 
        'updated_at'
    ];

    public function apply(Builder $query, Request $request): Builder
    {
        // Appliquer chaque filtre disponible
        foreach ($this->allowedFilters as $filter) {
            if ($request->has($filter) && !empty($request->$filter)) {
                $methodName = 'filter' . str_replace('_', '', ucwords($filter, '_'));
                if (method_exists($this, $methodName)) {
                    $this->$methodName($query, $request->$filter, $request);
                }
            }
        }

        // Appliquer le tri
        $this->applySorting($query, $request);

        return $query;
    }

    protected function filterStatut(Builder $query, $value): void
    {
        if (in_array($value, StatutHotel::values())) {
            $query->where('statut', $value);
        }
    }

    protected function filterDevice(Builder $query, $value): void
    {
        if (in_array($value, Device::values())) {
            $query->where('device', $value);
        }
    }

    protected function filterPrixMin(Builder $query, $value): void
    {
        if (is_numeric($value)) {
            $query->where('prix_par_nuit', '>=', (float) $value);
        }
    }

    protected function filterPrixMax(Builder $query, $value): void
    {
        if (is_numeric($value)) {
            $query->where('prix_par_nuit', '<=', (float) $value);
        }
    }

    protected function filterSearch(Builder $query, $value): void
    {
        $searchTerm = '%' . $value . '%';
        $query->where(function($q) use ($searchTerm) {
            $q->where('nom', 'ILIKE', $searchTerm)
              ->orWhere('adresse', 'ILIKE', $searchTerm)
              ->orWhere('telephone', 'ILIKE', $searchTerm)
              ->orWhere('mail', 'ILIKE', $searchTerm);
        });
    }

    protected function filterDateFrom(Builder $query, $value): void
    {
        $query->where('created_at', '>=', $value);
    }

    protected function filterDateTo(Builder $query, $value): void
    {
        $query->where('created_at', '<=', $value . ' 23:59:59');
    }

    protected function applySorting(Builder $query, Request $request): void
    {
        $sortField = $request->get('sort_field', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');

        if (in_array($sortField, $this->allowedSortFields)) {
            $query->orderBy($sortField, $sortDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }
    }

    public function getAppliedFilters(Request $request): array
    {
        return array_intersect_key($request->all(), array_flip($this->allowedFilters));
    }
}