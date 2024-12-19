@extends('layouts.vertical', ['title' => 'Penerimaan Oli Bulan Ini'])

@section('content')

<div class="row">
    <div class="col-xxl-5">
        <div class="row">
            <div class="col-12">
                <div class="alert alert-primary text-truncate mb-3" role="alert">
                    Welcome Back {{Auth::user()->name}} !
                </div>
            </div>

            <div class="col-md-6">
                <div class="card overflow-hidden">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-5">
                                <div class="avatar-md bg-soft-primary rounded">
                                    <iconify-icon icon="solar:fuel-outline"
                                                    class="avatar-title fs-32 text-primary"></iconify-icon>
                                </div>
                            </div> <!-- end col -->
                            <div class="col-7 text-end">
                                <p class="text-muted mb-0 text-truncate">Oli Bahan</p>
                                <h3 class="text-dark mt-1 mb-0">{{ $totalOliCurrent['Bahan'] ?? 0 }} Drum</h3>
                            </div> <!-- end col -->
                        </div> <!-- end row-->
                    </div> <!-- end card body -->
                    <div class="card-footer py-2 bg-light bg-opacity-50">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                @php
                                    $change = $percentChange['Bahan'] ?? 0;
                                    $changeIcon = $change >= 0 ? 'bx bxs-up-arrow' : 'bx bxs-down-arrow';
                                    $changeColor = $change >= 0 ? 'text-success' : 'text-danger';
                                @endphp
                                <span class="{{ $changeColor }}"> 
                                    <i class="{{ $changeIcon }} fs-12"></i> {{ abs(round($change, 1)) }}%
                                </span>
                                <span class="text-muted ms-1 fs-12">Last Month</span>
                            </div>
                        </div>
                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->
            <div class="col-md-6">
                <div class="card overflow-hidden">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6">
                                <div class="avatar-md bg-soft-primary rounded">
                                <iconify-icon icon="solar:fuel-bold"
                                class="avatar-title fs-32 text-primary"></iconify-icon>
                                </div>
                            </div> <!-- end col -->
                            <div class="col-6 text-end">
                                <p class="text-muted mb-0 text-truncate">Oli Trafo</p>
                                <h3 class="text-dark mt-1 mb-0">{{ $totalOliCurrent['Trafo'] ?? 0 }} Drum</h3>
                            </div> <!-- end col -->
                        </div> <!-- end row-->
                    </div> <!-- end card body -->
                    <div class="card-footer py-2 bg-light bg-opacity-50">
                        <div class="d-flex align-items-center justify-content-between">
                            @php
                                $change = $percentChange['Trafo'] ?? 0;
                                $changeIcon = $change >= 0 ? 'bx bxs-up-arrow' : 'bx bxs-down-arrow';
                                $changeColor = $change >= 0 ? 'text-success' : 'text-danger';
                            @endphp
                            <div>
                                <span class="{{ $changeColor }}"> 
                                    <i class="{{ $changeIcon }} fs-12"></i> {{ abs(round($change, 1)) }}%
                                </span>
                                <span class="text-muted ms-1 fs-12">Last Month</span>
                            </div>
                        </div>
                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->
            <div class="col-md-6">
                <div class="card overflow-hidden">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6">
                                <div class="avatar-md bg-soft-primary rounded">
                                <iconify-icon icon="solar:fuel-bold-duotone"
                                class="avatar-title fs-32 text-primary"></iconify-icon>
                                </div>
                            </div> <!-- end col -->
                            <div class="col-6 text-end">
                                <p class="text-muted mb-0 text-truncate">Oli Service</p>
                                <h3 class="text-dark mt-1 mb-0">{{ $totalOliCurrent['Service'] ?? 0 }} Drum</h3>
                            </div> <!-- end col -->
                        </div> <!-- end row-->
                    </div> <!-- end card body -->
                    <div class="card-footer py-2 bg-light bg-opacity-50">
                        <div class="d-flex align-items-center justify-content-between">
                             @php
                                $change = $percentChange['Service'] ?? 0;
                                $changeIcon = $change >= 0 ? 'bx bxs-up-arrow' : 'bx bxs-down-arrow';
                                $changeColor = $change >= 0 ? 'text-success' : 'text-danger';
                            @endphp
                            <div>
                                <span class="{{ $changeColor }}"> 
                                    <i class="{{ $changeIcon }} fs-12"></i> {{ abs(round($change, 1)) }}%
                                </span>
                                <span class="text-muted ms-1 fs-12">Last Month</span>
                            </div>
                        </div>
                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->
            <div class="col-md-6">
                <div class="card overflow-hidden">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6">
                                <div class="avatar-md bg-soft-primary rounded">
                                    <iconify-icon icon="solar:fuel-broken"
                                    class="avatar-title fs-32 text-primary"></iconify-icon>
                                </div>
                            </div> <!-- end col -->
                            <div class="col-6 text-end">
                                <p class="text-muted mb-0 text-truncate">Minarex</p>
                                <h3 class="text-dark mt-1 mb-0">{{ $totalOliCurrent['Minarex'] ?? 0 }} Drum</h3>
                            </div> <!-- end col -->
                        </div> <!-- end row-->
                    </div> <!-- end card body -->
                    <div class="card-footer py-2 bg-light bg-opacity-50">
                        <div class="d-flex align-items-center justify-content-between">
                        @php
                                $change = $percentChange['Minarex'] ?? 0;
                                $changeIcon = $change >= 0 ? 'bx bxs-up-arrow' : 'bx bxs-down-arrow';
                                $changeColor = $change >= 0 ? 'text-success' : 'text-danger';
                            @endphp
                            <div>
                                <span class="{{ $changeColor }}"> 
                                    <i class="{{ $changeIcon }} fs-12"></i> {{ abs(round($change, 1)) }}%
                                </span>
                                <span class="text-muted ms-1 fs-12">Last Month</span>
                            </div>
                        </div>
                    </div> <!-- end card body -->
                </div> <!-- end card -->
            </div> <!-- end col -->
        </div> <!-- end row -->
    </div> <!-- end col -->

    <div class="col-xxl-7">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="card-title">Received Oli Demographic</h4>
                    <div>
                        <a href="{{ route('oli.index', array_merge(request()->all(), ['filter' => '1M'])) }}" type="button" class="btn btn-sm btn-outline-light {{ $filter == '1M' ? 'active' : '' }}">1M</a>
                        <a href="{{ route('oli.index', array_merge(request()->all(), ['filter' => '6M'])) }}" type="button" class="btn btn-sm btn-outline-light {{ $filter == '6M' ? 'active' : '' }}">6M</a>
                        <a href="{{ route('oli.index', array_merge(request()->all(), ['filter' => '1Y'])) }}" type="button" class="btn btn-sm btn-outline-light {{ $filter == '1Y' ? 'active' : '' }}">1Y</a>
                    </div>
                </div> <!-- end card-title-->

                <div dir="ltr">
                    <div id="dash-performance-chart" class="apex-charts"></div>
                </div>
            </div> <!-- end card body -->
        </div> <!-- end card -->
    </div> <!-- end col -->

