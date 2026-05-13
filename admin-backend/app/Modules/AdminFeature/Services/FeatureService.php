<?php

namespace App\Modules\AdminFeature\Services;

use App\AppFeatures;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FeatureService
{
    private function normalizeFeatureType($value): ?string
    {
        $type = trim((string) ($value ?? ''));
        if ($type === '') {
            return null;
        }

        return mb_substr($type, 0, 255);
    }

    public function list(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $q = AppFeatures::query();

        if (!empty($filters['search'])) {
            $s = '%' . $filters['search'] . '%';
            $q->where(function ($sub) use ($s) {
                $sub->where('feature_name', 'like', $s)
                    ->orWhere('feature_type', 'like', $s)
                    ->orWhere('feature_description', 'like', $s);
            });
        }

        if (!empty($filters['feature_status']) && $filters['feature_status'] !== 'all') {
            if ($filters['feature_status'] === 'active') {
                $q->whereIn('feature_status', ['A', '1', 1, 'Active']);
            } elseif ($filters['feature_status'] === 'inactive') {
                $q->whereNotIn('feature_status', ['A', '1', 1, 'Active']);
            }
        }

        return $q->orderByDesc('feature_id')->paginate($perPage);
    }

    public function getOne(int $featureId): AppFeatures
    {
        return AppFeatures::findOrFail($featureId);
    }

    public function create(array $data): AppFeatures
    {
        $data['feature_status'] = $this->normalizeStatus($data['feature_status'] ?? 'A');
        if (array_key_exists('feature_type', $data)) {
            $data['feature_type'] = $this->normalizeFeatureType($data['feature_type']);
        }
        return AppFeatures::create($data);
    }

    public function update(int $featureId, array $data): AppFeatures
    {
        $feature = AppFeatures::findOrFail($featureId);
        if (array_key_exists('feature_status', $data)) {
            $data['feature_status'] = $this->normalizeStatus($data['feature_status']);
        }
        if (array_key_exists('feature_type', $data)) {
            $data['feature_type'] = $this->normalizeFeatureType($data['feature_type']);
        }
        $feature->update($data);
        return $feature;
    }

    public function delete(int $featureId): void
    {
        $feature = AppFeatures::findOrFail($featureId);
        $feature->delete();
    }

    private function normalizeStatus($status): string
    {
        // DB column matches legacy app: A = active, D = disabled (not I — ENUM rejects I and truncates)
        if (in_array($status, ['A', '1', 1, true, 'Active'], true)) {
            return 'A';
        }
        if ($status === 'I') {
            return 'D';
        }

        return 'D';
    }
}

