<?php

namespace App\Http\Controllers\Produck;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

// Model
use App\Models\Product\Product;
use App\Models\Product\ProductImage;

class ProdukController extends Controller
{

    public function index()
    {
        // Ambil produk dengan pagination
        $products = Product::with('images')->paginate(10);
        // Kirim data ke view
        return view('general.products.list', compact('products'));
    }

    public function create()
    {
        return view('general.products.create');
    }
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);
    
        try {
            // Mulai transaksi database
            \DB::beginTransaction();
    
            // Buat produk
            $product = Product::create([
                'name' => $request->name,
                'categories' => $request->categories,
                'weight' => $request->weight,
                'size' => $request->size,
                'tag_number' => $request->tag_number,
                'stock' => $request->stock,
                'tags' => $request->tags,
            ]);
    
            // Upload gambar jika ada
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $path = $image->store('product_images', 'public');
                    ProductImage::create([
                        'product_id' => $product->id,
                        'file_path' => $path,
                    ]);
                }
            }
    
            // Commit transaksi
            \DB::commit();
    
            return redirect()->back()->with('success', 'Product created successfully!');
        } catch (\Exception $e) {
            // Rollback transaksi jika terjadi error
            \DB::rollBack();
    
            // Log error untuk debugging
            Log::error('Product Creation Error: ' . $e->getMessage());
    
            // Redirect dengan pesan error
            return redirect()->back()->with('error', 'Failed to create product: ' . $e->getMessage());
        }
    }
}
