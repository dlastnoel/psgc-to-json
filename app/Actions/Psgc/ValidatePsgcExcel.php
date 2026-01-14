<?php

namespace App\Actions\Psgc;

use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReader;

class ValidatePsgcExcel
{
    protected array $errors = [];

    protected bool $isValid = false;

    public static function run(string $filePath): self
    {
        $validator = new self;

        $validator->validate($filePath);

        return $validator;
    }

    public function validate(string $filePath): void
    {
        try {
            // Read only first 100 rows for validation
            $reader = IOFactory::createReaderForFile($filePath);

            // Load only first sheet
            $reader->setLoadSheetsOnly([config('psgc.validation.required_sheet')]);

            // Set read filter to limit rows
            $filter = new RowLimitFilter(100);
            $reader->setReadFilter($filter);

            $spreadsheet = $reader->load($filePath);

            $this->validateSheetExists($spreadsheet);

            if ($this->errors) {
                return;
            }

            $this->validateColumns($spreadsheet);

            // Clear spreadsheet to free memory
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        } catch (\Exception $e) {
            $this->errors[] = 'File is corrupted or invalid Excel format: '.$e->getMessage();
        }

        $this->isValid = empty($this->errors);
    }

    protected function validateSheetExists($spreadsheet): void
    {
        $requiredSheet = config('psgc.validation.required_sheet');

        if (! $spreadsheet->getSheetByName($requiredSheet)) {
            $this->errors[] = 'PSGC sheet not found in Excel file. Required sheet: '.$requiredSheet;
        }
    }

    protected function validateColumns($spreadsheet): void
    {
        $sheetName = config('psgc.validation.required_sheet');
        $sheet = $spreadsheet->getSheetByName($sheetName);

        if (! $sheet) {
            return;
        }

        $data = $sheet->toArray();

        if (empty($data)) {
            $this->errors[] = 'PSGC sheet is empty or has no data';

            return;
        }

        $headerRow = $data[0];

        $requiredColumns = config('psgc.validation.required_columns');
        $foundColumns = [];

        // Normalize required columns for comparison (trim and lowercase)
        $requiredColumnsNormalized = [];
        foreach ($requiredColumns as $column) {
            $requiredColumnsNormalized[strtolower(trim($column))] = trim($column);
        }

        foreach ($headerRow as $cell) {
            $value = trim((string) $cell);

            if ($value) {
                // Use trimmed lowercase value for comparison
                $foundColumns[strtolower($value)] = true;
            }
        }

        foreach ($requiredColumnsNormalized as $normalizedKey => $originalColumn) {
            if (! isset($foundColumns[$normalizedKey])) {
                $this->errors[] = "Required column '{$originalColumn}' is missing in PSGC sheet";
            }
        }

        $this->validateGeographicLevels($sheet, $foundColumns);
    }

    protected function validateGeographicLevels($sheet, array $foundColumns): void
    {
        $geographicLevelColumn = 'Geographic Level';

        if (! isset($foundColumns[$geographicLevelColumn])) {
            return;
        }

        $validLevels = config('psgc.validation.valid_geographic_levels');
        $validLevels = array_map('strtolower', $validLevels);

        $invalidLevels = [];

        foreach ($sheet->getRowIterator() as $rowIndex => $row) {
            if ($rowIndex === 1) {
                continue; // Skip header row
            }

            $cell = $row->getCell($geographicLevelColumn);

            if (! $cell) {
                continue;
            }

            $level = (string) $cell->getValue();

            if ($level) {
                $normalizedLevel = strtolower($level);

                if (! in_array($normalizedLevel, $validLevels, true)) {
                    $invalidLevels[$level] = true;
                }
            }
        }

        if ($invalidLevels) {
            $invalidLevelList = implode(', ', array_keys($invalidLevels));
            $this->errors[] = "Invalid Geographic Levels found: {$invalidLevelList}. Valid levels: ".implode(', ', $validLevels);
        }
    }

    public function isValid(): bool
    {
        return $this->isValid;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}

class RowLimitFilter implements \PhpOffice\PhpSpreadsheet\Reader\IReadFilter
{
    protected int $maxRows;

    public function __construct(int $maxRows)
    {
        $this->maxRows = $maxRows;
    }

    public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
    {
        return $row <= $this->maxRows;
    }
}
