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
            if ($topProductPreviousMonth?->total_delivered > 0) {
                $percentageChangetopProduct = (($topProductCurrentMonth->total_delivered - $topProductPreviousMonth->total_delivered) 
                                / $topProductPreviousMonth->total_delivered) * 100;
            } else {
                $percentageChangetopProduct = 0;
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
                    $startDate = OrderItem::min('tanggal_kirim') ?? $now->copy()->subYears(5); // Default to 5 years if no data
                    $endDate = $now->copy()->endOfDay();
                    break;
            }

            // Fetch product data, grouping by month for '1Y' and '6M' filters
            if ($filter === '1Y' || $filter === '6M') {
                // Group by year, month, and product name (join with Product to get the product names)
                $orderItems = OrderItem::whereBetween('tanggal_kirim', [$startDate, $endDate])
                    ->join('products', 'order_items.nama_produk', '=', 'products.id')  // Join with the Product table
                    ->selectRaw('YEAR(order_items.tanggal_kirim) as year, MONTH(order_items.tanggal_kirim) as month, products.name as nama_produk, SUM(order_items.total_order) as total')
                    ->groupBy('year', 'month', 'products.name')
                    ->orderBy('year', 'asc')
                    ->orderBy('month', 'asc')
                    ->get();
            } else {
                // Group by specific dates and product names
                $orderItems = OrderItem::whereBetween('tanggal_kirim', [$startDate, $endDate])
                    ->join('products', 'order_items.nama_produk', '=', 'products.id')  // Join with the Product table
                    ->selectRaw('DATE(order_items.tanggal_kirim) as date, products.name as nama_produk, SUM(order_items.total_order) as total')
                    ->groupBy('date', 'products.name')
                    ->orderBy('date', 'asc')
                    ->get();
            }

            // Prepare data for ApexCharts
            $products = Product::pluck('name')->toArray(); // Get all product names from the database
            $dataSeries = [];
            $dates = [];

            // Initialize data series with 0 for each product
            foreach ($products as $productName) {
                $dataSeries[$productName] = [];
            }

            // Create the categories (months or days depending on filter)
            if ($filter === '1Y' || $filter === '6M') {
                // Group by year and month (format as YYYY-MM)
                $orderItems->each(function ($item) use (&$dates) {
                    $monthYear = $item->year . '-' . str_pad($item->month, 2, '0', STR_PAD_LEFT);  // Format as YYYY-MM
                    if (!in_array($monthYear, $dates)) {
                        $dates[] = $monthYear;
                    }
                });
            } else {
                // Group by specific dates (for 1M or ALL) with full date (YYYY-MM-DD)
                $orderItems->each(function ($item) use (&$dates) {
                    if (!in_array($item->date, $dates)) {
                        $dates[] = $item->date;
                    }
                });
            }

            // Add current month to the categories if it's '1M'
            if ($filter === '1M' && !in_array($now->format('Y-m-d'), $dates)) {
                $dates[] = $now->format('Y-m-d');  // Ensure the current day is included for '1M'
            }

            // Initialize data series with 0 for each date/month
            foreach ($products as $productName) {
                foreach ($dates as $date) {
                    $dataSeries[$productName][] = 0;
                }
            }

            // Populate the data series with totals for each product
            foreach ($orderItems as $item) {
                // For '1Y' or '6M', format as YYYY-MM, and for '1M', use the full date
                if ($filter === '1Y' || $filter === '6M') {
                    $index = array_search($item->year . '-' . str_pad($item->month, 2, '0', STR_PAD_LEFT), $dates);
                } else {
                    $index = array_search($item->date, $dates);
                }

                if ($index !== false && in_array($item->nama_produk, $products)) {
                    // Round the total value to 1 decimal place
                    $dataSeries[$item->nama_produk][$index] = round((float) $item->total, 1);
                }
            }

            // Prepare data for ApexCharts
            $chartData = [
                'categories' => $dates,
                'series' => []
            ];

            // Add formatted labels for each date
            $labels = [];
            foreach ($dates as $date) {
                if ($filter === '1Y' || $filter === '6M') {
                    // Use YYYY-MM format for 1Y or 6M
                    $labels[] = Carbon::createFromFormat('Y-m', $date)->format('Y-m');
                } else {
                    // Use YYYY-MM-DD format for 1M
                    $labels[] = Carbon::createFromFormat('Y-m-d', $date)->format('Y-m-d');
                }
            }

            foreach ($products as $productName) {
                $chartData['series'][] = [
                    'name' => $productName,
                    'data' => $dataSeries[$productName],
                ];
            }

            $chartData['labels'] = $labels;

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
