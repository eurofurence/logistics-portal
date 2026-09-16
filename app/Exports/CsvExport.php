<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class CsvExport implements FromCollection, WithCustomCsvSettings, WithHeadings, WithStrictNullComparison
{
    protected Collection $rows;

    protected array $headings;

    public function __construct(WithMultipleSheets|MetroExport $export)
    {
        if ($export instanceof MetroExport) {
            $this->rows = $export->collection();
            $this->headings = $export->headings();

            return;
        }

        $this->rows = collect();
        $this->headings = [__('table_exports.department')];

        foreach ($export->sheets() as $sheet) {
            $this->headings = [__('table_exports.department'), ...$sheet->headings()];

            foreach ($sheet->collection() as $row) {
                $this->rows->push([$sheet->title(), ...collect($row)->values()->all()]);
            }
        }
    }

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    /**
     * @return array{delimiter: string, use_bom: bool, output_encoding: string}
     */
    public function getCsvSettings(): array
    {
        return [
            'delimiter' => ';',
            'use_bom' => true,
            'output_encoding' => 'UTF-8',
        ];
    }
}
