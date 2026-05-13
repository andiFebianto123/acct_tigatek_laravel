<?php

namespace App\Repositories\Asset;

use App\Models\Asset;
use Illuminate\Database\Eloquent\Collection;

class AssetRepository
{
    public function findById(int $id): ?Asset
    {
        return Asset::find($id);
    }

    public function getAllAssets(): Collection
    {
        return Asset::all();
    }
}
