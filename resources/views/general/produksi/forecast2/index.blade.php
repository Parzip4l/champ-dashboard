@extends('layouts.vertical', ['title' => 'Production Forecast'])

@section('content')
    <div class="mt-4">
        <h3 class="mb-4">Prediksi Kebutuhan Bahan Baku</h3>

        <!-- Menampilkan Nama Produk dan Quantity -->
        <div class="alert alert-info">
            <strong>Produk:</strong> {{ $produk }} <br>
            <strong>Jumlah Target:</strong> {{ number_format($targetQuantity, 0) }}
        </div>

        @if(!empty($predictedMaterials))
            <div class="row">

                <!-- Tabel -->
                <div class="col-md-12">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">Tabel Prediksi Kebutuhan Bahan Baku</h5>
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th scope="col">Nama Bahan</th>
                                        <th scope="col">Tipe</th>
                                        <th scope="col">Jenis</th>
                                        <th scope="col">Prediksi Jumlah</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($predictedMaterials as $item)
                                        <tr>
                                            <td>{{ $item['nama'] }}</td>
                                            <td>{{ $item['tipe'] }}</td>
                                            <td>{{ $item['jenis'] }}</td>
                                            <td>{{ number_format($item['predicted_qty'], 0) }} Kg</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="alert alert-info">
                <strong>Info!</strong> Tidak ada data prediksi untuk produk ini.
            </div>
        @endif
        <a href="{{ route('production_batches.index') }}" class="btn btn-primary btn-sm mb-2">
            <iconify-icon icon="solar:alt-arrow-left-bold" class="align-middle fs-18"></iconify-icon> Kembali
        </a>
    </div>
    @endsection
    @section('script')
        <!-- Chart.js CDN -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            // Debugging output
            console.log(@json($predictedMaterials->pluck('nama')));
            console.log(@json($predictedMaterials->pluck('predicted_qty')));

            // Data untuk grafik
            var ctx = document.getElementById('forecastChart').getContext('2d');
            var forecastChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: @json($predictedMaterials->pluck('nama')), // Nama bahan
                    datasets: [{
                        label: 'Prediksi Kebutuhan Bahan Baku',
                        data: @json($predictedMaterials->pluck('predicted_qty')), // Prediksi jumlah bahan
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        </script>
@endsection

