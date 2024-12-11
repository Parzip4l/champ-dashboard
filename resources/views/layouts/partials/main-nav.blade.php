<div class="main-nav">
    <!-- Sidebar Logo -->
    <div class="logo-box">
        <a href="#" class="logo-dark">
            <img src="/images/champ-sm.png" class="champ-sm" alt="logo sm">
        </a>

        <a href="#" class="logo-light">
            <img src="/images/champ-sm.png" class="champ-sm" alt="logo sm">
        </a>
    </div>

    <!-- Menu Toggle Button (sm-hover) -->
    <button type="button" class="button-sm-hover" aria-label="Show Full Sidebar">
        <iconify-icon icon="solar:double-alt-arrow-right-bold-duotone" class="button-sm-hover-icon"></iconify-icon>
    </button>

    <div class="scrollbar" data-simplebar>
        @php
            $menus = \App\Models\General\Menu::whereNull('parent_id')->where('is_active', 1)->with('children')->orderBy('order')->get();
        @endphp
        <ul class="navbar-nav" id="navbar-nav">
            @foreach($menus as $menu)
                @if($menu->children->isEmpty())
                <li class="nav-item active">
                    <a class="nav-link" href="{{ $menu->url ? route($menu->url) : '#' }}">
                            <span class="nav-icon">
                                <iconify-icon icon="{{ $menu->icon }}"></iconify-icon>
                            </span>
                        <span class="nav-text">{{ $menu->title }} </span>
                    </a>
                </li>
                @endif
                @if($menu->children->isNotEmpty())
                <li class="nav-item">
                    <a class="nav-link menu-arrow" href="#sidebar{{ $menu->id }}" data-bs-toggle="collapse" role="button"
                    aria-expanded="false" aria-controls="sidebar{{ $menu->id }}">
                            <span class="nav-icon">
                            <iconify-icon icon="{{ $menu->icon }}" width="24" height="24"></iconify-icon>
                            </span>
                        <span class="nav-text"> {{ $menu->title }} </span>
                    </a>
                    <div class="collapse" id="sidebar{{ $menu->id }}">
                        <ul class="nav sub-navbar-nav">
                            @foreach($menu->children as $child)
                            <li class="sub-nav-item">
                                <a class="sub-nav-link" href="{{ route($child->url) }}">{{ $child->title }}</a>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </li>
                @endif
            @endforeach
        </ul>
    </div>
</div>
