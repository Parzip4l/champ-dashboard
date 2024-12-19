@extends('layouts.vertical', ['title' => 'Penerimaan Oli Bulan Ini'])

@push('plugin-styles')
  <link href="{{ asset('assets/plugins/datatables-net-bs5/dataTables.bootstrap5.css') }}" rel="stylesheet" />
  <link href="{{ asset('assets/plugins/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" />
@endpush

@section('content')
<div class="row">
    <div class="col-md-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-header">
                <h4>Form Pencatatan Pengiriman Oli</h4>
            </div>
            <div class="card-body">
            <form action="{{route('oli.store')}}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-12 mb-2">
                            <label for="" class="form-label">Pengirim</label>
                            <input type="text" class="form-control" name="pengirim" required>    
                        </div>
                        <div class="col-md-12 mb-2">
                            <label for="" class="form-label">Jenis Oli</label>
                            <select name="jenis_oli" class="form-control" id="" required>
                                <option value="Bahan">Bahan</option>
                                <option value="Service">Service</option>
                                <option value="Trafo">Trafo</option>
                                <option value="Minarex">Minarex</option>
                            </select>   
                        </div>
                        <div class="col-md-12 mb-2">
                            <label for="" class="form-label">Jumlah</label>
                            <input type="text" class="form-control" name="jumlah" placeholder="2 Drum" required>       
                        </div>
                        <div class="col-md-12 mt-2">
                            <button class="btn btn-primary w-100" type="submit">Kirim Data</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('plugin-scripts')
  <script src="{{ asset('assets/plugins/datatables-net/jquery.dataTables.js') }}"></script>
  <script src="{{ asset('assets/plugins/datatables-net-bs5/dataTables.bootstrap5.js') }}"></script>
  <script src="{{ asset('assets/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
@endpush

@push('custom-scripts')
  <script src="{{ asset('assets/js/data-table.js') }}"></script>
  <script src="{{ asset('assets/js/sweet-alert.js') }}"></script>
<script>
    @if(session('success'))
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: '{{ session('success') }}',
        });
    @endif

    @if(session('error'))
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: '{{ session('error') }}',
        });
    @endif
</script>
@endpush