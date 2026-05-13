<?php

namespace App\Modules\AdminLineOfBusiness\Services;

use App\LineOfBusiness;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class LineOfBusinessService
{
    public function list(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $q = LineOfBusiness::query();

        if (!empty($filters['search'])) {
            $s = '%' . $filters['search'] . '%';
            $q->where(function ($sub) use ($s) {
                $sub->where('lob_name', 'like', $s)
                    ->orWhere('lob_description', 'like', $s);
            });
        }

        if (!empty($filters['lob_status']) && $filters['lob_status'] !== 'all') {
            $q->where('lob_status', $filters['lob_status']);
        }

        $q->orderByDesc('create_dtm');

        return $q->paginate($perPage);
    }

    public function getOne(int $lobId): LineOfBusiness
    {
        return LineOfBusiness::findOrFail($lobId);
    }

    public function create(array $data): LineOfBusiness
    {
        return LineOfBusiness::create($data);
    }

    public function update(int $lobId, array $data): LineOfBusiness
    {
        $lob = LineOfBusiness::findOrFail($lobId);
        $lob->update($data);
        return $lob;
    }

    public function delete(int $lobId): void
    {
        $lob = LineOfBusiness::findOrFail($lobId);
        $lob->delete();
    }
}

