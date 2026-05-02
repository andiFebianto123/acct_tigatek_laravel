<?php

namespace App\Http\Exports;

use Carbon\Carbon;
use App\Models\PurchaseOrder;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExportVendorPo implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    private $type;
    private $year;
    private $rowNumber = 0;

    function __construct($type, $year = null)
    {
        if ($type == 'open') {
            $this->type = 'open';
        } else if ($type == 'close') {
            $this->type = 'close';
        } else {
            $this->type = 'all';
        }
        $this->year = $year;
    }

    public function collection()
    {
        $dataset = new PurchaseOrder;

        if ($this->type != 'all') {
            $dataset = $dataset->where('status', strtolower($this->type));
        }

        if ($this->year && $this->year != 'all') {
            $dataset = $dataset->whereYear('date_po', $this->year);
        }

        if (request()->has('columns')) {
            // Index 1: po_number
            if (trim(request()->columns[1]['search']['value'] ?? '') != '') {
                $search = request()->columns[1]['search']['value'];
                $dataset = $dataset->where('po_number', 'like', "%{$search}%");
            }
            // Index 2: date_po
            if (trim(request()->columns[2]['search']['value'] ?? '') != '') {
                $search = request()->columns[2]['search']['value'];
                $dataset = $dataset->where('date_po', 'like', "%{$search}%");
            }
            // Index 3: subkon_id (searching by subkon.name)
            if (trim(request()->columns[3]['search']['value'] ?? '') != '') {
                $search = request()->columns[3]['search']['value'];
                $dataset = $dataset->whereHas('subkon', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            }
            // Index 4: work_code
            if (trim(request()->columns[4]['search']['value'] ?? '') != '') {
                $search = request()->columns[4]['search']['value'];
                $dataset = $dataset->where('work_code', 'like', "%{$search}%");
            }
            // Index 5: job_name
            if (trim(request()->columns[5]['search']['value'] ?? '') != '') {
                $search = request()->columns[5]['search']['value'];
                $dataset = $dataset->where('job_name', 'like', "%{$search}%");
            }
            // Index 6: job_description
            if (trim(request()->columns[6]['search']['value'] ?? '') != '') {
                $search = request()->columns[6]['search']['value'];
                $dataset = $dataset->where('job_description', 'like', "%{$search}%");
            }
            // Index 7: job_value
            if (trim(request()->columns[7]['search']['value'] ?? '') != '') {
                $search = request()->columns[7]['search']['value'];
                $dataset = $dataset->where('job_value', 'like', "%{$search}%");
            }
            // Index 8: tax_ppn
            if (trim(request()->columns[8]['search']['value'] ?? '') != '') {
                $search = request()->columns[8]['search']['value'];
                $dataset = $dataset->where('tax_ppn', 'like', "%{$search}%");
            }
            // Index 9: total_value_with_tax
            if (trim(request()->columns[9]['search']['value'] ?? '') != '') {
                $search = request()->columns[9]['search']['value'];
                $dataset = $dataset->where('total_value_with_tax', 'like', "%{$search}%");
            }
            // Index 10: due_date
            if (trim(request()->columns[10]['search']['value'] ?? '') != '') {
                $search = request()->columns[10]['search']['value'];
                $dataset = $dataset->where(function ($q) use ($search) {
                    $q->where('due_date', 'like', "%{$search}%")
                        ->orWhere('date_po', 'like', "%{$search}%");
                });
            }
            // Index 11: status
            if (trim(request()->columns[11]['search']['value'] ?? '') != '') {
                $search = request()->columns[11]['search']['value'];
                $dataset = $dataset->where('status', 'like', "%{$search}%");
            }
            // Index 13: additional_info
            if (trim(request()->columns[13]['search']['value'] ?? '') != '') {
                $search = request()->columns[13]['search']['value'];
                $dataset = $dataset->where('additional_info', 'like', "%{$search}%");
            }
        }
        return $dataset->select(
            'subkon_id',
            'po_number',
            'date_po',
            'job_name',
            'job_description',
            'job_value',
            'tax_ppn',
            'total_value_with_tax',
            'due_date',
            'status',
            'company_id'
        )->with('company')->get();
    }

    public function headings(): array
    {
        $headings = [
            'No',
            trans('backpack::crud.po.column.po_number'),
            trans('backpack::crud.po.column.date_po'),
            trans('backpack::crud.subkon.column.name'),
            trans('backpack::crud.po.column.job_name'),
            trans('backpack::crud.po.column.job_description'),
            trans('backpack::crud.po.column.job_value'),
            trans('backpack::crud.po.column.tax_ppn'),
            trans('backpack::crud.po.column.total_value_with_tax'),
            trans('backpack::crud.po.column.due_date'),
            trans('backpack::crud.po.column.status'),
        ];

        if (backpack_user()->hasRole('Super Admin')) {
            array_splice($headings, 3, 0, [trans('backpack::crud.subkon.column.company')]);
        }

        return $headings;
    }

    public function map($entry): array
    {
        $this->rowNumber++;

        $subkonName = $entry->subkon->name ?? '-';
        $date_po = $entry->date_po
            ? Carbon::parse($entry->date_po)->translatedFormat("d/m/Y")
            : '-';

        $due_date = $entry->due_date
            ? Carbon::parse($entry->due_date)->translatedFormat("d/m/Y")
            : '-';

        $status = strtoupper($entry->status ?? '-');

        $data = [
            $this->rowNumber,
            $entry->po_number ?? '-',
            $date_po,
            $subkonName,
            $entry->job_name ?? '-',
            $entry->job_description ?? '-',
            $entry->job_value ?? 0,
            $entry->tax_ppn ?? 0,
            $entry->total_value_with_tax ?? 0,
            $due_date,
            $status,
        ];

        if (backpack_user()->hasRole('Super Admin')) {
            array_splice($data, 3, 0, [$entry->company->name ?? '-']);
        }

        return $data;
    }

    public function styles(Worksheet $sheet)
    {
        $highestColumn = backpack_user()->hasRole('Super Admin') ? 'L' : 'K'; // Ganti jika jumlah kolom berubah

        $sheet->getStyle("A1:{$highestColumn}" . ($this->rowNumber + 1))->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ]);

        $sheet->getStyle("A1:{$highestColumn}1")->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
    }
}
