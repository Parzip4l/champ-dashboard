<?php
namespace App\Http\Controllers;

use App\Models\General\Menu;
use Illuminate\Support\Facades\Auth;

class SidebarController extends Controller
{
    public function index()
    {
        $roles = Auth::user()->role_id; // Get the role ID of the logged-in user
        $menus = Menu::where('is_active', 1)
            ->whereNull('parent_id')
            ->with('children')
            ->orderBy('order')
            ->get();

        // Filter the menus based on the user's role
        $filteredMenus = $menus->filter(function ($menu) use ($roles) {
            $roleIds = is_string($menu->role_id) ? json_decode($menu->role_id, true) : $menu->role_id;

            // If role_id is not an array, skip the menu
            if (!is_array($roleIds)) {
                return false;
            }

            // Ensure roles are an array for comparison
            if (is_string($roles)) {
                $roles = json_decode($roles, true); // Decode if it's a string
            }

            return in_array($roles, $roleIds); // Check if the user's role is in the menu's allowed roles
        });

        // Return the filtered menus to the Blade view
        return view('layouts.partials.main-nav', compact('filteredMenus'));
    }
}
