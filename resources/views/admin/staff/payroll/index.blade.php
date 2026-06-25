@extends('layouts.app')
@section('title', 'Staff Payroll — ' . $monthLabel)
@section('page-title', 'Staff Payroll Management')

@section('content')

{{-- ── Month Selector & Generate Button ───────────────────────────────── --}}
<div class="chart-card mb-4">
    <div class="row g-3 align-items-end">
        <div class="col-md-4">
            <label class="form-label fw-bold" style="font-size:11px; text-transform:uppercase; color:#64748b;">Payroll Month</label>
            <form method="GET" action="{{ route('admin.staff.payroll.index') }}" id="monthSwitchForm">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-calendar-month"></i></span>
                    <input type="month" name="month" class="form-control fw-bold"
                           value="{{ $selectedMonth }}"
                           onchange="document.getElementById('monthSwitchForm').submit()">
                </div>
            </form>
        </div>

        <div class="col-md-5">
            @if(in_array(auth()->user()->role, ['super_admin', 'data_entry']))
            <form method="POST" action="{{ route('admin.staff.payroll.generate') }}">
                @csrf
                <input type="hidden" name="payroll_month" value="{{ $selectedMonth }}">
                <button type="submit" class="btn btn-success fw-bold w-100"
                        onclick="return confirm('{{ $payrollGenerated ? 'Re-generate' : 'Generate' }} payroll for {{ $monthLabel }}?\n\nAlready-PAID entries will NOT be changed.')">
                    <i class="bi bi-calculator me-2"></i>
                    {{ $payrollGenerated ? 'Re-Generate' : 'Generate' }} Payroll — {{ $monthLabel }}
                </button>
            </form>
            @endif
        </div>

        <div class="col-md-3">
            <div class="p-3 rounded-3 border" style="background:#f8fafc; font-size:11px;">
                <strong class="text-muted d-block mb-1" style="letter-spacing:.5px;">ℹ️ HOW IT WORKS</strong>
                <span class="text-muted">Salary is computed from attendance.
                Marking PAID <strong>locks</strong> attendance for that month.</span>
            </div>
        </div>
    </div>
</div>

