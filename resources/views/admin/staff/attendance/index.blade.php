@extends('layouts.app')
@section('title', 'Staff Attendance')
@section('page-title', 'Maintenance Staff Attendance')

@section('content')

{{-- ── Date Navigation Bar ─────────────────────────────────────────────── --}}
<div class="chart-card mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div>
            <h6 class="section-title mb-1"><i class="bi bi-calendar3 me-2 text-primary"></i>Mark Daily Attendance</h6>
            <small class="text-muted">Select a date, set status for each staff member, then click Save.</small>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="{{ route('admin.staff.attendance.index', ['date' => $selectedDate->copy()->subDay()->format('Y-m-d')]) }}"
               class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-chevron-left"></i> Prev
            </a>
            <form method="GET" action="{{ route('admin.staff.attendance.index') }}">
                <input type="date" name="date" class="form-control form-control-sm"
                       value="{{ $selectedDate->format('Y-m-d') }}"
                       onchange="this.form.submit()" style="width: 150px;">
            </form>
            <a href="{{ route('admin.staff.attendance.index', ['date' => $selectedDate->copy()->addDay()->format('Y-m-d')]) }}"
               class="btn btn-sm btn-outline-secondary">
                Next <i class="bi bi-chevron-right"></i>
            </a>
            @if(!$selectedDate->isToday())
            <a href="{{ route('admin.staff.attendance.index') }}"
               class="btn btn-sm btn-success fw-bold">
                <i class="bi bi-calendar-check me-1"></i>Today
            </a>
            @endif
        </div>
    </div>
</div>

{{-- ── Status Legend ────────────────────────────────────────────────────── --}}
<div class="d-flex align-items-center gap-3 mb-3 flex-wrap px-1" style="font-size: 12px; font-weight: 600;">
    <span><span class="badge bg-success me-1">P</span> Present</span>
    <span><span class="badge bg-danger me-1">A</span> Absent</span>
    <span><span class="badge bg-warning text-dark me-1">½</span> Half Day</span>
    <span><span class="badge bg-info text-dark me-1">L</span> On Leave</span>
    <span><span class="badge bg-secondary me-1">H</span> Holiday</span>
    <span class="text-danger"><i class="bi bi-lock-fill me-1"></i> Locked (Payroll Paid)</span>
</div>

@if($staff->isEmpty())
<div class="chart-card text-center py-5">
    <i class="bi bi-people text-muted" style="font-size: 3rem; display:block;"></i>
    <p class="text-muted mt-3 mb-0">No active maintenance staff found.<br>
        <a href="{{ route('admin.complaints.staff.index') }}" class="fw-bold">Add staff members</a> first.</p>
</div>
@else

