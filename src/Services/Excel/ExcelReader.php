<?php
/**
 * CORRECCIÓN PARA ExcelReader.php
 * 
 * Problema: PhpSpreadsheet devuelve objetos RichText en lugar de strings
 * Solución: Convertir todos los valores a string usando getCalculatedValue()
 */

namespace App\Services\Excel;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
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
                // CORRECCIÓN: Convertir valores correctamente
                $rowData[] = $this->getCellValue($cell);
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
        $headers = array_map(fn($h) => strtolower(trim($this->convertToString($h))), $headers);
        
        $result = [];
        foreach ($data as $row) {
            if (count($row) === count($headers)) {
                // CORRECCIÓN: Convertir todos los valores a string/number
                $cleanRow = array_map([$this, 'convertToString'], $row);
                $result[] = array_combine($headers, $cleanRow);
            }
        }
        
        return $result;
    }
    
    /**
     * Obtener valor de celda correctamente (maneja RichText, fórmulas, fechas)
     */
    private function getCellValue(Cell $cell)
    {
        try {
            $value = $cell->getCalculatedValue();
            
            // Si es fecha, obtener el valor numérico
            if (\PhpOffice\PhpSpreadsheet\Shared\Date::isDateTime($cell)) {
                return $cell->getValue(); // Retorna el número serial
            }
            
            return $value;
        } catch (\Exception $e) {
            return $cell->getValue();
        }
    }
    
    /**
     * Convertir cualquier valor a string (maneja RichText)
     */
    private function convertToString($value): string
    {
        if ($value === null) {
            return '';
        }
        
        // Si es objeto RichText
        if ($value instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
            return trim($value->getPlainText());
        }
        
        // Si es otro tipo de objeto con __toString
        if (is_object($value) && method_exists($value, '__toString')) {
            return trim((string) $value);
        }
        
        // Convertir a string normalmente
        return trim((string) $value);
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