<?php
// src/Services/BusinessIntelligence/ChunkedExcelReader.php

namespace App\Services\BusinessIntelligence;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

/**
 * Filtro para leer chunks de Excel
 */
class ChunkReadFilter implements IReadFilter
{
    private int $startRow;
    private int $endRow;

    public function __construct(int $startRow, int $chunkSize)
    {
        $this->startRow = $startRow;
        $this->endRow = $startRow + $chunkSize;
    }

    public function readCell($columnAddress, $row, $worksheetName = '')
    {
        // Lee la fila de headers (fila 1) siempre
        if ($row == 1) {
            return true;
        }
        // Lee solo las filas en el rango del chunk
        if ($row >= $this->startRow && $row < $this->endRow) {
            return true;
        }
        return false;
    }
}

/**
 * Lector de Excel optimizado para archivos grandes
 * Lee el archivo por chunks para evitar problemas de memoria
 */
class ChunkedExcelReader
{
    private string $filePath;
    private int $chunkSize;

    public function __construct(string $filePath, int $chunkSize = 500)
    {
        $this->filePath = $filePath;
        $this->chunkSize = $chunkSize;
    }

    /**
     * Procesar archivo por chunks
     */
    public function processInChunks(callable $callback): array
    {
        $results = [
            'processed' => 0,
            'errors' => []
        ];

        try {
            // Primero, obtener el número total de filas
            $inputFileType = IOFactory::identify($this->filePath);
            $reader = IOFactory::createReader($inputFileType);
            $reader->setReadDataOnly(true);

            $worksheetData = $reader->listWorksheetInfo($this->filePath);
            $totalRows = $worksheetData[0]['totalRows'];

            // Leer headers primero (fila 1)
            $headerFilter = new ChunkReadFilter(1, 1);
            $reader->setReadFilter($headerFilter);
            $spreadsheet = $reader->load($this->filePath);
            $worksheet = $spreadsheet->getActiveSheet();

            $headers = [];
            foreach ($worksheet->getRowIterator(1, 1) as $row) {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);

                foreach ($cellIterator as $cell) {
                    $value = $cell->getValue();
                    $headers[] = strtolower(trim($this->convertToString($value)));
                }
            }

            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

            // Procesar por chunks
            for ($startRow = 2; $startRow <= $totalRows; $startRow += $this->chunkSize) {
                $chunkFilter = new ChunkReadFilter($startRow, $this->chunkSize);
                $reader = IOFactory::createReader($inputFileType);
                $reader->setReadDataOnly(true);
                $reader->setReadFilter($chunkFilter);

                $spreadsheet = $reader->load($this->filePath);
                $worksheet = $spreadsheet->getActiveSheet();

                $chunkData = [];
                foreach ($worksheet->getRowIterator() as $row) {
                    if ($row->getRowIndex() == 1) continue; // Skip header row

                    $rowData = [];
                    $cellIterator = $row->getCellIterator();
                    $cellIterator->setIterateOnlyExistingCells(false);

                    $colIndex = 0;
                    foreach ($cellIterator as $cell) {
                        if ($colIndex >= count($headers)) break;

                        $value = $cell->getValue();
                        $rowData[$headers[$colIndex]] = $this->convertToString($value);
                        $colIndex++;
                    }

                    if (!empty($rowData)) {
                        $chunkData[] = $rowData;
                    }
                }

                // Procesar el chunk con el callback
                if (!empty($chunkData)) {
                    try {
                        $callback($chunkData, $results);
                    } catch (\Exception $e) {
                        $results['errors'][] = "Error en chunk (filas {$startRow} a " . ($startRow + $this->chunkSize) . "): " . $e->getMessage();
                    }
                }

                // Liberar memoria
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet, $chunkData);
                gc_collect_cycles();
            }

        } catch (\Exception $e) {
            $results['errors'][] = "Error general: " . $e->getMessage();
        }

        return $results;
    }

    /**
     * Convertir valor de celda a string
     */
    private function convertToString($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (is_object($value)) {
            if (method_exists($value, '__toString')) {
                return (string) $value;
            }
            if (method_exists($value, 'getPlainText')) {
                return $value->getPlainText();
            }
            return '';
        }

        return (string) $value;
    }
}
