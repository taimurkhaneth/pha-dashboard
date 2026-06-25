@extends('layouts.app')
@section('title', 'Staff Performance Report')
@section('page-title', 'Staff Performance Report')

@section('content')

{{-- ── Date Range Filter ───────────────────────────────────────────────── --}}
<div class="chart-card mb-4">
    <form method="GET" action="{{ route('admin.staff.performance.index') }}" class="row g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label fw-bold" style="font-size:11px; text-transform:uppercase; color:#64748b;">From Date</label>
            <input type="date" name="start_date" class="form-control"
                   value="{{ $startDate->format('Y-m-d') }}">
        </div>
        <div class="col-md-3">
            <label class="form-label fw-bold" style="font-size:11px; text-transform:uppercase; color:#64748b;">To Date</label>
            <input type="date" name="end_date" class="form-control"
                   value="{{ $endDate->format('Y-m-d') }}">
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-success fw-bold w-100">
                <i class="bi bi-funnel me-1"></i>Apply
            </button>
        </div>
        <div class="col-md-4 d-flex align-items-end">
            <small class="text-muted">
                Showing <strong>{{ $startDate->format('d M Y') }}</strong> → <strong>{{ $endDate->format('d M Y') }}</strong>
                &nbsp;·&nbsp;
                <a href="{{ route('admin.staff.performance.index') }}" class="text-muted">Reset to this month</a>
            </small>
        </div>
    </form>
</div>

{{-- ── Performance Table ───────────────────────────────────────────────── --}}
<div class="chart-card mb-4">
    <h6 class="section-title mb-3">
        <i class="bi bi-bar-chart-line me-2 text-primary"></i>Performance Overview
    </h6>

    <div class="table-responsive">
        <table class="table data-table align-middle">
            <thead>
                <tr>
                    <th>Staff Name</th>
                    <th>Designation</th>
                    <th class="text-center">Assigned</th>
                    <th class="text-center">Resolved</th>
                    <th class="text-center">Pending</th>
                    <th class="text-center">Resolution Rate</th>
                    <th class="text-center">Avg. Time</th>
                    <th class="text-center">Satisfaction</th>
                    <th class="text-end">Detail</th>
                </tr>
            </thead>
            <tbody>
                @forelse($performanceData as $data)
                <tr>
                    <td class="fw-bold">{{ $data['staff']->name }}</td>
                    <td>
                        <span class="badge bg-light text-dark border">{{ $data['staff']->designation }}</span>
                    </td>
                    <td class="text-center">
                        <span class="fw-bold" style="font-size:16px;">{{ $data['total'] }}</span>
                    </td>
                    <td class="text-center">
                        <span class="text-success fw-bold">{{ $data['resolved'] }}</span>
                    </td>
                    <td class="text-center">
                        <span class="{{ $data['pending'] > 0 ? 'text-warning fw-bold' : 'text-muted' }}">
                            {{ $data['pending'] }}
                        </span>
                    </td>
                    <td class="text-center">
                        @php $rate = $data['resolution_rate']; @endphp
                        <div class="d-flex align-items-center justify-content-center gap-2">
                            <div class="progress flex-grow-1" style="height:7px; max-width:70px;">
                                <div class="progress-bar bg-{{ $rate >= 75 ? 'success' : ($rate >= 50 ? 'warning' : 'danger') }}"
                                     style="width:{{ $rate }}%"></div>
                            </div>
                            <span class="fw-bold text-{{ $rate >= 75 ? 'success' : ($rate >= 50 ? 'warning' : 'danger') }}"
                                  style="font-size:13px; min-width:40px;">
                                {{ $rate }}%
                            </span>
                        </div>
                    </td>
                    <td class="text-center text-muted" style="font-size:12px;">
                        @if($data['avg_hours'] > 0)
                            {{ $data['avg_hours'] < 24
                                ? $data['avg_hours'] . ' hrs'
                                : round($data['avg_hours'] / 24, 1) . ' days' }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="text-center">
                        @php $sat = $data['satisfaction']; @endphp
                        @if($sat > 0)
                            <span class="badge px-3 py-2 {{ $sat >= 75 ? 'bg-success' : ($sat >= 50 ? 'bg-warning text-dark' : 'bg-danger') }}">
                                {{ $sat }}%
                            </span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <a href="{{ route('admin.staff.performance.show', $data['staff']) }}?start_date={{ $startDate->format('Y-m-d') }}&end_date={{ $endDate->format('Y-m-d') }}"
                           class="btn btn-sm btn-outline-primary fw-bold">
                            <i class="bi bi-eye me-1"></i>Details
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center py-5 text-muted">
                        <i class="bi bi-people display-5 d-block mb-3 opacity-25"></i>
                        No active maintenance staff found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($performanceData->count() > 1)
{{-- ── Charts ─────────────────────────────────────────────────────────── --}}
<div class="row g-3">
    <div class="col-lg-7">
        <div class="chart-card">
            <h6 class="section-title">
                <i class="bi bi-bar-chart-fill me-2 text-primary"></i>Assigned vs Resolved
            </h6>
            <div id="staffComparisonChart"></div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="chart-card">
            <h6 class="section-title">
                <i class="bi bi-trophy-fill me-2 text-warning"></i>Resolution Rate
            </h6>
            <div id="resolutionRateChart"></div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const data  = @json($chartData->values());
    const names = data.map(d => d.name);

    new ApexCharts(document.querySelector('#staffComparisonChart'), {
        chart: { type: 'bar', height: 260, toolbar: { show: false } },
        series: [
            { name: 'Assigned', data: data.map(d => d.total) },
            { name: 'Resolved', data: data.map(d => d.resolved) },
        ],
        xaxis: { categories: names, labels: { style: { fontSize: '11px', fontWeight: 600 } } },
        colors: ['#C9A84C', '#1B6B35'],
        plotOptions: { bar: { borderRadius: 4, columnWidth: '55%', grouped: true } },
        legend: { position: 'top', fontWeight: 600 },
        grid: { borderColor: '#f1f5f9' },
        dataLabels: { enabled: false },
    }).render();

    new ApexCharts(document.querySelector('#resolutionRateChart'), {
        chart: { type: 'bar', height: 260, toolbar: { show: false } },
        series: [{ name: 'Resolution %', data: data.map(d => d.rate) }],
        xaxis: { categories: names, labels: { style: { fontSize: '11px', fontWeight: 600 } } },
        colors: ['#2563eb'],
        plotOptions: { bar: { borderRadius: 4, horizontal: true, barHeight: '55%' } },
        dataLabels: { enabled: true, formatter: v => v + '%' },
        yaxis: { max: 100 },
        grid: { borderColor: '#f1f5f9' },
    }).render();
});
</script>
@endpush
@endif

@endsection
