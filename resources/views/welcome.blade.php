<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Financial Wallet') }} - {{ __('Sua Carteira Digital Segura') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        <!-- Styles / Scripts -->
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            <style> /* Fallback CSS content here... */ </style>
        @endif
    </head>
    <body class="font-sans bg-[#FDFDFC] dark:bg-gray-900 text-gray-800 dark:text-gray-200 flex flex-col min-h-screen antialiased">
        <header class="w-full p-6 lg:px-8">
            @if (Route::has('login'))
                <nav class="flex items-center justify-end gap-4 text-sm">
                    @auth
                        <a
                            href="{{ route('dashboard') }}"
                            class="inline-block px-5 py-1.5 dark:text-gray-200 border border-gray-300 dark:border-gray-700 hover:border-gray-500 dark:hover:border-gray-500 rounded-sm leading-normal transition"
                        >
                            {{ __('Painel') }}
                        </a>
                    @else
                        <a
                            href="{{ route('login') }}"
                            class="inline-block px-5 py-1.5 dark:text-gray-200 text-gray-800 hover:text-black dark:hover:text-white rounded-sm leading-normal transition"
                        >
                            {{ __('Entrar') }}
                        </a>

                        @if (Route::has('register'))
                            <a
                                href="{{ route('register') }}"
                                class="inline-block px-5 py-1.5 dark:text-gray-200 border border-gray-300 dark:border-gray-700 hover:border-gray-500 dark:hover:border-gray-500 rounded-sm leading-normal transition">
                                {{ __('Registrar') }}
                            </a>
                        @endif
                    @endauth
                </nav>
            @endif
        </header>

        <main class="flex flex-1 items-center justify-center w-full p-6">
            <div class="max-w-2xl w-full text-center">

                {{-- Logo Opcional da Aplicação --}}

                <h1 class="text-4xl lg:text-5xl font-semibold mb-4 text-gray-900 dark:text-white">
                    {{ __('Bem-vindo(a) à Financial Wallet') }}
                </h1>

                <p class="text-lg text-gray-600 dark:text-gray-400 mb-10">
                    {{ __('Sua solução simples e segura para gerenciar fundos, depositar instantaneamente e transferir dinheiro facilmente entre usuários.') }}
                </p>

                <div class="flex flex-col sm:flex-row justify-center items-center gap-4">
                    @guest
                        <a href="{{ route('register') }}" class="w-full sm:w-auto">
                            <x-primary-button class="w-full justify-center px-8 py-3 text-lg">
                                {{ __('Criar Conta') }}
                            </x-primary-button>
                        </a>
                        <a href="{{ route('login') }}" class="w-full sm:w-auto">
                             <x-secondary-button class="w-full justify-center px-8 py-3 text-lg">
                                {{ __('Entrar') }}
                            </x-secondary-button>
                        </a>
                    @endguest
                    @auth
                         <a href="{{ route('dashboard') }}" class="w-full sm:w-auto">
                            <x-primary-button class="w-full justify-center px-8 py-3 text-lg">
                                {{ __('Ir para sua Carteira') }}
                            </x-primary-button>
                        </a>
                    @endauth
                </div>

            </div>
        </main>

        <footer class="text-center text-sm text-gray-500 dark:text-gray-400 py-4 mt-auto">
           {{ config('app.name', 'Financial Wallet') }} © {{ date('Y') }}
        </footer>

    </body>
</html>