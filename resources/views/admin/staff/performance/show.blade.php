@extends('layouts.app')
@section('title', 'Performance — ' . $staff->name)
@section('page-title', $staff->name . ' — Performance Detail')

@section('content')

{{-- ── Back + Date Filter ───────────────────────────────────────────────── --}}
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <a href="{{ route('admin.staff.performance.index') }}" class="btn btn-outline-secondary btn-sm fw-bold">
        <i class="bi bi-arrow-left me-1"></i>All Staff
    </a>
    <form method="GET" action="{{ route('admin.staff.performance.show', $staff) }}"
          class="d-flex align-items-center gap-2 flex-wrap">
        <input type="date" name="start_date" class="form-control form-control-sm"
               value="{{ $startDate->format('Y-m-d') }}" style="width:145px;">
        <span class="text-muted">→</span>
        <input type="date" name="end_date" class="form-control form-control-sm"
               value="{{ $endDate->format('Y-m-d') }}" style="width:145px;">
        <button type="submit" class="btn btn-success btn-sm fw-bold">
            <i class="bi bi-funnel me-1"></i>Apply
        </button>
    </form>
</div>

{{-- ── Staff Info Card ─────────────────────────────────────────────────── --}}
<div class="chart-card mb-4">
    <div class="d-flex align-items-center gap-4 flex-wrap">
        <div class="d-flex align-items-center justify-content-center rounded-circle fw-bold text-white"
             style="width:64px; height:64px; font-size:24px; background:linear-gradient(135deg,#1B6B35,#C9A84C); flex-shrink:0;">
            {{ strtoupper(substr($staff->name, 0, 1)) }}
        </div>
        <div class="flex-grow-1">
            <h5 class="fw-bold mb-1">{{ $staff->name }}</h5>
            <span class="badge bg-light text-dark border me-2">{{ $staff->designation }}</span>
            @if($staff->phone)<span class="text-muted" style="font-size:13px;"><i class="bi bi-telephone me-1"></i>{{ $staff->phone }}</span>@endif
            @if($staff->joining_date)
                <span class="text-muted ms-3" style="font-size:13px;"><i class="bi bi-calendar-event me-1"></i>Joined {{ $staff->joining_date->format('d M Y') }}</span>
            @endif
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.staff.attendance.index', ['date' => now()->format('Y-m-d')]) }}"
               class="btn btn-sm btn-outline-primary">
                <i class="bi bi-calendar3 me-1"></i>Attendance
            </a>
            <a href="{{ route('admin.staff.payroll.index', ['month' => now()->format('Y-m')]) }}"
               class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-calculator me-1"></i>Payroll
            </a>
        </div>
    </div>
</div>