</div> <!-- end row -->

<div class="row">
    <div class="col">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <h4 class="card-title">
                        Oli Data
                    </h4>
                </div>
            </div>
            <!-- end card body -->

            <!-- Search Input -->
            <div class="mb-3 mx-3">
                <label for="" class="mb-2">Search Data</label>
                <input type="text" id="search-input" class="form-control" placeholder="Search by menu name" value="{{ request()->get('search') }}">
            </div>

            <!-- Table -->
            <div id="table-search">
                <table class="table mb-0">
                    <thead class="bg-light bg-opacity-50">
                        <tr>
                            <th class="ps-3">Tanggal</th>
                            <th>Pengirim</th>
                            <th>Jenis Oli</th>
                            <th>Jumlah</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="user-table-body">
                        @foreach($oli as $data)
                            <tr>
                                <td class="ps-3"> {{ $data->tanggal }} </td>
                                <td> {{ $data->pengirim }} </td>
                                <td> {{ $data->jenis_oli }} </td>
                                <td> {{ $data->jumlah }} </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="" class="btn btn-soft-primary btn-sm">
                                            <iconify-icon icon="solar:pen-2-broken" class="align-middle fs-18"></iconify-icon>
                                        </a>
                                        <a href="#!" class="btn btn-soft-danger btn-sm">
                                            <iconify-icon icon="solar:trash-bin-minimalistic-2-broken" class="align-middle fs-18"></iconify-icon>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <tfoot>
                    <div class="d-flex justify-content-between mx-3 mt-2 mb-2">
                        <div>
                            Showing {{ $oli->firstItem() }} to {{ $oli->lastItem() }} of {{ $oli->total() }} entries
                        </div>
                        <div class="">
                            {{ $oli->links('pagination::bootstrap-4') }}  <!-- Pagination links -->
                        </div>
                    </div>
                </tfoot>
            </div>

        </div>

        <!-- end card -->
    </div>
    <!-- end col -->
