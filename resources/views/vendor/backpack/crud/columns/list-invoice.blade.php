{{-- regular object attribute --}}
@php
    $column['value'] = $column['value'] ?? data_get($entry, $column['name']);
    $column['escaped'] = $column['escaped'] ?? true;
    $column['limit'] = $column['limit'] ?? 32;
    $column['prefix'] = $column['prefix'] ?? '';
    $column['suffix'] = $column['suffix'] ?? '';
    $column['text'] = $column['default'] ?? '-';

    if($column['value'] instanceof \Closure) {
        $column['value'] = $column['value']($entry);
    }

    if(is_array($column['value'])) {
        $column['value'] = json_encode($column['value']);
    }

    if(!empty($column['value'])) {
        $column['text'] = $column['prefix'].Str::limit($column['value'], $column['limit'], '…').$column['suffix'];
    }
    $items = $entry->invoice_client_details;
    $row_number = 0;
@endphp

<div>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>No</th>
                <th>Nama</th>
                <th>Qty</th>
                <th>Harga</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $item)
                @php
                    $row_number++;
                    $price_str = \App\Http\Helpers\CustomHelper::formatRupiahWithCurrency($item->price);
                    $total_price_str = \App\Http\Helpers\CustomHelper::formatRupiahWithCurrency($item->price * $item->qty);
                @endphp
                <tr>
                    <td>{{$row_number}}</td>
                    <td>{{$item->name}}</td>
                    <td>{{$item->qty}}</td>
                    <td>{{$price_str}}</td>
                    <td>{{$total_price_str}}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
