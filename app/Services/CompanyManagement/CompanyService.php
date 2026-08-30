<?php

namespace App\Services\CompanyManagement;

use App\DTOs\CompanyManagement\CompanyData;
use App\Models\Company;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CompanyService
{
    /**
     * Create a new Company.
     */
    public function createCompany(CompanyData $data): Company
    {
        return DB::transaction(function () use ($data) {
            $attributes = [
                'name'        => $data->name,
                'address'     => $data->address,
                'city'        => $data->city,
                'province'    => $data->province,
                'postal_code' => $data->postal_code,
                'phone'       => $data->phone,
                'email'       => $data->email,
                'website'     => $data->website,
            ];

            if ($data->logo instanceof UploadedFile) {
                $attributes['logo'] = $this->handleFileUpload($data->logo);
            }

            return Company::create($attributes);
        });
    }

    /**
     * Update an existing Company.
     */
    public function updateCompany(int $id, CompanyData $data): Company
    {
        return DB::transaction(function () use ($id, $data) {
            $company = Company::findOrFail($id);

            $attributes = [
                'name'        => $data->name,
                'address'     => $data->address,
                'city'        => $data->city,
                'province'    => $data->province,
                'postal_code' => $data->postal_code,
                'phone'       => $data->phone,
                'email'       => $data->email,
                'website'     => $data->website,
            ];

            if ($data->logo instanceof UploadedFile) {
                if ($company->logo && Storage::disk('public')->exists($company->logo)) {
                    Storage::disk('public')->delete($company->logo);
                }
                $attributes['logo'] = $this->handleFileUpload($data->logo);
            } elseif ($data->logo === '') {
                if ($company->logo && Storage::disk('public')->exists($company->logo)) {
                    Storage::disk('public')->delete($company->logo);
                }
                $attributes['logo'] = null;
            }

            $company->update($attributes);
            return $company;
        });
    }

    /**
     * Delete a Company.
     */
    public function deleteCompany(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $company = Company::findOrFail($id);
            if ($company->logo && Storage::disk('public')->exists($company->logo)) {
                Storage::disk('public')->delete($company->logo);
            }
            return (bool) $company->delete();
        });
    }

    /**
     * Handle file upload with custom naming in dedicated folder.
     */
    private function handleFileUpload(UploadedFile $file): string
    {
        if (!Storage::disk('public')->exists('company_logos')) {
            Storage::disk('public')->makeDirectory('company_logos');
        }

        $filename = $this->generateCustomFilename($file);
        $file->storeAs('company_logos', $filename, 'public');
        return 'company_logos/' . $filename;
    }

    /**
     * Generate custom filename: [original-name]-[random6].[ext]
     */
    private function generateCustomFilename(UploadedFile $file): string
    {
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $sanitizedName = Str::slug($originalName);
        $extension = $file->getClientOriginalExtension();
        $random = Str::random(6);

        return "{$sanitizedName}-{$random}.{$extension}";
    }
}
