<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print POS Ticket</title>
    <style>
        body, html {
            margin: 0;
            padding: 0;
            background: white;
            font-family: 'Courier New', monospace, sans-serif;
            color: #000;
        }
        .pos-thermal-receipt {
            width: 58mm;
            padding: 2mm;
            font-size: 12px;
            line-height: 1.4;
            margin: 0 auto;
        }
        @media print {
            @page {
                size: 58mm auto;
                margin: 0;
            }
            html, body {
                margin: 0;
                padding: 0;
            }
            .pos-thermal-receipt {
                margin: 0;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="pos-thermal-receipt">
        @if($isExit)
            <!-- EXIT RECEIPT -->
            <div style="text-align: center; margin-bottom: 10px;">
                <img src="data:image/svg+xml;base64,{{ $qrCode }}" style="width: 150px; height: 150px; display: block; margin: 0 auto;" alt="Payment QR">
                <div style="font-size: 11px; font-weight: 900; margin-top: 4px;">MODE OF PAYMENT</div>
            </div>

            <div style="text-align: center; font-size: 16px; font-weight: 900; font-family: monospace; letter-spacing: 1px; margin-bottom: 8px;">
                PLATE #: {{ $entry->plate_number }}
            </div>

            <div style="text-align: center; font-size: 12px; margin-bottom: 4px;">
                TIME IN: {{ $entry->entry_time ? $entry->entry_time->format('M d, Y h:i A') : 'N/A' }}
            </div>

            <div style="text-align: center; font-size: 12px; margin-bottom: 4px;">
                TIME OUT: {{ $entry->exit_time ? $entry->exit_time->format('M d, Y h:i A') : 'N/A' }}
            </div>

            <div style="text-align: center; font-size: 12px; margin-bottom: 8px; font-weight: bold;">
                TIME PARKED: @if($duration && $duration->d > 0){{ $duration->d }}d @endif{{ $duration ? $duration->h : 0 }}h {{ $duration ? $duration->i : 0 }}m
            </div>

            <div style="text-align: center; font-size: 14px; font-weight: 900; border-top: 1px dashed #000; border-bottom: 1px dashed #000; padding: 6px 0; margin-bottom: 8px;">
                TOTAL COST: ₱{{ number_format($entry->parking_fee, 2) }}
            </div>

            <div style="text-align: center; font-size: 10px;">
                <div>THANK YOU FOR PARKING!</div>
                <div>AUTOTRACE PARKING</div>
            </div>
        @else
            <!-- ENTRY RECEIPT -->
            <div style="text-align: center; margin-bottom: 10px;">
                <img src="data:image/svg+xml;base64,{{ $qrCode }}" style="width: 150px; height: 150px; display: block; margin: 0 auto;" alt="Entry QR">
                <div style="font-size: 11px; font-weight: 900; margin-top: 4px;">ENTRY GATE PASS</div>
            </div>

            <div style="text-align: center; font-size: 16px; font-weight: 900; font-family: monospace; letter-spacing: 1px; margin-bottom: 8px;">
                PLATE #: {{ $entry->plate_number }}
            </div>

            <div style="text-align: center; font-size: 12px; margin-bottom: 6px;">
                TIME IN: {{ $entry->entry_time ? $entry->entry_time->format('M d, Y h:i A') : 'N/A' }}
            </div>

            <div style="text-align: center; font-size: 10px; border-top: 1px dashed #000; padding-top: 8px; margin-top: 8px;">
                <div>KEEP TICKET UNTIL CHECKOUT</div>
                <div>AUTOTRACE PARKING</div>
            </div>
        @endif
    </div>
</body>
</html>
