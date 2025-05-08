<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class TransactionController extends Controller
{
    protected $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    public function reverse(Request $request, Transaction $transaction)
    {
             // Autorização: Garantir que o usuário possa reverter esta transação
            // Essa lógica pode ser complexa:
           // - Somente o usuário envolvido?
          // - Somente tipos específicos de transações?
         // - Um administrador pode forçar a reversão?
        // Para simplificar, vamos considerar que apenas o usuário ao qual a transação pertence (user_id)
       // Ou um administrador (não implementado)

        $currentUser = Auth::user();
        $isUserInvolved = ($transaction->user_id === $currentUser->id || $transaction->related_user_id === $currentUser->id);

        if (!$isUserInvolved) {
             return redirect()->route('dashboard')->with('error', 'You are not authorized to reverse this transaction.');
        }
        if ($transaction->status === 'reversed' || $transaction->type === 'reversal') {
            return redirect()->route('dashboard')->with('error', 'This transaction cannot be reversed or is already reversed.');
        }


        try {
            $reason = $request->input('reason', 'User requested reversal');
            $this->walletService->reverseTransaction($transaction, $reason);
            return redirect()->route('dashboard')->with('success', 'Transaction reversed successfully!');
        } catch (Exception $e) {
            return redirect()->route('dashboard')->with('error', 'Reversal failed: ' . $e->getMessage());
        }
    }
}