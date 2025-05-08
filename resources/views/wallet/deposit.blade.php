<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Depositar Fundos') }} 
        </h2>
    </x-slot>
<div class="py-12">
    <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                {{-- Mensagens de sessÃ£o (erro) - O conteÃºdo deve ser traduzido no backend ou em arquivos de localizaÃ§Ã£o --}}
                @if(session('error'))
                    <div class="mb-4 p-4 bg-red-100 text-red-700 rounded">{{ session('error') }}</div>
                @endif
                {{-- Erros de validaÃ§Ã£o - Laravel geralmente traduz as mensagens padrÃ£o se os arquivos de localizaÃ§Ã£o existirem --}}
                @if ($errors->any())
                    <div class="mb-4 p-4 bg-red-100 text-red-700 rounded">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li> {{-- Laravel tenta traduzir as mensagens de validaÃ§Ã£o automaticamente --}}
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('wallet.deposit.process') }}">
                    @csrf

                    <div>
                        {{-- Classes ajustadas para garantir texto preto e corrigir typo no tamanho --}}
                        <x-label for="amount" class="block font-medium text-sm text-black" :value="__('Valor (R$)')" />
                        <x-input id="amount" class="block mt-1 w-full" type="number" name="amount" step="0.01" :value="old('amount')" required autofocus />
                    </div>

                    <div class="flex items-center justify-end mt-4">
                        <x-primary-button>
                            {{ __('Depositar') }} 
                        </x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</x-app-layout>