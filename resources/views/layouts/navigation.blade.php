<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                

                
                <!-- Navigation Links -->
            <div class="hidden sm:-my-px ms-auto sm:flex items-center space-x-4">
            {{-- Dashboard --}}
            <x-nav-link href="{{ route('dashboard.redirect') }}" :active="request()->routeIs('dashboard*')">
                {{ __('Dashboard') }}
            </x-nav-link>

            @role('admin')
                {{-- Links que ficam fora do dropdown --}}
                <x-nav-link href="{{ route('admin.produtos.index') }}" :active="request()->routeIs('admin.produtos.*')">
                    {{ __('Estoque') }}
                </x-nav-link>

                <x-nav-link href="{{ route('admin.pedidos.index') }}" :active="request()->routeIs('admin.pedidos.*')">
                    {{ __('Pedidos') }}
                </x-nav-link>

              <x-nav-link href="{{ route('admin.relatorios.index') }}" :active="request()->routeIs('admin.pedidos.*')">
                    {{ __('Relatorios') }}
                </x-nav-link>
            

                {{-- Dropdown para os demais --}}
                <x-dropdown align="left" width="56">
                    <x-slot name="trigger">
                        <button
                            class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-600 bg-white hover:text-gray-800 focus:outline-none transition">
                            <span>{{ __('Usuários') }}</span>
                            <svg class="ms-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link href="{{ route('admin.clientes.index') }}"
                            class="{{ request()->routeIs('admin.clientes.*') ? 'bg-gray-100' : '' }}">
                            {{ __('Clientes') }}
                        </x-dropdown-link>

                        <x-dropdown-link href="{{ route('admin.gestores.index') }}"
                            class="{{ request()->routeIs('admin.gestores.*') ? 'bg-gray-100' : '' }}">
                            {{ __('Gestores') }}
                        </x-dropdown-link>

                        <x-dropdown-link href="{{ route('admin.distribuidores.index') }}"
                            class="{{ request()->routeIs('admin.distribuidores.*') ? 'bg-gray-100' : '' }}">
                            {{ __('Distribuidores') }}
                        </x-dropdown-link>

                        <x-dropdown-link href="{{ route('admin.advogados.index') }}"
                            class="{{ request()->routeIs('admin.advogados.*') ? 'bg-gray-100' : '' }}">
                            {{ __('Advogados') }}
                        </x-dropdown-link>

                        <x-dropdown-link href="{{ route('admin.diretor-comercials.index') }}"
                            class="{{ request()->routeIs('admin.diretor-comercials.*') ? 'bg-gray-100' : '' }}">
                            {{ __('Diretor') }}
                        </x-dropdown-link>

                        <x-dropdown-link href="{{ route('admin.usuarios.index') }}"
                            class="{{ request()->routeIs('admin.usuarios.*') ? 'bg-gray-100' : '' }}">
                            {{ __('Usuários') }}
                        </x-dropdown-link>
                    </x-slot>
                </x-dropdown>
            @endrole
        </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard.redirect')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
