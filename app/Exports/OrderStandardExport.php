<?php
namespace App\Exports;

use App\Models\Department;
use App\Exports\SingleDepartmentSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class OrderStandardExport extends ExportBase implements WithMultipleSheets
{
    public function __construct(array $data, int $image_height = 92, int $image_width = 92, array $bool_columns = [])
    {
        parent::__construct($data, $image_height, $image_width, $bool_columns);

        $order_collection = $this->data['records'];
        $this->included_columns[] = 'department_id';

        $orders = $order_collection->map(function ($item) {
            $ordered = [];
            foreach ($this->included_columns as $column) {
                $ordered[$column] = $item[$column] ?? null;
            }
            return $ordered;
        });

        $orders = $orders->transform(function ($order) {
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
        $this->processOption('show_who_approved_order', $this->data);
    }

    public function sheets(): array
    {
        $sheets = [];
        foreach ($this->final_records as $sheet) {
            $sheets[] = new SingleDepartmentSheet($sheet['department_name'], $sheet['orders'], $this->headings, $this->data, $this->image_height, $this->image_width);
        }
        return $sheets;
    }

    private function processOption(string $option): bool
    {
        if (isset($this->data[$option]) && $this->data[$option] === true) {
            $this->headings[] = __('table_exports.option_titles.' . $option);

            switch ($option) {
                case 'calculate_total_net':
                    foreach ($this->final_records as &$department) {
                        foreach ($department['orders'] as &$order) {
                            $order_id = $order['id'];
                            $order_model = $this->data['records']->where('id', $order_id)->first();
                            $order['total_net'] = $this->formatPrice($order_model['amount'] * $order_model['price_net'], $order_model['currency']) . ' ' . $order_model['currency'];
                        }
                    }
                    break;

                case 'calculate_total_gross':
                    foreach ($this->final_records as &$department) {
                        foreach ($department['orders'] as &$order) {
                            $order_id = $order['id'];
                            $order_model = $this->data['records']->where('id', $order_id)->first();
                            $order['total_gross'] = $this->formatPrice($order_model['amount'] * $order_model['price_gross'], $order_model['currency']) . ' ' . $order_model['currency'];
                        }
                    }
                    break;

                case 'calculate_total_returning_deposit':
                    foreach ($this->final_records as &$department) {
                        foreach ($department['orders'] as &$order) {
                            $order_id = $order['id'];
                            $order_model = $this->data['records']->where('id', $order_id)->first();
                            $order['total_returning_deposit'] = $this->formatPrice($order_model['amount'] * $order_model['returning_deposit'], $order_model['currency']) . ' ' . $order_model['currency'];
                        }
                    }
                    break;

                case 'show_who_added_order':
                    foreach ($this->final_records as &$department) {
                        foreach ($department['orders'] as &$order) {
                            $order_id = $order['id'];
                            $order_model = $this->data['records']->where('id', $order_id)->first();
                            $order['show_who_added_order'] = $order_model['addedBy']['name'];
                        }
                    }
                    break;

                case 'show_who_approved_order':
                    foreach ($this->final_records as &$department) {
                        foreach ($department['orders'] as &$order) {
                            $order_id = $order['id'];
                            $order_model = $this->data['records']->where('id', $order_id)->first();

                            if (!empty($order_model['approvedBy']['name'])) {
                                $order['show_who_approved_order'] = $order_model['approvedBy']['name'];
                            }
                        }
                    }
                    break;

                default:
                    return false;
            }
            return true;
        }
        return false;
    }
}
