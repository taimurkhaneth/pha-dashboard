<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\Project;
use App\Models\Allottee;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Illuminate\Support\Str;

class ImportKurriData extends Command
{
    protected $signature = 'import:kurri {--file=Kurri Houses Updated Final.xlsx : The excel file to import}';
    protected $description = 'Import Kurri Houses data into a new Project';

    public function handle()
    {
        $filename = $this->option('file');
        $filepath = base_path($filename);

        if (!file_exists($filepath)) {
            $this->error("File not found: $filepath");
            return 1;
        }

        $this->info("Creating/Finding 'Kurri Road Houses' Project...");
        $project = Project::firstOrCreate(
            ['code' => 'PHAF-KURRI'],
            [
                'name' => 'Kurri Road Houses',
                'full_name' => 'PHAF Kurri Road Housing Scheme',
                'city' => 'Islamabad',
                'maintenance_rate' => 0.83, // Setting to 0.83 as per excel
                'ww_amount' => 10000,
                'ww_cutoff_date' => '2023-07-23',
                'delay_percent' => 10,
                'bank_account_no' => 'PHA-KURRI-NBP-001',
                'bank_name' => 'National Bank of Pakistan',
                'bank_branch' => 'Islamabad',
                'total_units' => 0,
                'is_active' => false,
                'description' => 'Kurri Road Houses project imported from Excel'
            ]
        );

        $this->info("Project ID: {$project->id}. Loading Excel...");
        $spreadsheet = IOFactory::load($filepath);
        
        $sheetNames = $spreadsheet->getSheetNames();
        $count = 0;

        // We temporarily disable the global scope to ensure we can insert/find safely
        Allottee::withoutGlobalScope('project');

        foreach ($sheetNames as $sheetName) {
            if (strtolower($sheetName) === 'summary') continue;
            if (!str_contains(strtolower($sheetName), 'cat')) continue;

            $this->info("Parsing sheet: $sheetName");
            $worksheet = $spreadsheet->getSheetByName($sheetName);
            $rows = $worksheet->toArray(null, true, true, true);
            
            $totalRows = count($rows);
            $this->output->progressStart($totalRows - 1);

            foreach ($rows as $index => $row) {
                // skip header (row 1)
                if ($index == 1) continue;

                // Columns (A to R)
                // B = MEMBERSHIP NO
                $membership_no = trim($row['B'] ?? '');
                if (empty($membership_no)) {
                    $this->output->progressAdvance();
                    continue;
                }

                $name = trim($row['C'] ?? '');
                $cnic = trim($row['D'] ?? '');
                $lane_no = trim($row['E'] ?? '');
                $house_no = trim($row['F'] ?? '');
                $category = trim($row['G'] ?? '');
                $size = (int)($row['H'] ?? 0);
                
                // Dates can be excel dates or strings
                $effective_date_raw = $row['J'] ?? null;
                $possession_date = null;
                if (is_numeric($effective_date_raw)) {
                    $possession_date = Date::excelToDateTimeObject($effective_date_raw)->format('Y-m-d');
                } else if (!empty($effective_date_raw)) {
                    try {
                        $possession_date = \Carbon\Carbon::parse($effective_date_raw)->format('Y-m-d');
                    } catch (\Exception $e) {}
                }

                $due_months = (int)($row['L'] ?? 0);
                $mailing_address = trim($row['O'] ?? '');
                $cell = trim($row['P'] ?? '');
                $status = strtolower(trim($row['Q'] ?? ''));

                $handed_over = str_contains($status, 'finished') ? 'Yes' : 'No';

                // Calculate financials based on project rate
                $maintenance_charges = $size * $project->maintenance_rate * $due_months;
                $watch_ward = 0; // Or if you want to implement it based on possession date:
                if ($project->ww_amount > 0) {
                    if (!$possession_date || $possession_date >= $project->ww_cutoff_date) {
                        $watch_ward = $project->ww_amount;
                    }
                }
                
                $fine = 0;
                if ($due_months > 0) {
                    $fine = ($maintenance_charges + $watch_ward) * ($project->delay_percent / 100);
                }
                
                $total_charges = $maintenance_charges + $watch_ward + $fine;

                // Determine City
                $city = 'Unknown';
                $addr = strtolower($mailing_address);
                $cities = ['islamabad', 'rawalpindi', 'lahore', 'karachi', 'peshawar', 'quetta', 'multan'];
                foreach ($cities as $c) {
                    if (str_contains($addr, $c)) {
                        $city = ucfirst($c);
                        break;
                    }
                }

                Allottee::updateOrCreate(
                    ['project_id' => $project->id, 'membership_no' => $membership_no],
                    [
                        'file_no' => $membership_no, // Mapped file_no to membership_no
                        'name' => $name,
                        'cnic' => $cnic,
                        'block_no' => 'Lane ' . $lane_no,
                        'flat_no' => 'House ' . $house_no,
                        'floor' => 'N/A', // Houses don't have floors
                        'category' => $category,
                        'covered_area' => $size,
                        'possession_date' => $possession_date,
                        'due_months' => $due_months,
                        'maintenance_charges' => $maintenance_charges,
                        'watch_ward_charges' => $watch_ward,
                        'fine' => $fine,
                        'total_maintenance_charges' => $total_charges,
                        'mailing_address' => $mailing_address,
                        'cell' => $cell,
                        'city' => $city,
                        'handed_over' => $handed_over,
                        'temporary_occupancy' => 'No',
                    ]
                );

                $count++;
                $this->output->progressAdvance();
            }
            $this->output->progressFinish();
        }
        
        $project->total_units = $count;
        $project->save();

        $this->info("Import completed successfully! $count records imported to Kurri Road Houses project.");
        return 0;
    }
}
