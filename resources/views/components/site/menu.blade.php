@props([
    'items',
    'currentPath' => null,
    'isSubmenu' => false,
])

@if ($items->isNotEmpty())
    <ul class="{{ $isSubmenu ? 'nav-submenu' : 'nav-menu' }}">
        @foreach ($items as $item)
            @php
                $hasChildren = $item->children->isNotEmpty();
                $isActive = $item->path === $currentPath;
                $isActiveParent = ! $isActive && $currentPath !== null && $item->isActiveFor($currentPath);
            @endphp
            <li @class(['has-children' => $hasChildren, 'active' => $isActive, 'active-parent' => $isActiveParent])>
                <a href="{{ $item->url() }}" @class(['nav-dropdown-toggle' => $hasChildren]) @if ($isActive) aria-current="page" @endif>
                    {{ $item->title }}
                    @if ($hasChildren)
                        <span class="dropdown-arrow"></span>
                    @endif
                </a>
                @if ($hasChildren)
                    <x-site.menu :items="$item->children" :current-path="$currentPath" :is-submenu="true" />
                @endif
            </li>
        @endforeach
    </ul>
@endif
