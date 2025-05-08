<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception; 

// O controlador utiliza um serviço chamado WalletService para realizar as 
// operações de lógica de negócios e manipulação dos dados. 

class WalletController extends Controller
{
    protected $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    public function dashboard()
    {
        $user = Auth::user();
        // Eager load transactions for display, ordered by newest first
        $transactions = $user->transactions()->with(['relatedUser', 'originalTransaction.user'])
                             ->orderBy('created_at', 'desc')->paginate(10);
        
        return view('wallet.dashboard', compact('user', 'transactions'));
    }

    // --- Deposit ---
    public function showDepositForm()
    {
        return view('wallet.deposit');
    }

    public function processDeposit(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        try {
            $this->walletService->deposit(Auth::user(), (float)$request->amount);
            return redirect()->route('dashboard')->with('success', 'Deposit successful!');
        } catch (Exception $e) {
            return back()->withInput()->with('error', 'Deposit failed: ' . $e->getMessage());
        }
    }

    // --- Transfer ---
    public function showTransferForm()
    {
        return view('wallet.transfer');
    }

    public function processTransfer(Request $request)
    {
        $request->validate([
            'recipient_email' => 'required|email|exists:users,email',
            'amount' => 'required|numeric|min:0.01',
        ]);

        $sender = Auth::user();
        $recipient = User::findByEmail($request->recipient_email);

        if (!$recipient) { // Should be caught by 'exists' validation, but good to double check
            return back()->withInput()->with('error', 'Recipient not found.');
        }
        if ($sender->id === $recipient->id) {
             return back()->withInput()->with('error', 'You cannot transfer funds to yourself.');
        }


        try {
            $this->walletService->transfer($sender, $recipient, (float)$request->amount);
            return redirect()->route('dashboard')->with('success', 'Transfer successful!');
        } catch (Exception $e) {
            return back()->withInput()->with('error', 'Transfer failed: ' . $e->getMessage());
        }
    }
}