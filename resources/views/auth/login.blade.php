<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" /> {{-- O conteúdo de session('status') pode precisar de tradução dependendo de onde é definido --}}

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div>
            <x-label for="email" :value="__('E-mail')" />
            <x-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" /> {{-- As mensagens de erro (erros->get('email')) são geralmente traduzidas pelos arquivos de localização do Laravel --}}
        </div>

        <div class="mt-4">
            <x-label for="password" :value="__('Senha')" />
            <x-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" /> {{-- As mensagens de erro (erros->get('password')) são geralmente traduzidas pelos arquivos de localização do Laravel --}}
        </div>

        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:focus:ring-offset-gray-800" name="remember">
                <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">{{ __('Lembrar-me') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-between mt-4">  
            <div>
                @if (Route::has('register'))
                    <a class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800" href="{{ route('register') }}">
                        {{ __('Ainda não registrado?') }} 
                    </a>
                @endif
            </div>

            <div class="flex items-center">
                @if (Route::has('password.request'))
                    <a class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800" href="{{ route('password.request') }}">
                        {{ __('Esqueceu sua senha?') }}
                    </a>
                @endif

                <x-primary-button class="ms-3">
                    {{ __('Entrar') }}
                </x-primary-button>
            </div>
        </div>
    </form>
</x-guest-layout>