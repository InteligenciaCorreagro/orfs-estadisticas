<?php

namespace App\Services\Excel;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Exception;

class ExcelReader
{
    private string $filePath;
    private ?Spreadsheet $spreadsheet = null;
    
    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
    }
    
    public function load(): self
    {
        try {
            $this->spreadsheet = IOFactory::load($this->filePath);
        } catch (Exception $e) {
            throw new Exception("Error al cargar archivo Excel: " . $e->getMessage());
        }
        
        return $this;
    }
    
    public function toArray(int $sheetIndex = 0): array
    {
        if (!$this->spreadsheet) {
            $this->load();
        }
        
        $sheet = $this->spreadsheet->getSheet($sheetIndex);
        $data = [];
        
        foreach ($sheet->getRowIterator() as $row) {
            $rowData = [];
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            
            foreach ($cellIterator as $cell) {
                $rowData[] = $cell->getValue();
            }
            
            $data[] = $rowData;
        }
        
        return $data;
    }
    
    public function toAssociativeArray(int $sheetIndex = 0): array
    {
        $data = $this->toArray($sheetIndex);
        
        if (empty($data)) {
            return [];
        }
        
        $headers = array_shift($data);
        $headers = array_map(fn($h) => strtolower(trim($h)), $headers);
        
        $result = [];
        foreach ($data as $row) {
            if (count($row) === count($headers)) {
                $result[] = array_combine($headers, $row);
            }
        }
        
        return $result;
    }
    
    public function getSheetCount(): int
    {
        if (!$this->spreadsheet) {
            $this->load();
        }
        
        return $this->spreadsheet->getSheetCount();
    }
    
    public function getSheetNames(): array
    {
        if (!$this->spreadsheet) {
            $this->load();
        }
        
        return $this->spreadsheet->getSheetNames();
    }
}