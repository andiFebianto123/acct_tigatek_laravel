<?php

namespace App\Services\Asset;

use App\Models\Asset;
use App\Models\LogPayment;
use App\Models\JournalEntry;
use App\DTOs\Asset\AssetSaveData;
use App\Http\Helpers\CustomHelper;
use App\Http\Helpers\CustomVoid;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AssetService
{
    public function storeAsset(AssetSaveData $data): Asset
    {
        return DB::transaction(function () use ($data) {
            $period = $this->getInclusiveMonthDiff($data->year_acquisition);
            
            $penyusutan_per_tahun = $data->economic_age == 0 ? 0 : ($data->price_acquisition / $data->economic_age);
            $tarif = $data->price_acquisition == 0 ? 0 : ($penyusutan_per_tahun / $data->price_acquisition) * 100;
            $tarif_penyusutan_tahun_ini = $data->price_acquisition - $penyusutan_per_tahun;
            $akumulasi_desember_tahun_ini = round(($penyusutan_per_tahun / 12 * $period) / 10) * 10;
            $nilai_buku_desember = $data->price_acquisition - $akumulasi_desember_tahun_ini;

            $asset = $data->id ? Asset::findOrFail($data->id) : new Asset();
            
            $asset->account_id = $data->account_id;
            $asset->depreciation_account_id = $data->depreciation_account_id;
            $asset->expense_account_id = $data->expense_account_id;
            $asset->description = $data->description;
            $asset->year_acquisition = $data->year_acquisition;
            $asset->price_acquisition = $data->price_acquisition;
            $asset->economic_age = $data->economic_age;
            $asset->tarif = $tarif;
            $asset->price_rate_per_year = $penyusutan_per_tahun;
            $asset->price_rate_year_ago = $data->price_rate_year_ago;
            $asset->accumulated_until_december_last_year = $data->accumulated_until_december_last_year;
            $asset->book_value_last_december = $data->book_value_last_december;
            $asset->this_year_depreciation_rate = $tarif_penyusutan_tahun_ini;
            $asset->accumulated_until_december_this_year = $akumulasi_desember_tahun_ini;
            $asset->book_value_this_december = $nilai_buku_desember;
            $asset->company_id = $data->company_id;
            $asset->save();

            // Handle Journals
            if ($data->id) {
                CustomVoid::rollbackPayment(Asset::class, $asset->id);
            }

            $log_payment = $this->createInitialJournals($asset);

            if (count($log_payment) > 0) {
                LogPayment::create([
                    'reference_type' => Asset::class,
                    'reference_id' => $asset->id,
                    'name' => "CREATE_ASSET",
                    'snapshot' => json_encode($log_payment),
                ]);
            }

            return $asset;
        });
    }

    public function deleteAsset(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            CustomVoid::rollbackPayment(Asset::class, $id);
            return (bool) Asset::destroy($id);
        });
    }

    private function createInitialJournals(Asset $asset): array
    {
        $journalEntries = [
            [
                'account_id' => $asset->account_id,
                'debit' => $asset->price_acquisition,
            ],
            [
                'account_id' => $asset->depreciation_account_id,
                'debit' => $asset->price_rate_per_year,
            ],
            [
                'account_id' => $asset->expense_account_id,
                'debit' => $asset->book_value_this_december,
            ],
        ];

        $log_payment = [];
        foreach ($journalEntries as $entry) {
            $journal = CustomHelper::updateOrCreateJournalEntry([
                'account_id' => $entry['account_id'],
                'reference_id' => $asset->id,
                'reference_type' => Asset::class,
                'description' => 'FIRST BALANCE',
                'date' => Carbon::now(),
                'debit' => $entry['debit'],
            ], [
                'account_id' => $entry['account_id'],
                'reference_id' => $asset->id,
                'reference_type' => Asset::class,
            ]);

            $log_payment[] = [
                'id' => $journal->id,
                'reference_id' => $asset->id,
                'reference_type' => Asset::class,
                'type' => JournalEntry::class,
            ];
        }

        return $log_payment;
    }

    private function getInclusiveMonthDiff($fromDateStr): int
    {
        $fromDate = Carbon::parse($fromDateStr);
        $toDate = Carbon::create(date('Y'), 12, 1);
        return $fromDate->diffInMonths($toDate) + 1;
    }
}
