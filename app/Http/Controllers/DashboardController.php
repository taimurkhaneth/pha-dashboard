<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\Allottee;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $fiscalYear = $request->get('fy', '2025-26');
        
        $settings = Setting::all()->keyBy('key');


        // ── BILLING RATES (from settings) ──────────────────────────────
        $maintenanceRate = (float) Setting::getValue('maintenance_rate_per_sqft', 3.07);
        $wwAmount        = (float) Setting::getValue('watch_ward_amount', 10000);
        $wwCutoff        = Setting::getValue('watch_ward_cutoff_date', '2023-07-23');
        $delayPct        = (float) Setting::getValue('delay_charge_percent', 10);
        
        $activeProject = \App\Models\Project::active();
        if ($activeProject) {
            $maintenanceRate = $activeProject->maintenance_rate;
            $wwAmount        = $activeProject->ww_amount;
            $wwCutoff        = $activeProject->ww_cutoff_date;
            $delayPct        = $activeProject->delay_percent;
        }

        // ── CATEGORY STATS (Dynamic) ───────────────────────────────────
        $categoryStatsRaw = Allottee::select(
            'category', 
            DB::raw('COUNT(*) as count'), 
            DB::raw('MAX(covered_area) as typical_area'),
            DB::raw('SUM(covered_area) as total_area')
        )->whereNotNull('category')->where('category', '!=', '')->groupBy('category')->orderBy('category')->get();

        $totalAllottees = $categoryStatsRaw->sum('count');
        $totalMonthlyBilling = 0;

        $categoryStats = [];
        foreach ($categoryStatsRaw as $cat) {
            $catMonthly = $cat->total_area * $maintenanceRate;
            $totalMonthlyBilling += $catMonthly;
            $categoryStats[] = (object) [
                'name' => $cat->category,
                'count' => $cat->count,
                'typical_area' => $cat->typical_area,
                'monthly_per_unit' => $cat->typical_area * $maintenanceRate,
                'yearly_per_unit' => $cat->typical_area * $maintenanceRate * 12,
                'total_monthly' => $catMonthly
            ];
        }
        
        // ── YEARLY ACTUAL VS FORECAST ─────────────────────────────────
        $forecastYearly = $totalMonthlyBilling * 12;
        $actualYearly   = (float) Bill::where('bill_month', 'like', date('Y').'-%')->sum('paid_amount');


        // ── W&W ELIGIBILITY BREAKDOWN ──────────────────────────────────
        $allAllottees = Allottee::select('possession_date')->get();
        $totalWWRecoverable = 0;
        $wwBeforeCount = 0;
        $wwAfterCount = 0;
        $wwNullCount = 0;
        
        $wwStartDate = Carbon::create(2023, 7, 1);
        $now = Carbon::now();

        foreach ($allAllottees as $a) {
            $endDate = $a->possession_date ? clone $a->possession_date : $now;
            if ($a->possession_date) {
                if ($a->possession_date->lt($wwStartDate)) {
                    $wwBeforeCount++;
                } else {
                    $wwAfterCount++;
                }
            } else {
                $wwNullCount++;
            }
            
            $months = 0;
            if ($endDate->gt($wwStartDate)) {
                $months = $wwStartDate->diffInMonths($endDate);
            }
            $totalWWRecoverable += ($months * $wwAmount);
        }
        
        $wwBeforeAmount = 0;
        $wwAfterAmount = 0; // We will omit exact splits for dashboard simplification
        $wwNullAmount = 0;

        // ── BILLING SUMMARY ────────────────────────────────────────────
        $subtotal          = $totalMonthlyBilling + $totalWWRecoverable;
        $totalDelayCharges = $subtotal * $delayPct / 100;
        $grandTotal        = $subtotal + $totalDelayCharges;

        // ── PAYMENT VS PENDING (Filtered by Fiscal Year) ───────────────
        $fyStart = substr($fiscalYear, 0, 4) . '-07'; // e.g. 2025-07
        $fyEnd   = "20" . substr($fiscalYear, -2) . '-06'; // e.g. 2026-06
        
        $billsInFy = Bill::whereBetween('bill_month', [$fyStart, $fyEnd]);
        $totalPaid    = (float) $billsInFy->sum('paid_amount');
        $totalPending = (float) $billsInFy->sum('total_amount') - $totalPaid;

        // ── DEFAULTERS ─────────────────────────────────────────────────
        $threshold       = (int) Setting::getValue('defaulter_months_threshold', 3);
        $topCount        = (int) Setting::getValue('defaulter_top_count', 10);
        $totalDefaulters = Allottee::where('due_months', '>=', $threshold)->count();
        $defaulters      = Allottee::where('due_months', '>=', $threshold)
                                    ->orderByDesc('total_maintenance_charges')
                                    ->limit($topCount)->get();

        // ── MONTHLY BILLING TREND (last 6 months) ─────────────────────
        $trendData = [];
        $endDate   = Carbon::create(2025, 5, 31);
        for ($i = 5; $i >= 0; $i--) {
            $month    = $endDate->copy()->subMonths($i);
            $monthEnd = $month->copy()->endOfMonth()->format('Y-m-d');

            $monthData = [
                'label' => $month->format('M-y'),
                'total' => 0
            ];
            
            foreach ($categoryStats as $cat) {
                $totalForCat = Allottee::where('category', $cat->name)
                    ->where(function($q) use ($monthEnd) {
                        $q->whereNull('possession_date')
                          ->orWhere('possession_date', '<=', $monthEnd);
                    })->sum('covered_area') * $maintenanceRate;
                    
                $monthData[$cat->name] = round($totalForCat);
                $monthData['total'] += round($totalForCat);
            }
            $trendData[] = $monthData;
        }

        // ── CITY-WISE ─────────────────────────────────────────────────
        $cityData = Allottee::select(
            'city',
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(covered_area) * ' . $maintenanceRate . ' as monthly_billing'),
            DB::raw('SUM(covered_area) * ' . $maintenanceRate . ' * 12 as yearly_billing')
        )->groupBy('city')->orderByDesc('count')->get();



        // ── SAMPLE ALLOTTEES (bottom table, top 15 by total) ──────────
        $sampleAllottees = Allottee::orderByDesc('total_maintenance_charges')->limit(15)->get();

        // ── BPS + DUE MONTHS + POSSESSION (for charts) ───────────────
        $allBps = Allottee::select('bps')->get();
        $govtCount = 0;
        $gpCount = 0;
        foreach ($allBps as $b) {
            $val = strtoupper(trim($b->bps ?? ''));
            if ($val === '') continue;
            if ($val === 'GP' || str_contains($val, 'GENERAL')) {
                $gpCount++;
            } else {
                $num = (int) preg_replace('/[^0-9]/', '', $val);
                if ($num >= 1 && $num <= 22) {
                    $govtCount++;
                } else {
                    $gpCount++;
                }
            }
        }
        $bpsDistribution = [
            (object)['label' => 'Govt Employees (BPS)', 'count' => $govtCount],
            (object)['label' => 'General Public (GP)', 'count' => $gpCount]
        ];
        $monthsDistribution = Allottee::select('due_months', DB::raw('count(*) as count'))
                                ->whereNotNull('due_months')->groupBy('due_months')
                                ->orderBy('due_months')->get();
        $possessionTimeline = Allottee::select(
                                DB::raw("strftime('%Y', possession_date) as year"),
                                DB::raw("strftime('%Y-%m', possession_date) as month"),
                                DB::raw('count(*) as count')
                                )
                                ->whereNotNull('possession_date')
                                ->groupBy('month')
                                ->orderBy('month')
                                ->get();

        // ── BLOCK-WISE ANALYTICS ──────────────────────────────────────
        $blockData = Allottee::select(
            'category',
            'block_no',
            DB::raw('COUNT(*) as total'),
            DB::raw("SUM(CASE WHEN temporary_occupancy IS NOT NULL AND temporary_occupancy != '' AND temporary_occupancy != '0' THEN 1 ELSE 0 END) as temp_occ"),
            DB::raw("SUM(CASE WHEN handed_over IS NOT NULL AND handed_over != '' AND handed_over != '0' THEN 1 ELSE 0 END) as handed_over"),
            DB::raw("SUM(CASE WHEN transfer IS NOT NULL AND transfer != '' AND transfer != '0' THEN 1 ELSE 0 END) as transferred"),
            DB::raw('SUM(covered_area) * ' . $maintenanceRate . ' as monthly_billing')
        )
        ->whereNotNull('block_no')
        ->groupBy('category', 'block_no')
        ->orderBy('category')
        ->orderByRaw('CAST(block_no AS INTEGER) ASC')
        ->get();

        // Block KPIs
        $blockWithMaxAllottees = $blockData->sortByDesc('total')->first();
        $totalHandedOver       = Allottee::whereNotNull('handed_over')
            ->where('handed_over', '!=', '')->where('handed_over', '!=', '0')->count();
        $totalTempOcc          = Allottee::whereNotNull('temporary_occupancy')
            ->where('temporary_occupancy', '!=', '')->where('temporary_occupancy', '!=', '0')->count();
        $totalTransferred      = Allottee::whereNotNull('transfer')
            ->where('transfer', '!=', '')->where('transfer', '!=', '0')->count();

        return view('dashboard.index', compact(
            'totalAllottees', 'categoryStats',
            'maintenanceRate', 'wwAmount', 'wwCutoff', 'delayPct',
            'totalMonthlyBilling', 'forecastYearly', 'actualYearly',
            'wwBeforeCount', 'wwAfterCount', 'wwNullCount',
            'wwBeforeAmount', 'wwAfterAmount', 'wwNullAmount', 'totalWWRecoverable',
            'subtotal', 'totalDelayCharges', 'grandTotal',
            'totalPaid', 'totalPending',
            'threshold', 'topCount', 'totalDefaulters', 'defaulters',
            'trendData', 'cityData',
            'sampleAllottees', 'bpsDistribution', 'monthsDistribution', 'possessionTimeline',
            'settings',
            // block analytics
            'blockData', 'blockWithMaxAllottees',
            'totalHandedOver', 'totalTempOcc', 'totalTransferred',
            'fiscalYear'
        ));
    }
}
