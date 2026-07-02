<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bill Details — PHAF Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        body { background: #f0f4f8; }
        .portal-topbar {
            background: linear-gradient(135deg, #0f4423, #1B6B35);
            padding: 12px 24px; display: flex; align-items: center; justify-content: space-between;
        }
        .portal-topbar .brand { display: flex; align-items: center; gap: 12px; }
        .portal-topbar .brand img { height: 36px; }
        .portal-topbar .brand-text .t1 { color: #fff; font-weight: 700; font-size: 14px; }
        .portal-topbar .brand-text .t2 { color: rgba(255,255,255,0.6); font-size: 11px; }
        .page-body { padding: 28px; max-width: 700px; margin: 0 auto; }
        .bill-card { background: #fff; border-radius: 16px; padding: 0; border: 1px solid #e8edf3; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .bill-header { background: #1B6B35; color: #fff; padding: 24px; text-align: center; }
        .bill-header h3 { margin: 0; font-weight: 800; }
        .bill-body { padding: 24px; }
        .bill-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #f1f5f9; }
        .bill-row.total { border-bottom: none; font-size: 18px; font-weight: 800; padding-top: 16px; margin-top: 8px; border-top: 2px dashed #e2e8f0; }
        .psid-box { background: #f0f9f4; border: 2px dashed #1B6B35; border-radius: 12px; padding: 20px; text-align: center; margin-top: 24px; }
        .psid-box .lbl { font-size: 12px; color: #1B6B35; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; }
        .psid-box .psid-val { font-size: 28px; font-weight: 900; color: #1a2332; letter-spacing: 2px; margin: 8px 0; }
        .btn-pay { background: #2563eb; color: #fff; border: none; padding: 12px 24px; border-radius: 10px; font-weight: 700; width: 100%; display: flex; align-items: center; justify-content: center; gap: 8px; font-size: 15px; margin-top: 16px; }
        .btn-pay:hover { background: #1d4ed8; color: #fff; }
        .raast-qr-box { background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-top: 24px; }
        .raast-qr-box .qr-title { font-size: 14px; color: #0f4423; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; justify-content: center; gap: 8px; margin-bottom: 16px; }
        .raast-qr-box .qr-container { background: #fff; padding: 16px; border-radius: 12px; display: inline-block; border: 1px solid #cbd5e1; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .raast-qr-box .qr-container svg { max-width: 160px; height: auto; }
        .raast-steps { text-align: left; background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px 18px; margin-top: 16px; font-size: 13px; color: #334155; }
        .raast-steps ol { margin: 0; padding-left: 20px; }
        .raast-steps ol li { margin-bottom: 6px; font-weight: 500; }
        .raast-steps ol li:last-child { margin-bottom: 0; }
        .btn-action-outline { background: #fff; border: 1px solid #cbd5e1; color: #334155; padding: 6px 14px; border-radius: 8px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; cursor: pointer; transition: all 0.2s; }
        .btn-action-outline:hover { background: #f1f5f9; border-color: #94a3b8; color: #0f172a; }
    </style>
</head>
<body>

<div class="portal-topbar">
    <div class="brand">
        <a href="{{ route('portal.dashboard') }}" class="text-white text-decoration-none me-2"><i class="bi bi-arrow-left"></i></a>
        <img src="{{ asset('images/logos/pha-logo.svg') }}" alt="PHAF">
        <div class="brand-text">
            <div class="t1">PHAF Maintenance Portal</div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="bill-card">
        <div class="bill-header">
            <h3>Bill for {{ \Carbon\Carbon::parse($bill->bill_month)->format('F Y') }}</h3>
            <div style="font-size: 13px; opacity: 0.8; mt-1">File No: {{ $allottee->file_no }}</div>
        </div>
        <div class="bill-body">
            
            <div class="text-center mb-4">
                @if($bill->status === 'paid' || $bill->status === 'settled')
                    <div style="display:inline-block; padding:8px 24px; background:#dcfce7; color:#166534; font-weight:800; border-radius:20px; font-size:14px; border:2px solid #166534;">
                        <i class="bi bi-check-circle-fill me-1"></i> PAID / SETTLED
                    </div>
                @else
                    <div style="display:inline-block; padding:8px 24px; background:#fee2e2; color:#991b1b; font-weight:800; border-radius:20px; font-size:14px; border:2px solid #991b1b;">
                        <i class="bi bi-exclamation-circle-fill me-1"></i> UNPAID
                    </div>
                @endif
            </div>

            @php
                $pCharge = $allottee->has_parking ? ($allottee->parking_charges ?: 500) : 0;
                $wCharge = $allottee->has_water ? ($allottee->water_charges ?: 1000) : 0;
                $baseMonthly = ($allottee->covered_area * $rate) + $pCharge + $wCharge;
                $mMonths = $baseMonthly > 0 ? max(1, round($bill->maintenance_amount / $baseMonthly, 1)) : ($allottee->due_months ?: 1);
                $wwCutoff = \App\Models\Setting::getValue('ww_cutoff_date', '2023-07-01');
                $wwRate = (float) \App\Models\Setting::getValue('ww_monthly_rate', 10000);
                $wwMonths = $wwRate > 0 ? round($bill->ww_amount / $wwRate, 1) : 0;
            @endphp
            <div class="bill-row" style="flex-direction:column; align-items:flex-start;">
                <div style="display:flex; justify-content:space-between; width:100%;">
                    <span style="color:#64748b; font-weight:600;">Maintenance Charges</span>
                    <span style="font-weight:700;">Rs. {{ number_format($bill->maintenance_amount) }}</span>
                </div>
                <div style="font-size:11px; color:#475569; margin-top:4px;">
                    <strong>Calculation Formula:</strong> {{ $allottee->covered_area }} Sq Ft @ Rs. {{ number_format($rate, 2) }}/sq ft
                    @if($pCharge > 0) + Parking Rs. {{ number_format($pCharge) }} @endif
                    @if($wCharge > 0) + Water Rs. {{ number_format($wCharge) }} @endif
                    = <strong>Rs. {{ number_format($baseMonthly, 2) }}/mo</strong> &times; {{ $mMonths }} month(s) billed
                </div>
            </div>
            @if($bill->ww_amount > 0)
            <div class="bill-row" style="flex-direction:column; align-items:flex-start;">
                <div style="display:flex; justify-content:space-between; width:100%;">
                    <span style="color:#64748b; font-weight:600;">Watch & Ward Charges</span>
                    <span style="font-weight:700;">Rs. {{ number_format($bill->ww_amount) }}</span>
                </div>
                <div style="font-size:11px; color:#475569; margin-top:4px;">
                    <strong>Calculation Formula:</strong> Accrued @ Rs. {{ number_format($wwRate) }}/month &times; {{ $wwMonths }} months (From {{ \Carbon\Carbon::parse($wwCutoff)->format('d M Y') }})
                </div>
            </div>
            @endif
            @if($bill->fine_amount > 0)
            <div class="bill-row text-danger" style="flex-direction:column; align-items:flex-start;">
                <div style="display:flex; justify-content:space-between; width:100%;">
                    <span style="font-weight:600;">Delay Surcharge (Fine)</span>
                    <span style="font-weight:700;">Rs. {{ number_format($bill->fine_amount) }}</span>
                </div>
                <div style="font-size:11px; color:#dc2626; margin-top:4px;">
                    <strong>Calculation Formula:</strong> 10% delayed payment surcharge penalty applied on overdue maintenance arrears as per policy
                </div>
            </div>
            @endif
            <div class="bill-row total">
                <span>Total Billed Amount</span>
                <span>Rs. {{ number_format($bill->maintenance_amount + $bill->ww_amount + $bill->fine_amount) }}</span>
            </div>
            <div class="bill-row" style="border-bottom: 2px solid #e2e8f0; padding-bottom: 8px;">
                <span style="color:#64748b;">Amount Paid</span>
                <span style="font-weight:600; color:#1B6B35;">Rs. {{ number_format($bill->paid_amount) }}</span>
            </div>
            <div class="bill-row total" style="background:#f8fafc; padding: 10px;">
                <span style="font-weight: 700;">Net Balance Due</span>
                <span style="font-weight: 800; color:#dc2626;">Rs. {{ number_format(max(0, $bill->total_amount - $bill->paid_amount)) }}</span>
            </div>

            @if($bill->status !== 'paid' && $bill->status !== 'settled')
                @if(!empty($qrSvg))
                <div class="raast-qr-box text-center">
                    <div class="qr-title">
                        <i class="bi bi-qr-code-scan" style="font-size: 18px; color: #1B6B35;"></i>
                        Raast QR Code Payment
                    </div>
                    
                    <div class="qr-container mb-3">
                        {!! $qrSvg !!}
                    </div>

                    <div class="d-flex justify-content-center gap-2 mb-3 flex-wrap">
                        <button type="button" class="btn-action-outline" onclick="downloadQrImage()">
                            <i class="bi bi-download"></i> Download QR
                        </button>
                        <button type="button" class="btn-action-outline" onclick="copyRefNumber('{{ $allottee->file_no }}')">
                            <i class="bi bi-clipboard"></i> Copy Reference No
                        </button>
                    </div>

                    <div class="raast-steps">
                        <div class="fw-bold text-dark mb-2" style="font-size: 13px;"><i class="bi bi-info-circle-fill text-success me-1"></i> How to pay using Raast QR:</div>
                        <ol>
                            <li>Open your mobile banking app (Raast-enabled)</li>
                            <li>Select <strong>“Scan QR”</strong> option</li>
                            <li>Scan the displayed QR code above</li>
                            <li>Confirm bill details (name, amount, reference)</li>
                            <li>Complete payment through your bank</li>
                        </ol>
                    </div>
                </div>
                @endif

                <div class="psid-box">
                    <div class="lbl"><img src="{{ asset('images/logos/1link.png') }}" alt="1Link" style="height:20px; margin-right:8px; vertical-align:middle; filter:grayscale(1) opacity(0.6); display:none;">1Bill Payment (PSID)</div>
                    <div class="psid-val">{{ $bill->psid }}</div>
                    <div style="font-size:11px; color:#64748b;">Use this 1Bill Invoice ID in any banking app to pay instantly.</div>
                    
                    <button type="button" class="btn-pay" onclick="simulatePayment()">
                        <i class="bi bi-phone"></i> Simulate Payment via App
                    </button>
                </div>
            @else
                <div class="mt-4 p-3 bg-light rounded text-center border">
                    <i class="bi bi-receipt me-2 text-success"></i>
                    <strong>Payment Recorded:</strong> Rs. {{ number_format($bill->paid_amount) }}
                    @if($bill->payment_date)<br><small class="text-muted">Paid on: {{ \Carbon\Carbon::parse($bill->payment_date)->format('d M Y') }}</small>@endif
                </div>

                @if(!empty($qrSvg))
                <div class="raast-qr-box text-center opacity-50" style="pointer-events: none;">
                    <div class="qr-title text-muted">
                        <i class="bi bi-qr-code"></i> Raast QR Payment (Inactive)
                    </div>
                    <div class="qr-container mb-2" style="filter: grayscale(1);">
                        {!! $qrSvg !!}
                    </div>
                    <div style="font-size: 12px; font-weight: 600; color: #64748b;">
                        Bill is already paid. QR code scanning is disabled.
                    </div>
                </div>
                @endif
            @endif
            
            <div class="text-center mt-4">
                <a href="{{ route('bills.pdf', $allottee) }}" class="text-decoration-none" style="color:#1B6B35; font-size:13px; font-weight:600;">
                    <i class="bi bi-download me-1"></i> Download Original Bill PDF
                </a>
            </div>

        </div>
    </div>
</div>

<!-- Simulation Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content text-center">
            <div class="modal-body p-4" id="modalBody">
                <div class="spinner-border text-primary mb-3" role="status"></div>
                <h6 class="fw-bold">Processing...</h6>
                <p class="text-muted small">Simulating payment via 1Bill API for PSID <br>{{ $bill->psid }}</p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function simulatePayment() {
        const modal = new bootstrap.Modal(document.getElementById('paymentModal'));
        modal.show();

        setTimeout(() => {
            const body = document.getElementById('modalBody');
            body.innerHTML = `
                <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                <h5 class="mt-3 fw-bold text-success">Payment Successful!</h5>
                <p class="text-muted small">Your payment of Rs. {{ number_format($bill->total_amount) }} was received successfully.</p>
                <button type="button" class="btn btn-outline-success btn-sm w-100" onclick="window.location.reload()">Return to Bill</button>
            `;
            
            // Actually record the payment in the backend using an AJAX call
            fetch("{{ route('monthly-bills.pay', $bill->id) }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    paid_amount: {{ $bill->total_amount }},
                    payment_mode: 'psid',
                    payment_date: new Date().toISOString().split('T')[0]
                })
            });
            
        }, 2500);
    }

    function downloadQrImage() {
        const svg = document.querySelector('.qr-container svg');
        if (!svg) return;
        const svgData = new XMLSerializer().serializeToString(svg);
        const svgBlob = new Blob([svgData], {type: 'image/svg+xml;charset=utf-8'});
        const DOMURL = window.URL || window.webkitURL || window;
        const url = DOMURL.createObjectURL(svgBlob);
        
        const img = new Image();
        img.onload = function () {
            const canvas = document.createElement('canvas');
            canvas.width = img.width || 300;
            canvas.height = img.height || 300;
            const ctx = canvas.getContext('2d');
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.drawImage(img, 0, 0);
            DOMURL.revokeObjectURL(url);
            
            const pngUrl = canvas.toDataURL('image/png');
            const downloadLink = document.createElement('a');
            downloadLink.href = pngUrl;
            downloadLink.download = 'Raast-QR-{{ $allottee->file_no }}.png';
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
        };
        img.src = url;
    }

    function copyRefNumber(text) {
        navigator.clipboard.writeText(text).then(() => {
            alert('Reference Number (' + text + ') copied to clipboard!');
        }).catch(err => {
            prompt('Copy Reference Number:', text);
        });
    }
</script>
</body>
</html>
