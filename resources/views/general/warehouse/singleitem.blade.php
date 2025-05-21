@extends('layouts.vertical', ['title' => 'Item Details'])

@section('content')

<div class="row">
    <div class="col-lg-12">
        <a href="{{ route('warehouse.index') }}" class="btn btn-primary btn-sm mb-2">
            <iconify-icon icon="solar:alt-arrow-left-bold" class="align-middle fs-18"></iconify-icon> Kembali
        </a>
        <div class="card">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-lg-3 border-end">
                        <div class="">
                            <h3 class="mb-1">{{ $item->name }} ({{ $item->code }}) </h3>
                            @if($item->stocks->sum('quantity') < $item->minimum_qty)
                                <div class="text-danger small mb-2 text-start">Stok kurang dari minimum!</div>
                            @endif
                            
                            <div class="mb-3">

                                <div class="d-flex justify-content-between mb-2">
                                    <span>Type</span>
                                    <strong>{{ $item->type ?? '-' }}</strong>
                                </div>

                                <div class="d-flex justify-content-between mb-2">
                                    <span>Kategori</span>
                                    <strong>{{ $item->category }}</strong>
                                </div>

                                <div class="d-flex justify-content-between mb-2">
                                    <span>UoM</span>
                                    <strong>{{ $item->unit }}</strong>
                                </div>

                                <div class="d-flex justify-content-between mb-2">
                                    <span>Stok Barang</span>
                                    <strong class="{{ $item->stocks->sum('quantity') < $item->minimum_qty ? 'text-danger' : '' }}">
                                        {{ $item->stocks->sum('quantity') }} {{ $item->unit }}
                                    </strong>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <span>Minimum Stock</span>
                                    <strong>{{ $item->minimum_qty }} {{ $item->unit }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-9">
                        <div class="ps-lg-4">
                            <h4 class="card-title">5 Data Mutasi Terakhir</h4>
                            @if($mutations->isEmpty())
                                <p>Tidak ada data mutasi.</p>
                            @else
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Tipe</th>
                                            <th>Qty Sebelumnya</th>
                                            <th>Qty Setelahnya</th>
                                            <th>Source</th>
                                            <th>Catatan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($mutations as $mutation)
                                            <tr @class([
                                                'table-success' => $mutation->type === 'in',
                                                'table-danger' => $mutation->type === 'out',
                                                'table-warning' => $mutation->type === 'adjustment',
                                            ])>
                                                <td>{{ $mutation->created_at->format('d-m-Y H:i') }}</td>
                                                <td>{{ ucfirst($mutation->type) }}</td>
                                                <td>{{ $mutation->quantity_before }}</td>
                                                <td>{{ $mutation->quantity_after }}</td>
                                                <td>{{ $mutation->source }}</td>
                                                <td>{{ $mutation->note }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <h3 class="d-flex align-items-center gap-2">Grafik Stok Barang </h3>
                        <p class="mb-0 {{ $stokawal < $minimumStock ? 'text-danger' : 'text-success' }}">
                            {{ $stockStatus }}
                        </p>
                    </div>
                    <div class="avatar-md bg-light bg-opacity-50 rounded">
                        <iconify-icon icon="solar:chart-2-bold-duotone" class="fs-32 text-primary avatar-title"></iconify-icon>
                    </div>
                </div>
                
                <div class="apex-charts">
                    <div id="stockChart" style="height: 350px;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
@section('script')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
@php
    $labels = $chartMutations->pluck('created_at')->map(fn($d) => $d->format('d M Y H:i'))->toArray();
    $dataQty = $chartMutations->pluck('quantity_after')->toArray();
@endphp

<script>
document.addEventListener('DOMContentLoaded', function () {
    const labels = {!! json_encode($labels) !!};
    const dataQty = {!! json_encode($dataQty) !!};
    const minStock = {{ $minimumStock }};
    const unit = "{{ $unit }}";

    const colors = dataQty.map(qty => qty <= minStock ? '#dc3545' : '#0d6efd');

    const options = {
        chart: {
            type: 'line',
            height: 350,
            toolbar: { show: false },
            zoom: { enabled: false }
        },
        series: [{
            name: 'Stok Barang',
            data: dataQty
        }],
        xaxis: {
            categories: labels,
            title: { text: 'Tanggal' }
        },
        yaxis: {
            title: { text: 'Qty' },
            min: Math.min(...dataQty, minStock) - 10,
            max: Math.max(...dataQty, minStock) + 10
        },
        markers: {
            size: 5,
            colors: colors,
            strokeColors: '#fff',
            strokeWidth: 2,
            hover: { size: 7 }
        },
        tooltip: {
            y: {
                formatter: function (val) {
                    return val + " " + unit;
                }
            }
        },
        annotations: {
            yaxis: [{
                y: minStock,
                borderColor: '#aaa',
                strokeDashArray: 5,
                label: {
                    borderColor: '#aaa',
                    style: {
                        color: '#fff',
                        background: '#aaa',
                        fontSize: '12px',
                        fontWeight: 600
                    },
                    text: 'Minimum Stok: ' + minStock + ' ' + unit,
                    position: 'right',
                    offsetY: 0
                }
            }]
        },
        stroke: {
            curve: 'smooth',
            width: 3
        },
        colors: ['#FFC512'],
    };

    const chart = new ApexCharts(document.querySelector("#stockChart"), options);
    chart.render();
});
</script>

@endsection