{{-- ── Attendance Form ───────────────────────────────────────────────────── --}}
<form method="POST" action="{{ route('admin.staff.attendance.save') }}">
    @csrf
    <input type="hidden" name="date" value="{{ $selectedDate->format('Y-m-d') }}">

    <div class="chart-card mb-4">
        <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
            <h6 class="section-title mb-0">
                <i class="bi bi-clipboard2-check me-2 text-success"></i>
                {{ $selectedDate->format('l, d F Y') }}
                @php $allLocked = count($lockedStaffIds) === $staff->count(); @endphp
                @if($allLocked)
                    <span class="badge bg-danger ms-2"><i class="bi bi-lock-fill me-1"></i>All Locked – Payroll Paid</span>
                @endif
            </h6>
            @if(!$allLocked)
            <button type="submit" class="btn btn-success fw-bold px-4">
                <i class="bi bi-floppy me-1"></i> Save Attendance
            </button>
            @endif
        </div>

        <div class="table-responsive">
            <table class="table data-table align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width:35px;">#</th>
                        <th>Staff Name</th>
                        <th>Designation</th>
                        <th>Status for {{ $selectedDate->format('d M') }}</th>
                        <th>Remarks</th>
                        <th class="text-center">{{ $monthLabel }} Summary</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($staff as $i => $member)
                        @php
                            $record       = $existingAttendance->get($member->id);
                            $currentStatus = $record ? $record->status : null;
                            $isLocked     = in_array($member->id, $lockedStaffIds);
                            $mRecords     = $monthlySummary->get($member->id, collect());
                            $mPresent     = $mRecords->where('status', 'present')->count();
                            $mAbsent      = $mRecords->where('status', 'absent')->count();
                            $mHalf        = $mRecords->where('status', 'half_day')->count();
                            $mLeave       = $mRecords->where('status', 'on_leave')->count();
                            $mTotal       = $mRecords->count();
                        @endphp
                        <tr class="{{ $isLocked ? 'table-secondary opacity-75' : '' }}">
                            <td class="text-muted" style="font-size:12px;">{{ $i + 1 }}</td>
                            <td class="fw-bold">{{ $member->name }}</td>
                            <td><span class="badge bg-light text-dark border">{{ $member->designation }}</span></td>
                            <td>
                                @if($isLocked)
                                    <span class="badge bg-danger bg-opacity-20 text-danger border border-danger border-opacity-25 py-2 px-3">
                                        <i class="bi bi-lock-fill me-1"></i>LOCKED — Payroll Paid
                                    </span>
                                    @if($record)
                                        <span class="badge ms-2 {{ $record->statusBadgeClass }}">
                                            {{ $record->statusLabel }}
                                        </span>
                                    @endif
                                @else
                                    <div class="btn-group btn-group-sm" role="group">
                                        @foreach([
                                            'present'  => ['P',      'outline-success'],
                                            'absent'   => ['A',      'outline-danger'],
                                            'half_day' => ['&frac12;','outline-warning'],
                                            'on_leave' => ['L',      'outline-info'],
                                            'holiday'  => ['H',      'outline-secondary'],
                                        ] as $statusVal => [$label, $btnClass])
                                            <input type="radio" class="btn-check"
                                                   name="attendance[{{ $member->id }}][status]"
                                                   id="att_{{ $member->id }}_{{ $statusVal }}"
                                                   value="{{ $statusVal }}"
                                                   {{ $currentStatus === $statusVal ? 'checked' : '' }}>
                                            <label class="btn btn-sm btn-{{ $btnClass }} fw-bold"
                                                   for="att_{{ $member->id }}_{{ $statusVal }}"
                                                   title="{{ ucwords(str_replace('_', ' ', $statusVal)) }}"
                                                   style="min-width:36px;">{!! $label !!}</label>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if(!$isLocked)
                                    <input type="text"
                                           name="attendance[{{ $member->id }}][remarks]"
                                           class="form-control form-control-sm"
                                           value="{{ $record?->remarks }}"
                                           placeholder="Note..." style="width:145px; font-size:12px;">
                                @else
                                    <span class="text-muted" style="font-size:12px;">{{ $record?->remarks ?: '—' }}</span>
                                @endif
                            </td>
                            <td class="text-center" style="font-size:11.5px; white-space:nowrap;">
                                <span class="text-success fw-bold">{{ $mPresent }}P</span>
                                <span class="text-danger ms-1">{{ $mAbsent }}A</span>
                                <span class="text-warning ms-1">{{ $mHalf }}½</span>
                                @if($mLeave > 0)<span class="text-info ms-1">{{ $mLeave }}L</span>@endif
                                <span class="text-muted ms-1">/ {{ $mTotal }} days</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if(!$allLocked)
        <div class="d-flex justify-content-end mt-3 pt-3 border-top">
            <button type="submit" class="btn btn-success fw-bold px-5">
                <i class="bi bi-floppy me-2"></i>Save Attendance — {{ $selectedDate->format('d F Y') }}
            </button>
        </div>
        @endif
    </div>
</form>

{{-- ── Quick Nav to Payroll ─────────────────────────────────────────────── --}}
<div class="d-flex align-items-center gap-3 mt-1">
    <a href="{{ route('admin.staff.payroll.index', ['month' => $selectedDate->format('Y-m')]) }}"
       class="btn btn-outline-primary btn-sm fw-bold">
        <i class="bi bi-calculator me-1"></i>View / Generate {{ $monthLabel }} Payroll
    </a>
    <a href="{{ route('admin.staff.performance.index') }}"
       class="btn btn-outline-secondary btn-sm fw-bold">
        <i class="bi bi-bar-chart-line me-1"></i>Performance Report
    </a>
</div>
@endif

@endsection
