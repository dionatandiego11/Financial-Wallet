<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Painel da Carteira') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">

                    @if(session('success'))
                        <div class="mb-4 p-4 bg-green-100 text-green-700 rounded">{{ session('success') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="mb-4 p-4 bg-red-100 text-red-700 rounded">{{ session('error') }}</div>
                    @endif

                    <h3 class="text-lg font-semibold">Bem-vindo, {{ $user->name }}!</h3>
                    <p class="mb-4">Seu saldo atual: <strong class="text-2xl">R$ {{ number_format($user->balance, 2, ',', '.') }}</strong></p>

                    <div class="mb-6">
                        <a href="{{ route('wallet.deposit.form') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Depositar Fundos
                        </a>
                        <a href="{{ route('wallet.transfer.form') }}" class="ml-2 bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                            Transferir Fundos
                        </a>
                    </div>

                    <h4 class="text-md font-semibold mb-2">Histórico de Transações</h4>
                    @if($transactions->isEmpty())
                        <p>Nenhuma transação ainda.</p>
                    @else
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valor</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Detalhes</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($transactions as $transaction)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ ucfirst(str_replace('_', ' ', $transaction->type)) }}</td> {{-- O tipo da transação (e.g., 'deposit', 'transfer') provavelmente precisa de tradução mais específica dependendo do valor --}}
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium
                                        @if(in_array($transaction->type, ['deposit', 'received_transfer'])) text-green-600 @else text-red-600 @endif">
                                        R$ {{ number_format($transaction->amount, 2, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $transaction->description }} {{-- A descrição pode conter texto que precise de tradução --}}
                                        @if($transaction->type === 'reversal' && $transaction->originalTransaction)
                                            <br><small>(Tx Original Revertida ID: {{ $transaction->original_transaction_id }} para {{ $transaction->originalTransaction->user->name }})</small>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                            @if($transaction->status == 'completed') bg-green-100 text-green-800 {{-- completo --}}
                                            @elseif($transaction->status == 'reversed') bg-yellow-100 text-yellow-800 {{-- revertido --}}
                                            @else bg-gray-100 text-gray-800 @endif"> {{-- outros status --}}
                                            {{ ucfirst($transaction->status) }} {{-- O status da transação (e.g., 'completed', 'reversed') provavelmente precisa de tradução mais específica dependendo do valor --}}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        @if($transaction->status === 'completed' && !in_array($transaction->type, ['reversal']))
                                            <form action="{{ route('transactions.reverse', $transaction) }}" method="POST" onsubmit="return confirm('Tem certeza que deseja reverter esta transação?');">
                                                @csrf
                                                <button type="submit" class="text-red-600 hover:text-red-900">Reverter</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="mt-4">
                            {{ $transactions->links() }} {{-- A paginação (links) pode precisar de tradução dependendo da configuração --}}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>