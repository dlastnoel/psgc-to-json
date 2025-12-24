<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CheckPsgcColumns extends Command
{
    protected $signature = 'psgc:check-columns {--path= : Path to PSGC Excel file}';

    protected $description = 'Check column headers in PSGC Excel file';

    public function handle(): int
    {
        $path = $this->option('path');

        if (!$path) {
            $path = storage_path('app/psgc/PSGC-4Q-2025-Publication-Datafile.xlsx');
        }

        if (!file_exists($path)) {
            $this->error("File not found: $path");

            return 1;
        }

        $this->info("Checking file: $path");

        try {
            $spreadsheet = IOFactory::load($path);
            $sheet = $spreadsheet->getSheetByName('PSGC');

            if (!$sheet) {
                $this->error('PSGC sheet not found');

                return 1;
            }

            // Convert to array to easily access header row
            $data = $sheet->toArray();

            if (empty($data)) {
                $this->error('Sheet is empty');

                return 1;
            }

            $this->info('Column Headers in PSGC sheet:');
            $this->newLine();

            $headerRow = $data[0];

            foreach ($headerRow as $cell) {
                if ($cell !== null) {
                    $this->line("  - $cell");
                }
            }

            $this->newLine();
            $this->info("Total rows in sheet: ".count($data));

            return 0;
        } catch (\Exception $e) {
            $this->error("Error: {$e->getMessage()}");

            return 1;
        }
    }
}
