@extends('layouts.vertical', ['title' => 'Dashboard'])

@section('content')
    <div class="row">
        <div class="col-xxl-12">
            <div class="row">
                <div class="col-12">
                    <div class="alert alert-primary text-truncate mb-3" role="alert">
                        Welcome Back {{Auth::user()->name}} !
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card overflow-hidden">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6">
                                    <div class="avatar-md bg-soft-primary rounded">
                                        <iconify-icon icon="solar:cart-5-bold-duotone"
                                                    class="avatar-title fs-32 text-primary"></iconify-icon>
                                    </div>
                                </div> <!-- end col -->
                                <div class="col-6 text-end">
                                    <p class="text-muted mb-0 text-truncate">Total Orders</p>
                                    <h3 class="text-dark mt-1 mb-0">{{ number_format($totalOrdersCurrentMonth) }}</h3>
                                </div> <!-- end col -->
                            </div> <!-- end row-->
                             <!-- end card footer -->
                        </div> <!-- end card body -->
                        <div class="card-footer py-2 bg-light bg-opacity-50">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="{{ $percentageChangeTotalOrders >= 0 ? 'text-success' : 'text-danger' }}">
                                        <i class="bx {{ $percentageChangeTotalOrders >= 0 ? 'bxs-up-arrow' : 'bxs-down-arrow' }} fs-12"></i> 
                                        {{ number_format($percentageChangeTotalOrders, 2) }}%
                                    </span>
                                    <span class="text-muted ms-1 fs-12">Last Month</span>
                                </div>
                                <a href="#!" class="text-reset fw-semibold fs-12">View More</a>
                            </div>
                        </div> 
                    </div> <!-- end card -->
                </div> <!-- end col -->
                <div class="col-md-3">
                    <div class="card overflow-hidden">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6">
                                    <div class="avatar-md bg-soft-primary rounded">
                                        <i class="bx bx-award avatar-title fs-24 text-primary"></i>
                                    </div>
                                </div> <!-- end col -->
                                <div class="col-6 text-end">
                                    <p class="text-muted mb-0 text-truncate">Delivery Success</p>
                                    <h3 class="text-dark mt-1 mb-0">{{$totalDelivered ?? 0}}</h3>
                                </div> <!-- end col -->
                            </div> <!-- end row-->
                        </div> <!-- end card body -->
                        <div class="card-footer py-2 bg-light bg-opacity-50">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="{{ $percentageChangeDelivered >= 0 ? 'text-success' : 'text-danger' }}">
                                        <i class="bx {{ $percentageChangeDelivered >= 0 ? 'bxs-up-arrow' : 'bxs-down-arrow' }} fs-12"></i> 
                                        {{ number_format($percentageChangeDelivered, 2) }}%
                                    </span>
                                    <span class="text-muted ms-1 fs-12">Last Month</span>
                                </div>
                                <a href="#!" class="text-reset fw-semibold fs-12">View More</a>
                            </div>
                        </div>  <!-- end card body -->
                    </div> <!-- end card -->
                </div> <!-- end col -->
                <div class="col-md-3">
                    <div class="card overflow-hidden">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6">
                                    <div class="avatar-md bg-soft-primary rounded">
                                        <i class="bx bxs-backpack avatar-title fs-24 text-primary"></i>
                                    </div>
                                </div> <!-- end col -->
                                <div class="col-6 text-end">
                                    <p class="text-muted mb-0 text-truncate">Top Products</p>
                                    <h3 class="text-dark mt-1 mb-0">{{number_format($topProductCurrentMonth->total_ordered ?? 0)}}</h3>
                                    <p class="text-muted mb-0 text-truncate"></p>
                                </div> <!-- end col -->
                            </div> <!-- end row-->
                        </div> <!-- end card body -->
                        <div class="card-footer py-2 bg-light bg-opacity-50">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="{{ $percentageChangetopProduct >= 0 ? 'text-success' : 'text-danger' }}">
                                        <i class="bx {{ $percentageChangetopProduct >= 0 ? 'bxs-up-arrow' : 'bxs-down-arrow' }} fs-12"></i> 
                                        {{ number_format($percentageChangetopProduct, 2) }}%
                                    </span>
                                    <span class="text-muted ms-1 fs-12">Last Month</span>
                                </div>
                                <a href="#!" class="text-reset fw-semibold fs-12">View More</a>
                            </div>
                        </div> <!-- end card body -->
                    </div> <!-- end card -->
                </div> <!-- end col -->
                <div class="col-md-3">
                    <div class="card overflow-hidden">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-3">
                                    <div class="avatar-md bg-soft-primary rounded">
                                        <i class="bx bx-dollar-circle avatar-title text-primary fs-24"></i>
                                    </div>
                                </div> <!-- end col -->
                                <div class="col-9 text-end">
                                    <p class="text-muted mb-0 text-truncate">Revenue</p>
                                    <h3 class="text-dark mt-1 mb-0">Rp. 123.6M</h3>
                                </div> <!-- end col -->
                            </div> <!-- end row-->
                        </div> <!-- end card body -->
                        <div class="card-footer py-2 bg-light bg-opacity-50">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="text-danger"> <i class="bx bxs-down-arrow fs-12"></i> 10.6%</span>
                                    <span class="text-muted ms-1 fs-12">Last Month</span>
                                </div>
                                <a href="#!" class="text-reset fw-semibold fs-12">View More</a>
                            </div>
                        </div> <!-- end card body -->
                    </div> <!-- end card -->
                </div> <!-- end col -->
            </div> <!-- end row -->
        </div> <!-- end col -->

        <div class="col-xxl-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="card-title">Performance</h4>
                        <div>
                            <a href="{{ route('dashboard.index', array_merge(request()->all(), ['filter' => '1M'])) }}" type="button" class="btn btn-sm btn-outline-light {{ $filter == '1M' ? 'active' : '' }}">1M</a>
                            <a href="{{ route('dashboard.index', array_merge(request()->all(), ['filter' => '6M'])) }}" type="button" class="btn btn-sm btn-outline-light {{ $filter == '6M' ? 'active' : '' }}">6M</a>
                            <a href="{{ route('dashboard.index', array_merge(request()->all(), ['filter' => '1Y'])) }}" type="button" class="btn btn-sm btn-outline-light {{ $filter == '1Y' ? 'active' : '' }}">1Y</a>
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
        <div class="col-lg-4">
            <div class="card card-height-100">
                <div class="card-header d-flex align-items-center justify-content-between gap-2">
                    <h4 class="card-title flex-grow-1">Top Distributor</h4>
                    <a href="#" class="btn btn-sm btn-soft-primary">View All</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-nowrap table-centered m-0">
                        <thead class="bg-light bg-opacity-50">
                            <tr>
                                <th class="text-muted ps-3">Distributor Name</th>
                                <th class="text-muted">Order Count</th>
                                <th class="text-muted">Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topDistributors as $distributor)
                            <tr>
                                <td class="ps-3"><a href="#" class="text-muted">{{ $distributor->distributor_name }}</a></td>
                                <td>{{ $distributor->total_ordered }}</td>
                                <td><span class="badge badge-soft-success">{{ $distributor->revenue_percentage }}</span></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div> <!-- end left chart card -->

        <div class="col-lg-4">
            <div class="card card-height-100">
                <div class="card-header d-flex align-items-center justify-content-between gap-2">
                    <h4 class="card-title flex-grow-1">Top Product</h4>
                    <a href="#" class="btn btn-sm btn-soft-primary">View All</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-nowrap table-centered m-0">
                        <thead class="bg-light bg-opacity-50">
                            <tr>
                                <th class="text-muted ps-3">Product Name</th>
                                <th class="text-muted">Quantity Sales</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topProducts as $product)
                            <tr>
                                <td class="ps-3"><a href="#" class="text-muted">{{ $product->nama_produk }}</a></td>
                                <td>{{ $product->total_delivered }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div> <!-- end col -->

        <div class="col-lg-4">
            <div class="card card-height-100">
                <div class="card-header d-flex align-items-center justify-content-between gap-2">
                    <h4 class="card-title flex-grow-1">Top Sales</h4>
                    <a href="#" class="btn btn-sm btn-soft-primary">View All</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-nowrap table-centered m-0">
                        <thead class="bg-light bg-opacity-50">
                            <tr>
                                <th class="text-muted ps-3">Sales Name</th>
                                <th class="text-muted">Sales Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($topSales as $sales)
                            <tr>
                                <td class="ps-3"><a href="#" class="text-muted">{{ $sales->sales }}</a></td>
                                <td>{{ $sales->total_delivered }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div> <!-- end col -->
    </div> <!-- end row -->


    <div class="row">
        <div class="col">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <h4 class="card-title">
                            Recent Orders
                        </h4>
                    </div>
                </div>
                <!-- end card body -->
                <div class="table-responsive table-centered">
                    <table class="table mb-0">
                        <thead class="bg-light bg-opacity-50">
                        <tr>
                            <th class="ps-3">
                                Distributor Name
                            </th>
                            <th>
                                Produk
                            </th>
                            <th>
                                Total Order
                            </th>
                            <th>
                                Total Kirim
                            </th>
                            <th>
                                Sisa Kirim
                            </th>
                            <th>
                                Status
                            </th>
                            <th>Sales</th>
                        </tr>
                        </thead>
                        <!-- end thead-->
                        <tbody>
                            @foreach($dataorder as $data)
                                @foreach($data->orderItems as $item)
                                <tr>
                                    <td class="ps-3">
                                        <a href="#">{{ $data->distributor->name ?? 'N/A' }}</a>
                                    </td>
                                    <td>{{ $item->product->name ?? 'N/A' }}</td>
                                    <td>
                                        {{ $item->total_order }}
                                    </td>
                                    <td>{{ $item->jumlah_kirim }}</td>
                                    <td>{{ $item->sisa_belum_kirim ?? '0' }}</td>
                                    <td>{{ $data->status ?? 'N/A' }}</td>
                                    <td>{{ $item->sales ?? 'N/A' }}</td>
                                </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                        <!-- end tbody -->
                    </table>
                    <!-- end table -->
                </div>
                <!-- table responsive -->

            </div>
            <!-- end card -->
        </div>
        <!-- end col -->
    </div>
@endsection

@section('script')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

    // Initial chart options for stacked area chart
    const options = {
        series: formattedSeries,  // Use the formatted series data
        chart: {
            type: 'area',  // Change chart type to area
            height: 350,   // Set height to 350px
            stacked: true,
            toolbar: {
                tools: {
                    download: true, // Disable the download button
                    selection: false, // Enable selection tool
                    zoom: false,     // Disable zooming
                    zoomin: false,   // Disable zoom in
                    zoomout: false,  // Disable zoom out
                    pan: false,      // Disable panning
                    reset: true      // Enable reset button
                },
            },
            events: {
                selection: function (chart, e) {
                    console.log(new Date(e.xaxis.min));  // Log selected date range
                }
            },
        },
        colors: ['#22c55e', '#c0392b', '#e74c3c', '#2980b9', '#1abc9c'],
        dataLabels: {
            enabled: false, // Disable data labels
        },
        stroke: {
            curve: 'monotoneCubic',  // Smooth line curve
        },
        fill: {
            type: 'gradient', // Use gradient fill
            gradient: {
                opacityFrom: 0.6,  // Starting opacity of gradient
                opacityTo: 0.8,    // Ending opacity of gradient
            }
        },
        legend: {
            position: 'top',
            horizontalAlign: 'left',
        },
        xaxis: {
            categories: formattedCategories,  // Use the formatted categories (with full date)
            type: 'category', // Use category type for x-axis
        },
        tooltip: {
            shared: true,
            intersect: false,
            y: [
                { formatter: (y) => (typeof y !== "undefined" ? parseFloat(y.toFixed(1)) : y) },
                { formatter: (y) => (typeof y !== "undefined" ? parseFloat(y.toFixed(1)) : y) },
                { formatter: (y) => (typeof y !== "undefined" ? parseFloat(y.toFixed(1)) : y) },
            ],
        },
    };

    // Initialize the chart
    const chart = new ApexCharts(document.querySelector("#dash-performance-chart"), options);
    chart.render();
</script>

@endsection
