<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;

class DebugPsgcData extends Command
{
    protected $signature = 'psgc:debug {--path= : Path to PSGC Excel file}';

    protected $description = 'Debug PSGC Excel file structure';

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

        $this->info("Debugging file: $path");

        try {
            $spreadsheet = IOFactory::load($path);
            $sheet = $spreadsheet->getSheetByName('PSGC');

            if (!$sheet) {
                $this->error('PSGC sheet not found');

                return 1;
            }

            $data = $sheet->toArray();

            if (empty($data)) {
                $this->error('Sheet is empty');

                return 1;
            }

            $this->info('First 20 rows (showing relevant columns):');
            $this->newLine();

            $headers = $data[0];

            // Find column indices
            $codeIndex = array_search('10-digit PSGC', $headers);
            $nameIndex = array_search('Name', $headers);
            $geoLevelIndex = array_search('Geographic Level', $headers);
            $corrCodeIndex = array_search('Correspondence Code', $headers);

            for ($i = 1; $i <= min(20, count($data) - 1); $i++) {
                $row = $data[$i];

                $this->line(sprintf(
                    "%s | %s | %s | %s",
                    $row[$geoLevelIndex] ?? '-',
                    $row[$corrCodeIndex] ?? '-',
                    $row[$codeIndex] ?? '-',
                    $row[$nameIndex] ?? '-'
                ));
            }

            $this->newLine();
            $this->info("Total rows: ".(count($data) - 1));

            return 0;
        } catch (\Exception $e) {
            $this->error("Error: {$e->getMessage()}");

            return 1;
        }
    }
}
