@extends('layouts.vertical', ['title' => 'Dashboard Production'])

@section('content')
<div class="row">
    <div class="col">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Tren Produksi Produk (Ton)</h4>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label for="produk" class="form-label">Produk</label>
                        <select name="produk" id="produk" class="form-select">
                            <option value="">Semua Produk</option>
                            @foreach ($produkOptions as $produk)
                                <option value="{{ $produk }}" {{ $produk == $selectedProduk ? 'selected' : '' }}>
                                    {{ $produk }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="from" class="form-label">Dari Tanggal</label>
                        <input type="date" name="from" id="from" class="form-control" value="{{ $from }}">
                    </div>

                    <div class="col-md-3">
                        <label for="to" class="form-label">Sampai Tanggal</label>
                        <input type="date" name="to" id="to" class="form-control" value="{{ $to }}">
                    </div>

                    <div class="col-md-3">
                        <label for="group_by" class="form-label">Group By</label>
                        <select name="group_by" id="group_by" class="form-select">
                            <option value="monthly" {{ $groupBy == 'monthly' ? 'selected' : '' }}>Bulanan</option>
                            <option value="daily" {{ $groupBy == 'daily' ? 'selected' : '' }}>Harian</option>
                        </select>
                    </div>

                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Tampilkan</button>
                    </div>

                    <div class="col-md-3 d-flex align-items-end">
                        <a href="{{ route('dashboard.production.trend_by_product') }}" class="btn btn-secondary w-100">Reset</a>
                    </div>
                </form>
                <canvas id="trendByProductChart" height="100"></canvas>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
            <div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>Produk</th>
                <th>Ukuran (kg)</th>
                <th>Kemasan</th>
                <th>Jumlah Unit</th>
                <th>Total Ton</th>
            </tr>
        </thead>
        <tbody>
            @foreach($breakdownData as $row)
                <tr>
                    <td>{{ $row->produk }}</td>
                    <td>{{ $row->size }}</td>
                    <td>{{ $row->kemasan }}</td>
                    <td>{{ $row->total_unit }}</td>
                    <td>{{ number_format($row->total_ton, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('trendByProductChart').getContext('2d');
    const trendChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: @json($labels),
            datasets: @json($datasets),
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    title: {
                        display: true,
                        text: 'Ton'
                    },
                    beginAtZero: true
                },
                x: {
                    title: {
                        display: true,
                        text: 'Periode'
                    }
                }
            },
            plugins: {
                title: {
                    display: true,
                    text: 'Grafik Tren Produksi per Produk (' + '{{ ucfirst($groupBy) }}' + ')'
                },
                tooltip: {
                    mode: 'index',
                    intersect: false
                }
            }
        }
    });
</script>
@endsection
