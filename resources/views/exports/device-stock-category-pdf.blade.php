<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h2 {
            margin: 0 0 5px 0;
            font-size: 16px;
            text-transform: uppercase;
            color: #000;
        }
        .header p {
            margin: 0;
            font-size: 11px;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 6px 8px;
            vertical-align: middle;
        }
        th {
            background-color: #f4f6f8;
            font-weight: bold;
            color: #333;
            text-align: center;
        }
        .text-center {
            text-align: center;
        }
        .text-end {
            text-align: right;
        }
        .text-start {
            text-align: left;
        }
        .fw-bold {
            font-weight: bold;
        }
        .text-success {
            color: #28a745;
        }
        .badge {
            background-color: #e9ecef;
            padding: 2px 5px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 10px;
        }
        .footer-total {
            background-color: #f8f9fa;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ $title }}</h2>
        <p>Dicetak pada: {{ now()->translatedFormat('d F Y H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 4%;">No.</th>
                <th style="width: 14%;">Kode Barang</th>
                <th style="width: 32%;">Nama Barang</th>
                <th style="width: 8%;">Qty</th>
                <th style="width: 14%;">Harga Beli</th>
                <th style="width: 14%;">Harga Jual</th>
                <th style="width: 14%;">Total HPP</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalQty = 0;
                $totalHpp = 0;
            @endphp
            @forelse ($stocks as $index => $item)
                @php
                    $qty = (float)($item->qty ?? 0);
                    $totalQty += $qty;
                    $hpp = (float)($item->total_hpp_base ?? ($qty * ($item->buy_price_base ?? $item->buy_price ?? 0)));
                    $totalHpp += $hpp;
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center"><span class="badge">{{ $item->code }}</span></td>
                    <td class="text-start fw-bold">{{ $item->name }}</td>
                    <td class="text-center">{{ number_format($qty, 0, ',', '.') }}</td>
                    <td class="text-center">{{ \App\Http\Helpers\CustomHelper::formatCurrency($item->buy_price, $item->currency_code ?? 'IDR') }}</td>
                    <td class="text-center">{{ \App\Http\Helpers\CustomHelper::formatCurrency($item->latest_sell_price ?? $item->sell_price, $item->latest_currency_code ?? $item->currency_code ?? 'IDR') }}</td>
                    <td class="text-end fw-bold text-success">{{ \App\Http\Helpers\CustomHelper::formatCurrency($hpp, 'IDR') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center" style="padding: 15px; color: #999;">Tidak ada data barang untuk kategori ini.</td>
                </tr>
            @endforelse
        </tbody>
        @if(count($stocks) > 0)
            <tfoot>
                <tr class="footer-total">
                    <td colspan="3" class="text-end fw-bold">TOTAL:</td>
                    <td class="text-center fw-bold">{{ number_format($totalQty, 0, ',', '.') }}</td>
                    <td colspan="2" class="text-end fw-bold">TOTAL:</td>
                    <td class="text-end fw-bold text-success">{{ \App\Http\Helpers\CustomHelper::formatCurrency($totalHpp, 'IDR') }}</td>
                </tr>
            </tfoot>
        @endif
    </table>
</body>
</html>
