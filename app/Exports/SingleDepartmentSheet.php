<?php

namespace App\Exports;

use Exception;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use PhpOffice\PhpSpreadsheet\Worksheet\HeaderFooter;
use PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing;

class SingleDepartmentSheet implements FromCollection, WithTitle, ShouldAutoSize, WithStyles, WithHeadings, WithEvents, WithCustomStartCell, WithDrawings, WithColumnWidths
{
    protected string $department_name;
    protected $orders;
    protected array $headings;
    protected array $data;
    protected int $image_height;
    protected int $image_width;

    public function __construct(string $department_name, $orders, array $headings, array $data, int $image_height, int $image_width)
    {
        $this->department_name = $department_name;
        $this->orders = $orders;
        $this->headings = $headings;
        $this->data = $data;
        $this->image_height = $image_height;
        $this->image_width = $image_width;
    }

    public function collection()
    {
        return $this->orders;
    }

    public function title(): string
    {
        return $this->department_name;
    }

    public function styles(Worksheet $sheet)
    {
        $styles = [];
        $columns = [];

        // Dynamic columns based on the number of headings
        for ($i = 1; $i <= count($this->headings); $i++) {
            $columns[] = $this->getExcelColumnLetters($i);
        }

        // Dynamic styles for header line (line 2)
        foreach ($columns as $column) {
            $styles["{$column}2"] = [
                'font' => [
                    'bold' => true,
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
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => '000000'], // Schwarze Umrandung
                    ],
                ],
            ];

            $styles["{$column}1"] = [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
                'font' => [
                    'bold' => true,
                    'size' => 26
                ],
                'borders' => [
                    'bottom' => [
                        'borderStyle' => Border::BORDER_NONE,
                    ],
                    'top' => [
                        'borderStyle' => Border::BORDER_NONE,
                    ],
                    'left' => [
                        'borderStyle' => Border::BORDER_NONE,
                    ],
                    'right' => [
                        'borderStyle' => Border::BORDER_NONE,
                    ]
                ]
            ];
        }

        // Dynamic styles for data rows (from row 3)
        $dataStartRow = 3; // Data starts from line 3
        $dataEndRow = $dataStartRow + $this->orders->count() - 1; // Dynamic based on the number of data records

        foreach (range($dataStartRow, $dataEndRow) as $row) {
            foreach ($columns as $column) {
                $styles["{$column}{$row}"] = [
                    'alignment' => [
                        'wrapText' => true,
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => '000000'], // Schwarze Umrandung
                        ],
                    ],
                ];
            }
        }

        // Return of the complete styles
        return $styles;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                if ($this->data['orientation'] == 'portrait') {
                    $event->sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_PORTRAIT);
                } else {
                    $event->sheet->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
                }

                $event->sheet->getDelegate()->getRowDimension(1)->setRowHeight($this->image_height);
                $event->sheet->setCellValue('B1', $this->department_name);

                //Page numbers
                $event->sheet->getHeaderFooter()->setOddFooter('&L&B' . __('general.page') . ' &P ' . __('general.of') . ' &N');
                $event->sheet->getHeaderFooter()->setEvenFooter('&L&B' . __('general.page') . ' &P ' . __('general.of') . ' &N');

                // Set font size and color for header and footer
                $event->sheet->getHeaderFooter()->setOddHeader('&C&H' . HeaderFooter::IMAGE_FOOTER_CENTER . '&G&12&KFF0000');
                $event->sheet->getHeaderFooter()->setOddFooter('&L&B' . $event->sheet->getTitle() . '&RPage &P of &N&12&KFF0000');
            },
        ];
    }

    public function startCell(): string
    {
        return 'A2';
    }

    public function drawings()
    {
        if ($this->data['image'] != null) {
            if (!$imageResource = @imagecreatefromstring(file_get_contents($this->data['image']))) {
                throw new Exception('The image URL cannot be converted into an image resource.');
            }

            imagealphablending($imageResource, false);
            imagesavealpha($imageResource, true);

            $drawing = new MemoryDrawing();
            $drawing->setName('Logo');
            $drawing->setDescription('Sheet logo');
            $drawing->setImageResource($imageResource);
            $drawing->setHeight($this->image_height);
            $drawing->setWidth($this->image_width);
            $drawing->setCoordinates('A1');

            return $drawing;
        }

        // Create a blank image
        $imageResource = imagecreatetruecolor($this->image_width, $this->image_height);
        imagealphablending($imageResource, false);
        imagesavealpha($imageResource, true);
        $transparent = imagecolorallocatealpha($imageResource, 0, 0, 0, 127);
        imagefilledrectangle($imageResource, 0, 0, $this->image_width, $this->image_height, $transparent);

        $drawing = new MemoryDrawing();
        $drawing->setName('Empty Logo');
        $drawing->setDescription('Empty sheet logo');
        $drawing->setImageResource($imageResource);
        $drawing->setHeight($this->image_height);
        $drawing->setWidth($this->image_width);
        $drawing->setCoordinates('A1');

        return $drawing;
    }

    public function columnWidths(): array
    {
        return [
            'A' => $this->image_width / 7,
        ];
    }

    public function getExcelColumnLetters($columnNumber)
    {
        $letters = '';
        while ($columnNumber > 0) {
            $remainder = ($columnNumber - 1) % 26;
            $letters = chr(65 + $remainder) . $letters;
            $columnNumber = intval(($columnNumber - 1) / 26);
        }
        return $letters;
    }
}