</div>
@endsection

@section('script')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        // Pass PHP array to JavaScript
        const chartData = @json($chartData);

        // Function to format date as 'YYYY-MM'
        const formatDateToMonth = (date) => {
            const d = new Date(date);
            const month = (d.getMonth() + 1).toString().padStart(2, '0');
            const year = d.getFullYear();
            return `${year}-${month}`;
        };

        // Format categories to group by month, but retain full date
        const formattedCategories = chartData.categories.map(date => new Date(date).toLocaleDateString('en-CA'));

        // Update series data without filtering by month
        const formattedSeries = chartData.series.map(series => ({
            ...series,
            data: series.data.map((value, index) => ({
                x: new Date(chartData.categories[index]).toLocaleDateString('en-CA'),
                y: value
            }))
        }));

        // Initial chart options
        const options = {
            series: formattedSeries,  // Use the formatted series data
            chart: {
                height: 313,
                type: "bar",
                stacked: true,
                toolbar: { show: false },
            },
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: "30%",
                    barHeight: "70%",
                    borderRadius: 3,
                },
            },
            fill: {
                opacity: [1, 1, 1, 1],
                type: ['solid', 'solid', 'solid', 'solid'],
            },
            stroke: { width: 0 },
            xaxis: {
                categories: formattedCategories,  // Use the formatted categories (with full date)
                axisTicks: { show: false },
                axisBorder: { show: false },
            },
            yaxis: {
                min: 0,
                axisBorder: { show: false },
            },
            grid: {
                show: true,
                strokeDashArray: 3,
                xaxis: { lines: { show: false } },
                yaxis: { lines: { show: true } },
                padding: { top: 0, right: -2, bottom: 0, left: 10 },
            },
            legend: {
                show: true,
                horizontalAlign: "center",
                offsetX: 0,
                offsetY: 5,
                markers: { width: 9, height: 9, radius: 6 },
                itemMargin: { horizontal: 10, vertical: 0 },
            },
            colors: ["#ff6c2f", "#22c55e", "#ffc512", "074799"],
            tooltip: {
                shared: true,
                intersect: false,
                y: [
                    { formatter: (y) => (typeof y !== "undefined" ? parseFloat(y.toFixed(1)) + " Drum" : y) },
                    { formatter: (y) => (typeof y !== "undefined" ? parseFloat(y.toFixed(1)) + " Drum" : y) },
                    { formatter: (y) => (typeof y !== "undefined" ? parseFloat(y.toFixed(1)) + " Drum" : y) },
                ],
            },
        };

        // Initialize the chart
        const chart = new ApexCharts(document.querySelector("#dash-performance-chart"), options);
        chart.render();
    </script>
    <script>
        $(document).ready(function() {
        // Trigger an AJAX request on keyup event
            $('#search-input').on('keyup', function() {
                var search = $(this).val();  // Get the search input value
                var page = $('.pagination .active a').text() || 1;  // Get the current page, default to 1

                $.ajax({
                    url: "{{ route('oli.index') }}",  // Route for user list
                    method: 'GET',
                    data: { 
                        search: search,  // Send the search query
                        page: page       // Send the current page number
                    },
                    success: function(response) {
                        $('#user-table-body').html($(response).find('#user-table-body').html());  // Replace table body with filtered data
                        $('.pagination').html($(response).find('.pagination').html());  // Replace pagination
                    }
                });
            });

            // Handle pagination click
            $(document).on('click', '.pagination a', function(event) {
                event.preventDefault();
                
                var page = $(this).attr('href').split('page=')[1];  // Extract the page number from the link
                var search = $('#search-input').val();  // Get the search input value

                $.ajax({
                    url: "{{ route('oli.index') }}",  // Route for user list
                    method: 'GET',
                    data: { 
                        search: search,  // Send the search query
                        page: page       // Send the page number
                    },
                    success: function(response) {
                        $('#user-table-body').html($(response).find('#user-table-body').html());  // Replace table body with filtered data
                        $('.pagination').html($(response).find('.pagination').html());  // Replace pagination
                    }
                });
            });
        });
    </script>


@endsection
