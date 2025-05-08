<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Transferir Fundos') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                     @if(session('error'))
                        <div class="mb-4 p-4 bg-red-100 text-red-700 rounded">{{ session('error') }}</div>
                    @endif
                    @if ($errors->any())
                        <div class="mb-4 p-4 bg-red-100 text-red-700 rounded">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('wallet.transfer.process') }}">
                        @csrf

                        <div>
                            <x-label for="recipient_email" :value="__('E-mail do Destinatário')" />
                            <x-input id="recipient_email" class="block mt-1 w-full" type="email" name="recipient_email" :value="old('recipient_email')" required />
                        </div>

                        <div class="mt-4">
                            <x-label for="amount" :value="__('Valor (R$)')" />
                            <x-input id="amount" class="block mt-1 w-full" type="number" name="amount" step="0.01" :value="old('amount')" required />
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <x-primary-button>
                                {{ __('Transferir') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>