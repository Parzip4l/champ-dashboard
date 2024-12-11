<?php

namespace App\Http\Controllers;

use App\Models\General\Menu;

class SidebarController extends Controller
{
    public function getMenus()
    {
        // Ambil data menu dari database
        $menus = Menu::whereNull('parent_id')->with('children')->orderBy('order')->get();

        // Return data menu ke view sidebar
        return view('layouts.partials.main-nav', compact('menus'));
    }
}
