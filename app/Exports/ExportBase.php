<?php
namespace App\Exports;

use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithDefaultStyles;
use Maatwebsite\Excel\Events\BeforeExport;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ExportBase implements WithDefaultStyles, WithEvents
{
    use Exportable;

    protected $final_records;
    protected array $headings = [];
    protected array $data;
    protected array $included_columns = [];
    protected int $image_height;
    protected int $image_width;
    protected array $bool_columns;

    public function __construct(array $data, int $image_height = 92, int $image_width = 92, array $bool_columns = [])
    {
        $this->data = $data;
        $this->image_height = $image_height;
        $this->image_width = $image_width;
        $this->bool_columns = $bool_columns;
        $this->included_columns = $this->data['columns'] ?? [];
        $this->included_columns = array_unique(array_merge(['id', 'name'], $this->included_columns));

        $this->headings = array_map(function ($column) {
            return __('table_exports.' . $column) ?? ucfirst(str_replace('_', ' ', $column));
        }, $this->included_columns);
    }

    public function defaultStyles(Style $defaultStyle)
    {
        return [
            'font' => [
                'name' => 'Arial',
                'color' => [
                    'rgb' => '1a1a1a'
                ]
            ],
            'borders' => [
                'bottom' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => [
                        'rgb' => '1a1a1a'
                    ]
                ],
                'top' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => [
                        'rgb' => '1a1a1a'
                    ]
                ],
                'left' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => [
                        'rgb' => '1a1a1a'
                    ]
                ],
                'right' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => [
                        'rgb' => '1a1a1a'
                    ]
                ]
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            BeforeExport::class => function (BeforeExport $event) {
                $event->writer->getDelegate()->getProperties()
                    ->setCreator(Auth::user()->name)
                    ->setLastModifiedBy(Auth::user()->name)
                    ->setTitle('Logistics Export')
                    ->setDescription('Exported data that can be accessed by the current user')
                    ->setSubject('Data')
                    ->setKeywords('export,data')
                    ->setCategory('Data');
            },
        ];
    }

    protected function formatPrice(float $price, string $currency): string
    {
        if ($currency === 'EUR') {
            return number_format($price, 2, ',', '.');
        } elseif ($currency === 'USD') {
            return number_format($price, 2, '.', ',');
        } else {
            return number_format($price, 2);
        }
    }
}
