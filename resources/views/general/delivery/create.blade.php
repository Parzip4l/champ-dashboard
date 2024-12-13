@extends('layouts.vertical', ['title' => 'Create Delivery Order'])

@section('css')
@vite(['node_modules/choices.js/public/assets/styles/choices.min.css'])
@endsection

@section('content')
@if (session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
@endif
<div class="row">
    <div class="col-xl-12 col-lg-12 ">
    <form action="{{ route('delivery-order.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Distributor / Customer Information</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-4">
                        <div class="mb-3">
                            <label for="product-name" class="form-label">Distributor Name</label>
                            <select class="form-control" name="distributor" id="distributor" data-choices data-choices-groups data-placeholder="Select Distributor" name="choices-single-groups">
                                <option value="">Choose a Distributor</option>
                                    @foreach($distributor as $data)
                                    <option value="{{$data->id}}">{{$data->name}}</option>
                                    @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <label for="product-categories" class="form-label">Tanggal Order</label>
                        <input type="date" name="tanggal_order" id="size-product" class="form-control" placeholder="15">
                    </div>
                    <div class="col-lg-4">
                        <label for="product-categories" class="form-label">Tanggal Kirim</label>
                        <input type="date" name="tanggal_kirim" id="size-product" class="form-control" placeholder="15">
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-4">
                        <label for="weight-product" class="form-label">PPN</label>
                        <select class="form-control" name="ppn" id="weight-product" data-placeholder="Select PPn">
                            <option value="">Choose a PPn</option>
                            <option value="Ppn">Ppn</option>
                            <option value="Non Ppn">Non Ppn</option>
                        </select>
                    </div>
                    <div class="col-lg-4">
                        <div class="mb-3">
                            <label for="size-product" class="form-label">Ekspedisi</label>
                            <input type="text" name="ekspedisi" id="size-product" class="form-control" placeholder="Ekspedisi">
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="mb-3">
                            <label for="size-product" class="form-label">Status</label>
                            <select class="form-control" name="status" id="weight-product" data-choices data-choices-groups data-placeholder="Select Status" name="choices-single-groups">
                            <option value="">Choose a Status</option>
                            <option value="Delivered">Delivered</option>
                            <option value="Delayed">Delayed</option>
                            <option value="On Process">On Process</option>
                            <option value="Cancel">Cancel</option>
                        </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Product Information</h4>
            </div>
            <div class="card-body">
                <div id="product-container">
                    <div class="row product-row" data-index="0">
                        <div class="col-lg-3">
                            <div class="mb-3">
                                <label for="product-name" class="form-label">Product Name</label>
                                <select class="form-control" name="order_items[0][nama_produk]" data-placeholder="Select Product">
                                    <option value="">Choose a Product</option>
                                    @foreach($product as $data)
                                        <option value="{{ $data->id }}">{{ $data->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <label for="product-categories" class="form-label">Total Order</label>
                            <input type="text" name="order_items[0][total_order]" class="form-control" placeholder="15">
                        </div>
                        <div class="col-lg-3">
                            <label for="product-categories" class="form-label">Total Kirim</label>
                            <input type="text" name="order_items[0][jumlah_kirim]" class="form-control" placeholder="15">
                        </div>
                        <div class="col-lg-3">
                            <label for="product-categories" class="form-label">Sisa Kiriman</label>
                            <input type="text" name="order_items[0][sisa_belum_kirim]" class="form-control" placeholder="15">
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <button type="button" class="btn btn-outline-success btn-add-product">Add Product</button>
                </div>
            </div>
        </div>

        <div class="p-3 bg-light mb-3 rounded">
            <div class="row justify-content-end g-2">
                <div class="col-lg-2">
                    <button class="btn btn-outline-secondary w-100" type="submit">Create Data</button>
                </div>
                <div class="col-lg-2">
                    <button class="btn btn-primary w-100" type="reset">Cancel</button>
                </div>
            </div>
        </div>
        </form>
    </div>
</div>

@endsection

@section('script-bottom')
@vite(['resources/js/pages/ecommerce-product-details.js'])
<script>
    document.querySelector('.btn-add-product').addEventListener('click', function() {
        // Get the index of the last added product row
        const productRows = document.querySelectorAll('.product-row');
        const lastRowIndex = parseInt(productRows[productRows.length - 1].getAttribute('data-index'));

        // Clone the first product row and increment its index
        const productRow = document.querySelector('.product-row').cloneNode(true);
        const newIndex = lastRowIndex + 1;

        // Update the index in the cloned row
        productRow.setAttribute('data-index', newIndex);

        // Update the name attributes to reflect the new index
        const inputs = productRow.querySelectorAll('input, select');
        inputs.forEach(input => {
            const name = input.name;
            const updatedName = name.replace(`[${lastRowIndex}]`, `[${newIndex}]`);
            input.name = updatedName;
        });

        // Reset input values in the cloned row
        inputs.forEach(input => {
            input.value = '';
        });

        // Append the cloned row to the container
        document.getElementById('product-container').appendChild(productRow);
    });

</script>

@endsection