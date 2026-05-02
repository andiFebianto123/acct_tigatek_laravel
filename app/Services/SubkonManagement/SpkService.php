<?php

namespace App\Services\SubkonManagement;

use App\Models\Spk;
use App\DTOs\SubkonManagement\SpkData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class SpkService
{
    /**
     * Create a new SPK.
     */
    public function createSpk(SpkData $data): Spk
    {
        return DB::transaction(function () use ($data) {
            $attributes = $data->toArray();

            // Calculate total value with tax
            $taxValue = ($data->job_value * ($data->tax_ppn ?? 0) / 100);
            $attributes['total_value_with_tax'] = $data->job_value + $taxValue;

            if ($data->document_path instanceof UploadedFile) {
                $attributes['document_path'] = $this->handleFileUpload($data->document_path);
            }

            return Spk::create($attributes);
        });
    }

    /**
     * Update an existing SPK.
     */
    public function updateSpk(int $id, SpkData $data): Spk
    {
        return DB::transaction(function () use ($id, $data) {
            $spk = Spk::findOrFail($id);
            $attributes = $data->toArray();

            // Calculate total value with tax
            $taxValue = ($data->job_value * ($data->tax_ppn ?? 0) / 100);
            $attributes['total_value_with_tax'] = $data->job_value + $taxValue;

            if ($data->document_path instanceof UploadedFile) {
                // Delete old file if exists
                if ($spk->document_path) {
                    Storage::disk('public')->delete($spk->document_path);
                }
                $attributes['document_path'] = $this->handleFileUpload($data->document_path);
            }

            $spk->update($attributes);
            return $spk;
        });
    }

    /**
     * Delete an SPK.
     */
    public function deleteSpk(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $spk = Spk::findOrFail($id);

            // Delete file if exists
            if ($spk->document_path) {
                Storage::disk('public')->delete('document_spk/' . $spk->document_path);
            }

            return (bool) $spk->delete();
        });
    }

    /**
     * Get UI events for the JSON response (Specific to current project requirement).
     */
    public function getUIEvents(Spk $item, string $actionType = 'create'): array
    {
        $suffix = $actionType === 'create' ? 'create_success' : 'updated_success';

        return [
            "crudTable-list_all_spk_{$suffix}" => $item,
            "crudTable-list_open_{$suffix}" => $item,
            "crudTable-list_close_{$suffix}" => $item,
            "crudTable-filter-spk_plugin_load" => $item,
        ];
    }

    /**
     * Handle file upload with custom naming.
     */
    private function handleFileUpload(UploadedFile $file): string
    {
        $filename = $this->generateCustomFilename($file);
        $file->storeAs('document_spk', $filename, 'public');
        return 'document_spk/' . $filename;
    }

    /**
     * Generate custom filename: [original-name]-[random5].[ext]
     */
    private function generateCustomFilename(UploadedFile $file): string
    {
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $sanitizedName = Str::slug($originalName);
        $extension = $file->getClientOriginalExtension();
        $random = Str::random(5);

        return "{$sanitizedName}-{$random}.{$extension}";
    }
}
