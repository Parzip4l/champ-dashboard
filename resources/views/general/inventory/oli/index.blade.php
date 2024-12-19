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
                                <th>Tanggal</th>
                                <th>Pengirim</th>
                                <th>Jenis Oli</th>
                                <th>Jumlah</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="user-table-body">
                            @foreach($oli as $data)
                                <tr>
                                    <td> {{ $data->tanggal }} </td>
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
    @vite(['resources/js/pages/dashboard.js'])
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
