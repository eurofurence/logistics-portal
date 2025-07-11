<?php

namespace App\Exports;

use App\Models\Order;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Style;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\BeforeExport;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithDefaultStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ItemsExport implements FromCollection, WithTitle, ShouldAutoSize, WithEvents, WithStyles, WithDefaultStyles, WithHeadings
{
    protected \Illuminate\Support\Collection $orders;
    protected array $headings;
    protected array $included_columns = ['id', 'name', 'amount', 'price_net', 'price_gross', 'status', 'user_note', 'comment', 'article_number', 'url'];
    protected int $event_id;

    public function __construct(Collection $orders)
    {

        $this->headings = [
            __('general.id') . '/' . __('general.group'),
            __('general.name'),
            __('general.amount'),
            __('general.price_net'),
            __('general.price_gross'),
            __('general.status'),
            __('general.user_note'),
            __('general.comment'),
            __('general.article_number'),
        ];

        // Filter the orders to include only those with the 'metro.de' domain in the URL
        $filteredOrders = $orders->filter(function ($order) {
            return strpos($order->url, 'metro.de') !== false;
        });


        // Group the selected data by 'url'
        $grouped_data = $filteredOrders->groupBy('url');

        $final_records = collect();

        // Iterate over grouped data to insert header and spacer rows
        foreach ($grouped_data as $url => $records) {
            // Add the URL as a header row
            $final_records->push(collect(['url' => $url]));

            // Add the records for this group
            foreach ($records as $record) {
                $recordArray = collect($record->toArray());

                // Filter the record to include only the included columns
                $filteredRecordArray = $recordArray->only($this->included_columns);

                // Remove the 'url' attribute if it's still present
                $filteredRecordArray->forget('url');

                // Remove the 'url' attribute
                $filteredRecordArray->forget('url');

                // Create a new collection with the attributes in the order of $included_columns
                $orderedRecordArray = collect();

                foreach ($this->included_columns as $column) {
                    if ($filteredRecordArray->has($column)) {
                        $orderedRecordArray->put($column, $filteredRecordArray->get($column));
                    }
                }

                $final_records->push($orderedRecordArray);
            }

            // Calculate the total amount for this group
            $total_amount = $records->sum('amount');

            // Add a row with the total amount
            $final_records->push(collect([
                'empty1' => null,
                'empty2' => null,
                'total_amount' => __('general.sum') . ': ' . $total_amount
            ]));

            // Add one empty row as a spacer
            $final_records->push(collect(['empty_row' => null]));
            // Add one empty row as a spacer
            $final_records->push(collect(['empty_row' => null]));
            // Add one empty row as a spacer
            $final_records->push(collect(['empty_row' => null]));
        }

        $this->orders = $final_records;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return $this->orders;
    }

    public function title(): string
    {
        return __('general.metro_list');
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
            },
            BeforeExport::class => function (BeforeExport $event) {
                $event->writer->getDelegate()->getProperties()
                    ->setCreator(Auth::user()->name)
                    ->setLastModifiedBy(Auth::user()->name)
                    ->setTitle('Inventory-Items Export')
                    ->setDescription('A list of inventory items')
                    ->setSubject('Orders')
                    ->setKeywords('items,export,departments,eurofurence,logistics')
                    ->setCategory('Inventory');
            },
        ];
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1    => [
                'font' => [
                    'bold' => true
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['argb' => '055350'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
            ],
            'A'    => [
                'font' => [
                    'bold' => true
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
            ],
        ];
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
            ]
        ];
    }
}