{{-- ── KPI Cards ───────────────────────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-md-2 col-6">
        <div class="kpi-card text-center">
            <div class="kpi-value">{{ $total }}</div>
            <div class="kpi-label">Total Assigned</div>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="kpi-card text-center">
            <div class="kpi-value text-success">{{ $resolved }}</div>
            <div class="kpi-label">Resolved</div>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="kpi-card text-center">
            <div class="kpi-value {{ $pending > 0 ? 'text-warning' : 'text-muted' }}">{{ $pending }}</div>
            <div class="kpi-label">In Progress</div>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="kpi-card text-center">
            <div class="kpi-value text-{{ $resolutionRate >= 75 ? 'success' : ($resolutionRate >= 50 ? 'warning' : 'danger') }}">
                {{ $resolutionRate }}%
            </div>
            <div class="kpi-label">Resolution Rate</div>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="kpi-card text-center">
            <div class="kpi-value text-info" style="font-size:20px;">
                @if($avgHours > 0)
                    {{ $avgHours < 24 ? $avgHours . 'h' : round($avgHours / 24, 1) . 'd' }}
                @else
                    —
                @endif
            </div>
            <div class="kpi-label">Avg Resolution</div>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="kpi-card text-center">
            <div class="kpi-value text-{{ $satisfactionRate >= 75 ? 'success' : ($satisfactionRate >= 50 ? 'warning' : 'danger') }}">
                {{ $satisfactionRate > 0 ? $satisfactionRate . '%' : '—' }}
            </div>
            <div class="kpi-label">Satisfaction</div>
        </div>
    </div>
</div>

{{-- ── Monthly Trend Chart + Attendance Summary ─────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="chart-card">
            <h6 class="section-title"><i class="bi bi-graph-up me-2 text-primary"></i>6-Month Activity Trend</h6>
            <div id="trendChart"></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="chart-card h-100">
            <h6 class="section-title">
                <i class="bi bi-calendar-check me-2 text-success"></i>
                This Month Attendance
                <small class="text-muted fw-normal">({{ now()->format('F Y') }})</small>
            </h6>
            @if($attendanceSummary->count() > 0)
                @php
                    $aPresent  = $attendanceSummary->where('status', 'present')->count();
                    $aAbsent   = $attendanceSummary->where('status', 'absent')->count();
                    $aHalf     = $attendanceSummary->where('status', 'half_day')->count();
                    $aLeave    = $attendanceSummary->where('status', 'on_leave')->count();
                    $aHoliday  = $attendanceSummary->where('status', 'holiday')->count();
                    $aTotal    = $attendanceSummary->count();
                @endphp
                <div class="row g-2">
                    @foreach([
                        ['Present',  $aPresent,  'success'],
                        ['Absent',   $aAbsent,   'danger'],
                        ['Half Day', $aHalf,     'warning'],
                        ['Leave',    $aLeave,    'info'],
                        ['Holiday',  $aHoliday,  'secondary'],
                    ] as [$lbl, $val, $clr])
                    <div class="col-6">
                        <div class="d-flex align-items-center gap-2 p-2 rounded-3 bg-light">
                            <span class="badge bg-{{ $clr }}" style="width:10px;height:10px;border-radius:50%;padding:0;">&nbsp;</span>
                            <span style="font-size:12px;">{{ $lbl }}</span>
                            <strong class="ms-auto text-{{ $clr }}">{{ $val }}</strong>
                        </div>
                    </div>
                    @endforeach
                    <div class="col-12 mt-1">
                        <small class="text-muted">{{ $aTotal }} days recorded of {{ now()->day }} days this month</small>
                    </div>
                </div>
            @else
                <p class="text-muted" style="font-size:13px;">No attendance recorded this month yet.</p>
                <a href="{{ route('admin.staff.attendance.index') }}" class="btn btn-sm btn-outline-primary">
                    Mark Attendance
                </a>
            @endif
        </div>
    </div>
</div>

{{-- ── Recent Complaints Table ─────────────────────────────────────────── --}}
<div class="chart-card">
    <h6 class="section-title mb-3">
        <i class="bi bi-list-task me-2 text-primary"></i>
        Complaints ({{ $startDate->format('d M Y') }} → {{ $endDate->format('d M Y') }})
        <span class="badge bg-light text-dark border ms-2">{{ $complaints->count() }}</span>
    </h6>

    @if($complaints->isEmpty())
        <p class="text-muted text-center py-4">No complaints assigned in this period.</p>
    @else
    <div class="table-responsive">
        <table class="table data-table align-middle" style="font-size:12.5px;">
            <thead>
                <tr>
                    <th>Complaint #</th>
                    <th>Date</th>
                    <th>Category</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Resolved In</th>
                    <th>Satisfaction</th>
                </tr>
            </thead>
            <tbody>
                @foreach($complaints as $c)
                <tr>
                    <td><a href="{{ route('admin.complaints.show', $c) }}" class="fw-bold text-decoration-none">{{ $c->complaint_number }}</a></td>
                    <td class="text-muted">{{ $c->created_at->format('d M Y') }}</td>
                    <td>{{ $c->category?->name ?? '—' }}</td>
                    <td>
                        <span class="badge {{ $c->priorityBadgeClass }}" style="font-size:10px;">
                            {{ strtoupper($c->priority) }}
                        </span>
                    </td>
                    <td>
                        <span class="badge {{ $c->statusBadgeClass }}" style="font-size:10px;">
                            {{ strtoupper(str_replace('_', ' ', $c->status)) }}
                        </span>
                    </td>
                    <td class="text-muted">
                        @if($c->resolved_at)
                            @php $h = $c->created_at->diffInHours($c->resolved_at); @endphp
                            {{ $h < 24 ? $h . ' hrs' : round($h/24, 1) . ' days' }}
                        @else
                            —
                        @endif
                    </td>
                    <td>
                        @if($c->status === 'closed')
                            @if($c->satisfaction_confirmed)
                                <span class="text-success"><i class="bi bi-emoji-smile"></i> Satisfied</span>
                            @else
                                <span class="text-danger"><i class="bi bi-emoji-frown"></i> Unsatisfied</span>
                            @endif
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const trend = @json($monthlyTrend);
    new ApexCharts(document.querySelector('#trendChart'), {
        chart: { type: 'area', height: 250, toolbar: { show: false } },
        series: [
            { name: 'Assigned',  data: trend.map(t => t.assigned) },
            { name: 'Resolved',  data: trend.map(t => t.resolved) },
        ],
        xaxis: { categories: trend.map(t => t.label), labels: { style: { fontSize: '10px', fontWeight: 600 } } },
        colors: ['#C9A84C', '#1B6B35'],
        stroke: { curve: 'smooth', width: 3 },
        fill: { type: 'gradient', gradient: { opacityFrom: 0.3, opacityTo: 0.03 } },
        legend: { position: 'top', fontWeight: 600 },
        grid: { borderColor: '#f1f5f9' },
        tooltip: { y: { formatter: v => v + ' complaints' } },
    }).render();
});
</script>
@endpush

@endsection
