<?php

namespace App\Exports;

use App\Models\Department;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class InventoryItemsExport extends ExportBase implements WithMultipleSheets
{
    public function __construct(array $data, int $image_height = 92, int $image_width = 92, array $bool_columns = [])
    {
        parent::__construct($data, $image_height, $image_width, $bool_columns);

        $item_collection = $this->data['records'];
        $this->included_columns[] = 'department';

        $items = $item_collection->map(function ($item) {
            $list = [];
            foreach ($this->included_columns as $column) {
                $list[$column] = $item[$column] ?? null;
            }

            return $list;
        });

        $items = $items->transform(function ($item) {
            $itemArray = is_array($item) ? $item : (array) $item;
            foreach ($itemArray as $key => $value) {
                if (in_array($key, $this->bool_columns)) {
                    $itemArray[$key] = $value ? __('general.yes') : __('general.no');
                }
            }

            return $itemArray;
        });

        $final_records = [];
        foreach ($items->groupBy('department_id') as $key => $value) {
            $department_name = Department::where('id', $key)->first()->name ?? 'Unknown Department';
            $final_records[$key]['department_name'] = $department_name;
            $final_records[$key]['items'] = $value->map(function ($record) {
                $recordArray = collect($record);

                return $recordArray->only(array_diff($this->included_columns, ['department']));
            });
        }

        $this->final_records = $final_records;
    }

    public function sheets(): array
    {
        $sheets = [];
        foreach ($this->final_records as $sheet) {
            $sheets[] = new SingleDepartmentSheet($sheet['department_name'], $sheet['items'], $this->headings, $this->data, $this->image_height, $this->image_width);
        }

        return $sheets;
    }

}