{{-- ── Summary KPI Cards (only shown after generation) ────────────────── --}}
@if($payrollGenerated)
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="kpi-card">
            <div class="kpi-icon icon-blue"><i class="bi bi-people-fill"></i></div>
            <div class="kpi-value">{{ $payrolls->count() }}</div>
            <div class="kpi-label">Staff on Payroll</div>
            <div class="kpi-sub">{{ $monthLabel }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-card" style="background:linear-gradient(135deg,#1B6B35,#0f4423);color:white;">
            <div class="kpi-icon" style="background:rgba(255,255,255,.15);color:#C9A84C;"><i class="bi bi-cash-coin"></i></div>
            <div class="kpi-value" style="color:white;">Rs {{ number_format($totalNet) }}</div>
            <div class="kpi-label" style="color:rgba(255,255,255,.8);">Total Net Payroll</div>
            <div class="kpi-sub" style="color:rgba(255,255,255,.55);">Gross − Deductions</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-card">
            <div class="kpi-icon icon-green"><i class="bi bi-check-circle-fill"></i></div>
            <div class="kpi-value text-success">Rs {{ number_format($paidNet) }}</div>
            <div class="kpi-label">Paid</div>
            <div class="kpi-sub">{{ $payrolls->where('payment_status','paid')->count() }} staff paid</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-card">
            <div class="kpi-icon icon-amber"><i class="bi bi-hourglass-split"></i></div>
            <div class="kpi-value text-warning">Rs {{ number_format($pendingNet) }}</div>
            <div class="kpi-label">Pending Payment</div>
            <div class="kpi-sub">{{ $payrolls->where('payment_status','pending')->count() }} staff pending</div>
        </div>
    </div>
</div>
@endif

{{-- ── Payroll Table ─────────────────────────────────────────────────────── --}}
<div class="chart-card">
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <h6 class="section-title mb-0">
            <i class="bi bi-table me-2 text-primary"></i>
            Payroll Details — {{ $monthLabel }}
        </h6>
        @if($payrollGenerated)
            <span class="badge bg-success bg-opacity-25 text-success border border-success px-3 py-2">
                <i class="bi bi-check-circle me-1"></i>Payroll Generated
            </span>
        @else
            <span class="badge bg-warning bg-opacity-25 text-warning border border-warning px-3 py-2">
                <i class="bi bi-exclamation-circle me-1"></i>Not Generated Yet — Click Generate above
            </span>
        @endif
    </div>

    <div class="table-responsive">
        <table class="table data-table align-middle">
            <thead>
                <tr>
                    <th>Staff Name</th>
                    <th>Designation</th>
                    <th class="text-center">Type</th>
                    <th class="text-center">Days (P + ½)</th>
                    <th class="text-end">Gross</th>
                    <th class="text-end">Deductions</th>
                    <th class="text-end fw-bold">Net Salary</th>
                    <th class="text-center">Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($staff as $member)
                    @php $payroll = $member->payrolls->first(); @endphp
                    <tr class="{{ $payroll?->isPaid() ? 'table-success bg-opacity-50' : '' }}">
                        <td class="fw-bold">{{ $member->name }}</td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ $member->designation }}</span>
                        </td>
                        <td class="text-center">
                            @if($payroll)
                                <span class="badge {{ $payroll->salary_type === 'monthly' ? 'bg-primary' : 'bg-info text-dark' }}">
                                    {{ ucfirst($payroll->salary_type) }}
                                </span>
                            @else
                                <span class="text-muted">{{ ucfirst($member->salary_type) }}</span>
                            @endif
                        </td>
                        <td class="text-center" style="font-size:12px;">
                            @if($payroll)
                                <span class="text-success fw-bold">{{ $payroll->present_days }}</span>
                                @if($payroll->half_days > 0)
                                    <span class="text-warning"> + {{ $payroll->half_days }}½</span>
                                @endif
                                <span class="text-muted"> / {{ $payroll->total_days }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-end" style="font-size:13px;">
                            @if($payroll)
                                Rs {{ number_format($payroll->gross_salary) }}
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-end" style="font-size:13px;">
                            @if($payroll && $payroll->deductions > 0)
                                <span class="text-danger">- Rs {{ number_format($payroll->deductions) }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @if($payroll)
                                <strong class="text-success" style="font-size:15px;">Rs {{ number_format($payroll->net_salary) }}</strong>
                            @else
                                <span class="text-muted" style="font-size:12px;">Not Generated</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($payroll)
                                @if($payroll->isPaid())
                                    <span class="badge bg-success fw-bold px-3 py-2">
                                        <i class="bi bi-lock-fill me-1"></i>PAID
                                    </span>
                                    <div class="text-muted" style="font-size:10px; margin-top:2px;">
                                        {{ $payroll->payment_date?->format('d M Y') }}
                                    </div>
                                @else
                                    <span class="badge bg-warning text-dark fw-bold px-3 py-2">PENDING</span>
                                @endif
                            @else
                                <span class="badge bg-light text-muted border">—</span>
                            @endif
                        </td>
                        <td class="text-end" style="white-space:nowrap;">
                            @if($payroll)
                                <a href="{{ route('admin.staff.payroll.show', $payroll) }}"
                                   class="btn btn-sm btn-outline-secondary" title="View Payslip">
                                    <i class="bi bi-eye"></i>
                                </a>

                                @if(!$payroll->isPaid() && auth()->user()->role === 'super_admin')
                                    <button type="button" class="btn btn-sm btn-success fw-bold ms-1"
                                            data-bs-toggle="modal" data-bs-target="#payModal{{ $payroll->id }}"
                                            title="Mark as Paid">
                                        <i class="bi bi-cash me-1"></i>Pay
                                    </button>

                                    {{-- Pay Modal --}}
                                    <div class="modal fade" id="payModal{{ $payroll->id }}" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content" style="border-radius:14px; border:none; overflow:hidden;">
                                                <form method="POST" action="{{ route('admin.staff.payroll.pay', $payroll) }}">
                                                    @csrf
                                                    <div class="modal-header" style="background:#1B6B35; color:white;">
                                                        <h5 class="modal-title fw-bold">
                                                            <i class="bi bi-cash-coin me-2"></i>Mark Salary as Paid
                                                        </h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body p-4">
                                                        <p class="mb-1 fw-bold">{{ $member->name }}</p>
                                                        <p class="text-muted mb-1" style="font-size:13px;">{{ $member->designation }} — {{ $monthLabel }}</p>
                                                        <div class="p-3 rounded-3 mb-3" style="background:#f0fdf4; border: 1px solid #86efac;">
                                                            <span class="text-muted" style="font-size:12px;">Net Salary Payable:</span>
                                                            <div class="fw-bold text-success" style="font-size:22px;">
                                                                Rs {{ number_format($payroll->net_salary) }}
                                                            </div>
                                                        </div>
                                                        <div class="alert alert-warning d-flex gap-2 align-items-start" style="font-size:12px;">
                                                            <i class="bi bi-lock-fill mt-1 flex-shrink-0"></i>
                                                            <div>
                                                                <strong>Attendance will be LOCKED</strong> for {{ $monthLabel }} once you confirm payment. This action cannot be undone.
                                                            </div>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label fw-bold" style="font-size:12px;">Payment Date <span class="text-danger">*</span></label>
                                                            <input type="date" name="payment_date" class="form-control"
                                                                   value="{{ now()->format('Y-m-d') }}" required>
                                                        </div>
                                                        <div class="mb-2">
                                                            <label class="form-label fw-bold" style="font-size:12px;">Remarks (Optional)</label>
                                                            <input type="text" name="payment_remarks" class="form-control"
                                                                   placeholder="e.g. Bank transfer, Cash...">
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-success fw-bold px-4">
                                                            <i class="bi bi-check-circle me-1"></i>Confirm Payment
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @else
                                <span class="text-muted" style="font-size:11px;">Generate first</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="bi bi-people display-5 d-block mb-2 opacity-25"></i>
                            No active maintenance staff found.
                        </td>
                    </tr>
                @endforelse
            </tbody>

            @if($payrollGenerated && $payrolls->count() > 0)
            <tfoot>
                <tr class="table-light fw-bold" style="font-size:13px;">
                    <td colspan="4">Totals — {{ $monthLabel }}</td>
                    <td class="text-end">Rs {{ number_format($payrolls->sum('gross_salary')) }}</td>
                    <td class="text-end text-danger">- Rs {{ number_format($payrolls->sum('deductions')) }}</td>
                    <td class="text-end text-success" style="font-size:15px;">Rs {{ number_format($payrolls->sum('net_salary')) }}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>

{{-- ── Quick nav ─────────────────────────────────────────────────────────── --}}
<div class="d-flex gap-2 mt-3">
    <a href="{{ route('admin.staff.attendance.index', ['date' => $monthStart->format('Y-m-d')]) }}"
       class="btn btn-sm btn-outline-secondary fw-bold">
        <i class="bi bi-calendar3 me-1"></i>Back to Attendance
    </a>
    <a href="{{ route('admin.staff.performance.index') }}"
       class="btn btn-sm btn-outline-secondary fw-bold">
        <i class="bi bi-bar-chart-line me-1"></i>Performance Report
    </a>
</div>

@endsection
