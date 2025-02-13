<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
// Model Data
use App\Models\General\ListOrder;
use App\Models\General\OrderItem;
use App\Models\General\Distributor;
use App\Models\Product\Product;
use Carbon\Carbon;

class dashboardController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->input('start_date', now()->subMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));
        try {

            // Order Total
            $totalOrdersCurrentMonth = OrderItem::select(DB::raw('SUM(total_order) as total_orders'))
            ->whereMonth('created_at', '=', now()->month) // Current month
            ->first();

            // Fetch Total Orders for the previous month
            $totalOrdersLastMonth = OrderItem::select(DB::raw('SUM(total_order) as total_orders'))
                ->whereMonth('created_at', '=', now()->subMonth()->month) // Previous month
                ->first();

            // Calculate the percentage change in total orders
            if ($totalOrdersLastMonth && $totalOrdersLastMonth->total_orders > 0) {
                $percentageChangeTotalOrders = (($totalOrdersCurrentMonth->total_orders - $totalOrdersLastMonth->total_orders) / $totalOrdersLastMonth->total_orders) * 100;
            } else {
                $percentageChangeTotalOrders = 0; // No change if there was no data for the previous month
            }

            // Top Delivered Total
            $totalDelivered = ListOrder::whereMonth('created_at', '=', now()->month) // Current month
                ->count(); // Count total records

            // Fetch total records for the previous month
            $totalOrdersPreviousMonth = ListOrder::whereMonth('created_at', '=', now()->subMonth()->month) // Previous month
                ->count(); // Count total records

            // Calculate the percentage change (if needed)
            $percentageChangeDelivered = 0;
            if ($totalOrdersPreviousMonth > 0) {
                $percentageChangeDelivered = (($totalDelivered - $totalOrdersPreviousMonth) / $totalOrdersPreviousMonth) * 100;
            }

             // Fetch top product for the current month
                $topProductCurrentMonth = OrderItem::select('products.name as nama_produk',
                        DB::raw('SUM(total_order) as total_ordered'),
                        DB::raw('SUM(jumlah_kirim) as total_delivered'))
                    ->join('products', 'order_items.nama_produk', '=', 'products.id')
                    ->whereMonth('order_items.created_at', '=', now()->month) // Current month
                    ->groupBy('products.name')
                    ->orderByDesc('total_delivered') // Order by total delivered in descending order
                    ->limit(1) // Get the top product only
                    ->first(); 

            // Fetch the same product for the previous month
            $topProductPreviousMonth = OrderItem::select('products.name as nama_produk',
                    DB::raw('SUM(total_order) as total_ordered'),
                    DB::raw('SUM(jumlah_kirim) as total_delivered'))
                ->join('products', 'order_items.nama_produk', '=', 'products.id')
                ->whereMonth('order_items.created_at', '=', now()->subMonth()->month) // Previous month
                ->groupBy('products.name')
                ->orderByDesc('total_delivered') // Order by total delivered in descending order
                ->limit(1) // Get the top product only
                ->first();

            // Calculate the percentage change for the product's total delivered
            $percentageChangetopProduct = 0;

            // Ensure both current and previous month data exist before calculation
            if ($topProductCurrentMonth && $topProductPreviousMonth && $topProductPreviousMonth->total_delivered > 0) {
                $percentageChangetopProduct = (($topProductCurrentMonth->total_delivered - $topProductPreviousMonth->total_delivered) 
                                / $topProductPreviousMonth->total_delivered) * 100;
            }

            

            // Top Produk - Produk yang Laku
            $topProducts = OrderItem::select('products.name as nama_produk', 
                DB::raw('SUM(total_order) as total_ordered'), 
                DB::raw('SUM(jumlah_kirim) as total_delivered'))
                ->join('products', 'order_items.nama_produk', '=', 'products.id') // Assuming 'product_id' in 'order_items' and 'id' in 'products'
                ->groupBy('products.name') // Grouping by product name
                ->orderByDesc('total_delivered') // Order by total delivered in descending order
                ->limit(5) // Get top 5 products
                ->get();
    
            // Top Sales - Sales dengan Jumlah Kirim Terbanyak
            $topSales = OrderItem::select('sales', 
                DB::raw('SUM(jumlah_kirim) as total_delivered'))
                ->groupBy('sales')
                ->orderByDesc('total_delivered')
                ->limit(5) // Ambil 5 sales teratas
                ->get();
    
            // Top Distributor - Distributor dengan Total Order Terbanyak
            $topDistributors = OrderItem::select('list_orders.customer', 
                    DB::raw('SUM(order_items.total_order) as total_ordered'))
                ->join('list_orders', 'order_items.list_order_id', '=', 'list_orders.id') // Join OrderItem dengan ListOrder
                ->groupBy('list_orders.customer')
                ->orderByDesc('total_ordered')
                ->limit(5) // Ambil 5 distributor teratas
                ->get();
    
            // Mengambil nama distributor untuk setiap customer ID
            foreach ($topDistributors as $distributor) {
                $distributor->distributor_name = Distributor::find($distributor->customer)->name;
            }


            // Graphic Data Product
            $search = $request->get('search');
            $filter = $request->get('filter', '1M');
            $now = Carbon::now();

            if ($filter === 'custom') {
                $startDate = Carbon::parse($request->get('start_date'))->startOfDay();
                $endDate = Carbon::parse($request->get('end_date'))->endOfDay();
            } else {
                switch ($filter) {
                    case '1M':
                        $startDate = $now->copy()->subMonth()->startOfDay();
                        $endDate = $now->copy()->endOfDay();
                        break;
                    case '6M':
                        $startDate = $now->copy()->subMonths(6)->startOfDay();
                        $endDate = $now->copy()->endOfDay();
                        break;
                    case '1Y':
                        $startDate = $now->copy()->subYear()->startOfDay();
                        $endDate = $now->copy()->endOfDay();
                        break;
                    default:
                        $startDate = OrderItem::min('tanggal_kirim') ?? $now->copy()->subYears(5);
                        $endDate = $now->copy()->endOfDay();
                        break;
                }
            }

            // Fetch data
            $orderItems = OrderItem::whereBetween('tanggal_kirim', [$startDate, $endDate])
                ->join('products', 'order_items.nama_produk', '=', 'products.id')
                ->selectRaw('DATE(order_items.tanggal_kirim) as date, products.name as nama_produk, SUM(order_items.total_order) as total')
                ->groupBy('date', 'products.name')
                ->orderBy('date', 'asc')
                ->get();

            // Prepare data for ApexCharts
            $products = Product::pluck('name')->toArray();
            $dataSeries = [];
            $dates = [];

            foreach ($products as $productName) {
                $dataSeries[$productName] = [];
            }

            $orderItems->each(function ($item) use (&$dates) {
                if (!in_array($item->date, $dates)) {
                    $dates[] = $item->date;
                }
            });

            foreach ($products as $productName) {
                foreach ($dates as $date) {
                    $dataSeries[$productName][] = 0;
                }
            }

            foreach ($orderItems as $item) {
                $index = array_search($item->date, $dates);
                if ($index !== false && in_array($item->nama_produk, $products)) {
                    $dataSeries[$item->nama_produk][$index] = round((float) $item->total, 1);
                }
            }

            $chartData = [
                'categories' => $dates,
                'series' => []
            ];

            foreach ($products as $productName) {
                $chartData['series'][] = [
                    'name' => $productName,
                    'data' => $dataSeries[$productName],
                ];
            }

            $chartData['labels'] = $dates;


            $listorder = ListOrder::orderBy('created_at', 'desc')->take(5)->get();


            
            return view('dashboards.index', [
                'topProducts' => $topProducts,
                'topSales' => $topSales,
                'topDistributors' => $topDistributors,
                'totalOrdersCurrentMonth' => $totalOrdersCurrentMonth->total_orders,
                'percentageChangeTotalOrders' => $percentageChangeTotalOrders,
                'totalDelivered' => $totalDelivered,
                'percentageChangeDelivered' => $percentageChangeDelivered,
                'topProductCurrentMonth' => $topProductCurrentMonth,
                'percentageChangetopProduct' => $percentageChangetopProduct,
                'chartData' => $chartData,
                'filter' => $filter,
                'dataorder' => $listorder

            ]);
        } catch (Exception $e) {
            Log::error('Error fetching dashboard data: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error fetching dashboard data: ' . $e->getMessage());
        }
    }
}
