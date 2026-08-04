<?php

namespace App\Http\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProfitLostSupplierExcel implements FromView, WithStyles, WithColumnWidths, WithEvents
{
    protected $profit_lost;
    protected $report;

    public function __construct($profit_lost, $report)
    {
        $this->profit_lost = $profit_lost;
        $this->report = $report;
    }

    public function view(): View
    {
        return view('exports.profit-lost-supplier-detail-excel', [
            'profit_lost' => $this->profit_lost,
            'report' => $this->report,
        ]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $sheet->getStyle('B7:B13')
                    ->getNumberFormat()
                    ->setFormatCode('#,##0.00');
            },
        ];
    }

    /**
     * Styling Excel
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14], 'alignment' => ['horizontal' => 'center']],
            2 => ['font' => ['bold' => true, 'size' => 12], 'alignment' => ['horizontal' => 'center']],
            3 => ['font' => ['size' => 11], 'alignment' => ['horizontal' => 'center']],
            4 => ['font' => ['size' => 10], 'alignment' => ['horizontal' => 'center']],

            6 => ['font' => ['bold' => true], 'alignment' => ['horizontal' => 'center']],

            'A7' => ['font' => ['bold' => true]],
            'A8' => ['font' => ['bold' => true]],
            'A11' => ['font' => ['bold' => true]],
            'A12' => ['font' => ['bold' => true]],
            'A13' => ['font' => ['bold' => true]],
        ];
    }

    /**
     * Atur lebar kolom
     */
    public function columnWidths(): array
    {
        return [
            'A' => 45,
            'B' => 25,
            'C' => 15,
            'D' => 20,
            'E' => 20,
        ];
    }
}
