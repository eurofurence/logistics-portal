<?php

namespace App\Exports;

use App\Models\Department;
use Illuminate\Support\Facades\Auth;
use App\Exports\SingleDepartmentSheet;
use PhpOffice\PhpSpreadsheet\Style\Style;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeExport;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Maatwebsite\Excel\Concerns\WithDefaultStyles;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class OrderStandardExport implements WithMultipleSheets, WithDefaultStyles, WithEvents
{
    use Exportable;

    protected $final_records;
    protected array $headings = [];
    protected array $data;
    protected array $included_columns = [];
    protected int $image_height;
    protected int $image_width;
    protected int $event_id;
    protected array $bool_columns;

    public function __construct(array $data, int $image_height = 92, int $image_width = 92, array $bool_columns = [])
    {
        $this->data = $data;
        $this->image_height = $image_height;
        $this->image_width = $image_width;
        $this->bool_columns = $bool_columns;

        $order_collection = $this->data['records'];

        // Included columns (Columns) from the data
        $this->included_columns = $this->data['columns'] ?? [];
        $this->included_columns[] = 'department_id'; // Needed to group by department
        $this->included_columns = array_unique(array_merge(['id', 'name'], $this->included_columns)); // Ensures required columns are included


        // Dynamically create headings based on the included columns
        $this->headings = array_map(function ($column) {
            // Load translations or default to column name if translation is missing
            return __('table_exports.' . $column) ?? ucfirst(str_replace('_', ' ', $column));
        }, array_diff($this->included_columns, ['department_id']));


        // Clearing orders and grouping by "department_id"
        $orders = $order_collection->map(function ($item) {
            $ordered = [];
            foreach ($this->included_columns as $column) {
                $ordered[$column] = $item[$column] ?? null;
            }
            return $ordered;
        });



        // Convert bool values to text - Only converts columns that are inlcuded in $this-$bool_columns
        $orders = $orders->transform(function ($order) {
            // Make sure that $order can be processed as an array
            $orderArray = is_array($order) ? $order : (array) $order;

            foreach ($orderArray as $key => $value) {
                if (in_array($key, $this->bool_columns)) {
                    $orderArray[$key] = $value ? __('general.yes') : __('general.no');
                }
            }

            return $orderArray;
        });
        $final_records = array();

        foreach ($orders->groupBy('department_id') as $key => $value) {
            $department_name = Department::where('id', $key)->first()->name ?? 'Unknown Department';
            $final_records[$key]['department_name'] = $department_name;

            $final_records[$key]['orders'] = $value->map(function ($record) {
                $recordArray = collect($record);
                return $recordArray->only(array_diff($this->included_columns, ['department_id']));
            });
        }

        $this->final_records = $final_records;

        $this->processOption('calculate_total_net', $this->data);
        $this->processOption('calculate_total_gross', $this->data);
        $this->processOption('calculate_total_returning_deposit', $this->data);
        $this->processOption('show_who_added_order', $this->data);
    }


    public function sheets(): array
    {
        $sheets = [];

        foreach ($this->final_records as $sheet) {
            $sheets[] = new SingleDepartmentSheet($sheet['department_name'], $sheet['orders'], $this->headings, $this->data, $this->image_height, $this->image_width);
        }

        return $sheets;
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

    /**
     * The function `registerEvents` sets metadata properties for an export event in PHP.
     *
     * @return array An array containing an event listener for the BeforeExport event is being returned. The event listener
     * sets various properties for the export writer, such as creator, last modified by, title, description, subject,
     * keywords, and category.
     */
    public function registerEvents(): array
    {
        return [
            BeforeExport::class => function (BeforeExport $event) {
                $event->writer->getDelegate()->getProperties()
                    ->setCreator(Auth::user()->name)
                    ->setLastModifiedBy(Auth::user()->name)
                    ->setTitle('Standart Order Export')
                    ->setDescription('All orders that can be accessed by the current user grouped into departments')
                    ->setSubject('Orders')
                    ->setKeywords('orders,export,departments,eurofurence,logistics')
                    ->setCategory('Orders');
            },
        ];
    }

    private function processOption(string $option): bool
    {
        if (isset($this->data[$option]) && $this->data[$option] === true) {
            // Add header
            $this->headings[] = __('table_exports.option_titles.' . $option);

            switch ($option) {
                case 'calculate_total_net':
                    foreach ($this->final_records as $department) {
                        foreach ($department['orders'] as $order) {
                            $order_id = $order['id'];
                            $order_model = $this->data['records']->where('id', $order_id);

                            $order['total_net'] = $this->formatPrice($order_model->value('amount') * $order_model->value('price_net'), $order_model->value('currency')) . ' ' .  $order_model->value('currency');
                        }
                    }
                    break;

                case 'calculate_total_gross':
                    foreach ($this->final_records as $department) {
                        foreach ($department['orders'] as $order) {
                            $order_id = $order['id'];
                            $order_model = $this->data['records']->where('id', $order_id);

                            $order['total_gross'] = $this->formatPrice($order_model->value('amount') * $order_model->value('price_gross'), $order_model->value('currency')) . ' ' .  $order_model->value('currency');
                        }
                    }
                    break;

                case 'calculate_total_returning_deposit':
                    foreach ($this->final_records as $department) {
                        foreach ($department['orders'] as $order) {
                            $order_id = $order['id'];
                            $order_model = $this->data['records']->where('id', $order_id);

                            $order['total_returning_deposit'] = $this->formatPrice($order_model->value('amount') * $order_model->value('returning_deposit'), $order_model->value('currency')) . ' ' .  $order_model->value('currency');
                        }
                    }
                    break;

                case 'show_who_added_order':
                    foreach ($this->final_records as $department) {
                        foreach ($department['orders'] as $order) {
                            $order_id = $order['id'];
                            $order_model = $this->data['records']->where('id', $order_id);

                            $order['show_who_added_order'] = $order_model->value('addedBy.name');
                        }
                    }
                    break;

                default:
                    return false;
                    break;
            }

            return true;
        }

        return false;
    }

    /**
     * The function `formatPrice` formats a given price value based on the specified currency, using different separators
     * for decimal and thousands places depending on the currency.
     *
     * @param int price The `price` parameter is an integer representing the price of a product or service.
     * @param string currency The `currency` parameter in the `formatPrice` function is a string that represents the
     * currency for which the price should be formatted. It can have three possible values: 'EUR' for Euro, 'USD' for US
     * Dollar, and any other value for a standard formatting without specific currency rules.
     *
     * @return string The function `formatPrice` returns a formatted string representation of the price based on the
     * specified currency. If the currency is 'EUR', the price is formatted with a comma as the decimal separator and a dot
     * as the thousands separator. If the currency is 'USD', the price is formatted with a point as the decimal separator
     * and a comma as the thousands separator. For any other currency, the price is
     */
    private function formatPrice(float $price, string $currency): string
    {
        // Formatting depending on currency
        if ($currency === 'EUR') {
            return number_format($price, 2, ',', '.'); // German: comma as decimal separator, dot as thousands separator
        } elseif ($currency === 'USD') {
            return number_format($price, 2, '.', ','); // English: Point as decimal separator, comma as thousands separator
        } else {
            return number_format($price, 2); // Standard
        }
    }
}
