<?php
// src/Services/Excel/ExcelWriter.php

namespace App\Services\Excel;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ExcelWriter
{
    private Spreadsheet $spreadsheet;
    private $activeSheet;
    
    public function __construct()
    {
        $this->spreadsheet = new Spreadsheet();
        $this->activeSheet = $this->spreadsheet->getActiveSheet();
    }
    
    public function setHeaders(array $headers): self
    {
        $column = 'A';
        foreach ($headers as $header) {
            $this->activeSheet->setCellValue($column . '1', $header);
            $column++;
        }
        
        // Estilo de encabezados
        $lastColumn = chr(ord('A') + count($headers) - 1);
        $headerRange = 'A1:' . $lastColumn . '1';
        
        $this->activeSheet->getStyle($headerRange)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ]);
        
        return $this;
    }
    
    public function setData(array $data): self
    {
        $row = 2;
        foreach ($data as $rowData) {
            $column = 'A';
            foreach ($rowData as $value) {
                $this->activeSheet->setCellValue($column . $row, $value);
                $column++;
            }
            $row++;
        }
        
        return $this;
    }
    
    public function autoSize(): self
    {
        foreach ($this->activeSheet->getColumnIterator() as $column) {
            $this->activeSheet->getColumnDimension($column->getColumnIndex())
                ->setAutoSize(true);
        }
        
        return $this;
    }
    
    public function addBorders(): self
    {
        $highestRow = $this->activeSheet->getHighestRow();
        $highestColumn = $this->activeSheet->getHighestColumn();
        
        $this->activeSheet->getStyle('A1:' . $highestColumn . $highestRow)
            ->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000']
                    ]
                ]
            ]);
        
        return $this;
    }
    
    public function formatCurrency(string $columnRange): self
    {
        $this->activeSheet->getStyle($columnRange)
            ->getNumberFormat()
            ->setFormatCode('#,##0.00');
        
        return $this;
    }
    
    public function save(string $filepath): void
    {
        $writer = new Xlsx($this->spreadsheet);
        $writer->save($filepath);
    }
    
    public function download(string $filename): void
    {
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer = new Xlsx($this->spreadsheet);
        $writer->save('php://output');
        exit;
    }
}