@extends('layouts.vertical', ['title' => 'Production Forecast'])

@section('content')
    <div class="mt-4">
        <h3 class="mb-4">📦 Prediksi Kebutuhan Produksi</h3>
        <div class="card">
            <div class="card-header">
            <h4>🔖 Informasi Produk</h4>
            </div>
            <div class="card-body">
               <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th>Packaging</th>
                                <th>Size (Kg)</th>
                                <th>Quantity</th>
                                <th>Total Kg</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($packagingInfo as $info)
                                <tr>
                                    <td>{{ $produk }}</td>
                                    <td>{{ $info['packaging'] }}</td>
                                    <td>{{ $info['size'] }} Kg</td>
                                    <td>{{ $info['quantity'] }}</td>
                                    <td>{{ $info['total_kg'] }} Kg</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
               </div>
            </div>
        </div>

        @if($predictedMaterials->isNotEmpty())
        <div class="card">
            <div class="card-header">
                <h4>⛽ Prediksi Penggunaan Bahan Bakar</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th scope="col">Tangki</th>
                                <th scope="col">Prediksi Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Tangki Masak</td>
                                <td>{{ number_format($predictedFuelMasak, 0) }} liter</td>
                            </tr>
                            <tr>
                                <td>Tangki Olah</td>
                                <td>{{ number_format($predictedFuelOlah, 0) }} liter</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        @if(!empty($predictedMaterials))
            <div class="row">

                <!-- Tabel -->
                <div class="col-md-12">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">📦 Prediksi Kebutuhan Bahan Baku</h5>
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

