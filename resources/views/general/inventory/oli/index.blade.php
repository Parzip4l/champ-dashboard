@extends('layouts.vertical', ['title' => 'Penerimaan Oli Bulan Ini'])

@section('content')
<div class="row">
    <div class="col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="card-title mb-2">Oli Bahan</h4>
                        <p id="oliBahanTotal" class="text-muted fw-medium fs-22 mb-0">0 Drum</p>
                    </div>
                    <div>
                        <div class="avatar-md bg-primary bg-opacity-10 rounded">
                            <iconify-icon icon="solar:fuel-outline" class="fs-32 text-primary avatar-title"></iconify-icon>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="card-title mb-2">Oli Trafo</h4>
                        <p id="oliTrafoTotal" class="text-muted fw-medium fs-22 mb-0">0 Drum</p>
                    </div>
                    <div>
                        <div class="avatar-md bg-primary bg-opacity-10 rounded">
                            <iconify-icon icon="solar:fuel-bold" class="fs-32 text-primary avatar-title"></iconify-icon>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="card-title mb-2">Oli Service</h4>
                        <p id="oliServiceTotal" class="text-muted fw-medium fs-22 mb-0">0 Drum</p>
                    </div>
                    <div>
                        <div class="avatar-md bg-primary bg-opacity-10 rounded">
                            <iconify-icon icon="solar:fuel-bold-duotone" class="fs-32 text-primary avatar-title"></iconify-icon>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="card-title mb-2">Minarex</h4>
                        <p id="oliMinarex" class="text-muted fw-medium fs-22 mb-0">0 Drum</p>
                    </div>
                    <div>
                        <div class="avatar-md bg-primary bg-opacity-10 rounded">
                            <iconify-icon icon="solar:fuel-broken" class="fs-32 text-primary avatar-title"></iconify-icon>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

    <div class="row">
        <div class="col">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <h4 class="card-title">
                            50 Data Terakhir Pengiriman Oli
                        </h4>
                    </div>
                </div>
                <!-- end card body -->

                <!-- Table -->
                <div id="table-search">
                    <table class="table mb-0">
                        <thead class="bg-light bg-opacity-50">
                            <tr>
                                <th class="ps-3">
                                    Pengirim
                                </th>
                                <th>Tanggal</th>
                                <th>
                                    Jenis
                                </th>
                                <th>
                                    Jumlah
                                </th>
                                <th>
                                    Action
                                </th>
                            </tr>
                        </thead>
                        <tbody id="user-table-body">
                            <!-- Data will be inserted here -->
                        </tbody>
                    </table>

                    <div id="pagination-container" class="d-flex justify-content-between mx-3 mt-2 mb-2">
                        <!-- Pagination will be inserted here -->
                    </div>
                </div>
            </div>

            <!-- end card -->
        </div>
        <!-- end col -->
    </div>
@endsection

@section('script')
    @vite(['resources/js/pages/dashboard.js'])
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    $(document).ready(function() {
    const apiUrl = "https://portal.champoil.co.id/api/pengiriman-oli"; // API endpoint baru

    // Get current month (1-12)
    const currentMonth = new Date().getMonth() + 1;

    // Function to get cached data from localStorage
    function getCachedData() {
        const cachedData = localStorage.getItem('totalOliData');
        return cachedData ? JSON.parse(cachedData) : null;
    }

    // Function to cache data in localStorage
    function cacheData(data) {
        localStorage.setItem('totalOliData', JSON.stringify(data));
    }

    // Fetch data function
    function fetchData() {
        // Check if data is available in cache
        const cachedData = getCachedData();

        // If data is cached, use it
        if (cachedData) {
            updateTotals(cachedData);
        } else {
            // If not cached, fetch data from API
            $.ajax({
                url: apiUrl,
                method: 'GET',
                data: {
                    month: currentMonth // Include current month in the request
                },
                success: function(response) {
                    if (response.success) {
                        // Cache the response data
                        cacheData(response.data.total_oli_per_jenis);
                        updateTotals(response.data.total_oli_per_jenis); // Only update totals
                    } else {
                        $('#user-table-body').html('<tr><td colspan="5" class="text-center">No data found</td></tr>');
                    }
                },
                error: function() {
                    $('#user-table-body').html('<tr><td colspan="5" class="text-center">Error fetching data</td></tr>');
                }
            });
        }
    }

    // Update totals for current month
    function updateTotals(totalOliData) {
        let totals = {
            'Oli Bahan': 0,
            'Oli Trafo': 0,
            'Oli Service': 0,
            'Oli Minarex': 0
        };

        // Ensure the totals are reset to zero for the current month
        if (totalOliData) {
            // Add up the totals for each type of oil for the current month
            ['Bahan', 'Trafo', 'Service', 'Minarex'].forEach(jenisOli => {
                if (totalOliData[jenisOli] && totalOliData[jenisOli][currentMonth]) {
                    totals[`Oli ${jenisOli}`] = parseFloat(totalOliData[jenisOli][currentMonth]);
                }
            });
        }

        // Update the totals on the page
        document.getElementById('oliBahanTotal').textContent = totals['Oli Bahan'] + ' Drum' || 0;
        document.getElementById('oliTrafoTotal').textContent = totals['Oli Trafo'] + ' Drum' || 0;
        document.getElementById('oliServiceTotal').textContent = totals['Oli Service'] + ' Drum' || 0;
        document.getElementById('oliMinarex').textContent = totals['Oli Minarex'] + ' Drum' || 0;
    }

    // Initial data fetch
    fetchData();
});

</script>


@endsection
