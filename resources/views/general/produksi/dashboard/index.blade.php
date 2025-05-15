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
                    
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="button" class="btn btn-outline-danger w-100" data-bs-toggle="modal" data-bs-target="#compareModal">
                            <i class="bi bi-bar-chart-steps me-1"></i> Bandingkan Data
                        </button>
                    </div>
                    

                    <div class="col-md-3 d-flex align-items-end">
                        <a href="{{ route('produksi.export.pdf') . '?' . http_build_query(request()->query()) }}"
                        class="btn btn-success w-100">Download PDF</a>
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

<!-- Modal Bandingkan -->
<!-- Modal Bandingkan -->
<div class="modal fade" id="compareModal" tabindex="-1" aria-labelledby="compareModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <form method="GET" action="{{ route('dashboard.production.trend_by_product') }}">
        <div class="modal-header">
          <h5 class="modal-title" id="compareModalLabel">Bandingkan Tren Produksi</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Jenis Perbandingan</label>
              <select name="compare_mode" id="compare_mode" class="form-select" required>
                <option value="">Pilih...</option>
                <option value="product">Produk Lain</option>
                <option value="date">Periode Lain</option>
              </select>
            </div>

            <div class="col-md-6 compare-field compare-product d-none">
              <label class="form-label">Produk Pembanding</label>
              <select name="produk_compare" class="form-select">
                @foreach ($produkOptions as $produk)
                  @if($produk !== $selectedProduk)
                  <option value="{{ $produk }}">{{ $produk }}</option>
                  @endif
                @endforeach
              </select>
            </div>

            <div class="col-md-6 compare-field compare-date d-none">
              <label class="form-label">Tanggal Mulai</label>
              <input type="date" name="compare_from" class="form-control">
            </div>
            <div class="col-md-6 compare-field compare-date d-none">
              <label class="form-label">Tanggal Selesai</label>
              <input type="date" name="compare_to" class="form-control">
            </div>

            <!-- Hidden original filter values -->
            <input type="hidden" name="produk" value="{{ $selectedProduk }}">
            <input type="hidden" name="from" value="{{ $from }}">
            <input type="hidden" name="to" value="{{ $to }}">
            <input type="hidden" name="group_by" value="{{ $groupBy }}">
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-circle me-1"></i> Terapkan Perbandingan
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

@endsection

@section('script')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('trendByProductChart').getContext('2d');

    const mainDatasets = @json($datasets);
    const compareDatasets = @json($compareDatasets ?? []);
    const allDatasets = mainDatasets.concat(compareDatasets);

    const trendChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: @json($labels),
            datasets: allDatasets
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
                    text: 'Grafik Tren Produksi per Produk ({{ ucfirst($groupBy) }})'
                },
                tooltip: {
                    mode: 'index',
                    intersect: false
                },
                legend: {
                    display: true,
                    position: 'top'
                }
            }
        }
    });
</script>

@if (!empty($compareMode))
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const chartContainer = document.getElementById('trendByProductChart').parentElement;
        const note = document.createElement('div');
        note.classList.add('alert', 'alert-info', 'mt-3');
        note.innerHTML = '<strong>Keterangan:</strong> {{ $compareLabel ?? "Data Pembanding" }}';
        chartContainer.appendChild(note);
    });
</script>
@endif

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const compareSelect = document.getElementById('compare_mode');
        const fields = document.querySelectorAll('.compare-field');

        compareSelect.addEventListener('change', function () {
            fields.forEach(el => el.classList.add('d-none'));
            if (this.value === 'product') {
                document.querySelectorAll('.compare-product').forEach(el => el.classList.remove('d-none'));
            } else if (this.value === 'date') {
                document.querySelectorAll('.compare-date').forEach(el => el.classList.remove('d-none'));
            }
        });
    });
</script>
@endsection

