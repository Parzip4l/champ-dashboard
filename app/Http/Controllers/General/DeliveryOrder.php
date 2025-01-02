<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;

// Model
use App\Models\General\ListOrder;
use App\Models\General\OrderItem;
use App\Models\General\Distributor;
use App\Models\Product\Product;
use App\Models\User;
use App\Models\Setting\Role;
use Illuminate\Support\Facades\Log;

class DeliveryOrder extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');
        $status = $request->get('status');  // Get the selected status

        // Query to get the list of orders with the option to filter by search and status
        $listorder = ListOrder::with(['distributor', 'orderItems.product'])  // Eager load distributor and product relationships
            ->when($search, function ($query, $search) {
                return $query->whereHas('distributor', function ($query) use ($search) {
                    $query->where('name', 'like', '%' . $search . '%');
                });
            })
            ->when($status, function ($query, $status) {
                return $query->where('status', $status);  // Filter by status if provided
            })
            ->paginate(10);
        $deliverySuccessCount = ListOrder::where('status', 'Delivered')->count(); 
        $onProcessCount = ListOrder::where('status', 'On Process')->count();  
        $delayedCount = ListOrder::where('status', 'Delayed')->count(); 
        $notDeliveredCount = ListOrder::where('status', 'Cancel')->count();
        // If the request is AJAX, return the partial view with updated data
        if ($request->ajax()) {
            return view('general.delivery.index', compact('listorder','deliverySuccessCount', 'onProcessCount', 'delayedCount', 'notDeliveredCount'))->render();
        }

        

        // For regular view, return the full page
        return view('general.delivery.index', compact('listorder', 'search', 'status','deliverySuccessCount', 'onProcessCount', 'delayedCount', 'notDeliveredCount'));
    }


    public function create()
    {
        $distributor = Distributor::all();
        $product = Product::all();
        $sales = User::join('roles', 'users.role_id', '=', 'roles.id')
             ->where('roles.name', 'sales')
             ->select('users.*')
             ->get();
        return view('general.delivery.create', compact('distributor','product','sales'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'tanggal_order' => 'nullable|date',
            ]);

            $listOrder = new ListOrder();
            $listOrder->customer = $request->distributor;
            $listOrder->tanggal_terima_order = $request->tanggal_order;
            $listOrder->maks_kirim = Carbon::parse($request->tanggal_order)->addDays(7);
            $listOrder->ppn = $request->ppn;
            $listOrder->ekspedisi = $request->ekspedisi;
            $listOrder->status = $request->status;
            $listOrder->save();

            // Now create order items using the validated data
            foreach ($request->order_items as $item) {
                $orderItem = new OrderItem();
                $orderItem->list_order_id = $listOrder->id; // Associate with the ListOrder
                $orderItem->tanggal_kirim = $request->tanggal_kirim;
                $orderItem->sales = $request->sales;
                $orderItem->nama_produk = $item['nama_produk']; // Make sure the array structure matches
                $orderItem->total_order = $item['total_order'];
                $orderItem->jumlah_kirim = $item['jumlah_kirim'];
                $orderItem->sisa_belum_kirim = $item['sisa_belum_kirim'] ?? 0; // Default to 0 if null
                $orderItem->save();
            }

            return redirect()->route('delivery-order.index')->with('success', 'Data information saved successfully!');
        } catch (Exception $e) {
            Log::error('Error storing menu: ' . $e->getMessage());
            return redirect()->route('delivery-order.index')->with('error', 'Data information error!' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $listOrder = ListOrder::with('orderItems')->findOrFail($id);
            return view('general.delivery.edit', compact('listOrder'));
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }

    public function edit($id)
    {
        try {
            $listOrder = ListOrder::with('orderItems')->findOrFail($id);
            $distributor = Distributor::all();
            $product = Product::all();
            return view('general.delivery.edit', compact('listOrder','distributor','product'));
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            // Validate the incoming request
            $validated = $request->validate([
                'customer' => 'nullable|string',
            ]);

            // Find the ListOrder by ID and update it
            $listOrder = ListOrder::findOrFail($id);
            $listOrder->customer = $request->distributor; // Use distributor instead of customer
            $listOrder->tanggal_terima_order = $request->tanggal_order;
            $listOrder->maks_kirim = Carbon::parse($request->tanggal_order)->addDays(7); // Set max shipping date
            $listOrder->ppn = $request->ppn;
            $listOrder->ekspedisi = $request->ekspedisi;
            $listOrder->status = $request->status; // Update only the status
            $listOrder->save();

            // Only update order items if there are changes in the order_items field
            if ($request->has('order_items') && !empty($request->order_items)) {
                foreach ($request->order_items as $item) {
                    // Cek apakah item memiliki ID (update) atau tidak (create baru)
                    if (isset($item['id']) && $item['id']) {
                        $orderItem = OrderItem::find($item['id']);
                        if ($orderItem) {
                            $orderItem->update([
                                'nama_produk' => $item['nama_produk'],
                                'total_order' => $item['total_order'],
                                'jumlah_kirim' => $item['jumlah_kirim'],
                                'sisa_belum_kirim' => $item['sisa_belum_kirim'] ?? 0,
                                'tanggal_kirim' => $item['tanggal_kirim'] ?? null,
                            ]);
                        }
                    } else {
                        // Pastikan tidak membuat item baru yang identik dengan yang sudah ada
                        $existingItem = $listOrder->orderItems()
                            ->where('nama_produk', $item['nama_produk'])
                            ->where('tanggal_kirim', $item['tanggal_kirim'])
                            ->first();
            
                        if (!$existingItem) {
                            $listOrder->orderItems()->create([
                                'nama_produk' => $item['nama_produk'],
                                'total_order' => $item['total_order'],
                                'jumlah_kirim' => $item['jumlah_kirim'] ?? 0,
                                'sisa_belum_kirim' => $item['sisa_belum_kirim'] ?? 0,
                                'tanggal_kirim' => $item['tanggal_kirim'] ?? null,
                            ]);
                        }
                    }
                }
            }
            

            // Return a success response
            return redirect()->route('delivery-order.index')->with('success', 'Data information updated successfully!');
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }




    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $listOrder = ListOrder::findOrFail($id);
            $listOrder->delete();

            return response()->json(['message' => 'Order deleted successfully.']);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
