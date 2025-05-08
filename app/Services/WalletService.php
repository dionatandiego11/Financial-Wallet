<?php

namespace App\Services;

use App\Models\User;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; 
use Exception;

class WalletService
{
    /**
     * Depositar fundos na conta de um usuário.
     *
     * @param User $user O usuário para depositar.
     * @param float $amount O valor a depositar.
     * @param string $description A descrição do depósito.
     * @return Transaction A transação de depósito criada.
     * @throws Exception Se o valor do depósito for zero ou negativo.
     */
    public function deposit(User $user, float $amount, string $description = 'Depósito'): Transaction
    {
        if ($amount <= 0) {
            throw new Exception("O valor do depósito deve ser positivo.");
        }

        return DB::transaction(function () use ($user, $amount, $description) {
            // Mesmo que o saldo seja negativo, o depósito é adicionado a ele.
            $user->balance += $amount;
            $user->save();

            return Transaction::create([
                'user_id' => $user->id,
                'type' => 'deposit', // Tipo de transação: depósito 
                'amount' => $amount,
                'description' => $description,
                'status' => 'completed', // Status: concluída
            ]);
        });
    }

    /**
     * Transferir fundos de um usuário para outro.
     *
     * @param User $sender O usuário remetente.
     * @param User $recipient O usuário destinatário.
     * @param float $amount O valor a transferir.
     * @param string $description A descrição da transferência.
     * @return array Um array contendo as transações do remetente e do destinatário.
     * @throws Exception Se o valor for zero ou negativo, se remetente e destinatário forem o mesmo, ou se o saldo for insuficiente.
     */
    public function transfer(User $sender, User $recipient, float $amount, string $description = 'Transferência'): array
    {
        if ($amount <= 0) {
            throw new Exception("O valor da transferência deve ser positivo.");
        }
        if ($sender->id === $recipient->id) {
            throw new Exception("Não é possível transferir para você mesmo.");
        }
        if ($sender->balance < $amount) {
            throw new Exception("Saldo insuficiente para transferência.");
        }

        return DB::transaction(function () use ($sender, $recipient, $amount, $description) {
            $sender->balance -= $amount;
            $sender->save();

            $recipient->balance += $amount;
            $recipient->save();

            $senderTransaction = Transaction::create([
                'user_id' => $sender->id,
                'type' => 'transfer', // Tipo de transação: transferência enviada 
                'amount' => $amount,
                'related_user_id' => $recipient->id, // ID do usuário relacionado (destinatário)
                'description' => $description . " para " . $recipient->name, 
                'status' => 'completed', // Status: concluída 
            ]);

            $recipientTransaction = Transaction::create([
                'user_id' => $recipient->id,
                'type' => 'received_transfer', // Tipo de transação: transferência recebida 
                'amount' => $amount,
                'related_user_id' => $sender->id, // ID do usuário relacionado (remetente)
                'description' => $description . " de " . $sender->name, 
                'status' => 'completed', // Status: concluída 
            ]);

            return ['sender_transaction' => $senderTransaction, 'recipient_transaction' => $recipientTransaction];
        });
    }

    /**
     * Reverter uma transação.
     * Cria uma nova transação de 'reversal' e marca a transação original como 'reversed'.
     *
     * @param Transaction $transactionToReverse A transação a ser estornada.
     * @param string $reason O motivo do estorno.
     * @return Transaction A nova transação de estorno criada.
     * @throws Exception Se a transação já foi estornada, já possui um estorno, ou se o tipo de transação é desconhecido/inválido para estorno.
     */
    public function reverseTransaction(Transaction $transactionToReverse, string $reason = 'Solicitação de estorno pelo usuário'): Transaction
    {
        if ($transactionToReverse->status === 'reversed') {
            throw new Exception("Transação já foi estornada.");
        }
        if ($transactionToReverse->reversalTransaction()->exists()) {
            throw new Exception("A transação já possui um registro de estorno.");
        }

        return DB::transaction(function () use ($transactionToReverse, $reason) {
            $user = $transactionToReverse->user;
            $amountToReverse = $transactionToReverse->amount;
            $newReversalTransaction = null;

            switch ($transactionToReverse->type) {
                case 'deposit': // Estornando um depósito
                    // O usuário perde o valor depositado.
                    // Importante: Implementar se o usuário tem saldo suficiente para cobrir o estorno.
                    // Essa regra de negócio precisa de classificação: o estorno pode tornar o saldo mais negativo?
                    // Por enquanto, vamos assumir que sim, como implícito em "no depósito deve acrescentar ao valor".
                    $user->balance -= $amountToReverse;
                    $user->save();

                    $newReversalTransaction = Transaction::create([
                        'user_id' => $user->id,
                        'type' => 'reversal', // Tipo de transação: estorno 
                        'amount' => $amountToReverse, // Armazenado como positivo, o tipo indica a direção (saída para o user)
                        'original_transaction_id' => $transactionToReverse->id, // ID da transação original
                        'description' => "Estorno de depósito: " . ($transactionToReverse->description ?: 'N/A') . ". Motivo: " . $reason, // Descrição humanamente legível
                        'status' => 'completed', // Status: concluída 
                    ]);
                    break;

                case 'transfer': // Estornando uma transferência ENVIADA por $transactionToReverse->user
                    $sender = $user; // O remetente original
                    $recipient = $transactionToReverse->relatedUser; // O destinatário original

                    if (!$recipient) {
                        throw new Exception("Não é possível estornar transferência: destinatário não encontrado ou deletado.");
                    }

                    // O remetente recebe o dinheiro de volta
                    $sender->balance += $amountToReverse;
                    $sender->save();

                    // O destinatário perde o dinheiro
                    $recipient->balance -= $amountToReverse;
                    $recipient->save();

                    $newReversalTransaction = Transaction::create([
                        'user_id' => $sender->id, // O estorno é logado para o remetente (quem recebeu o dinheiro de volta)
                        'type' => 'reversal', // Tipo de transação: estorno 
                        'amount' => $amountToReverse,
                        'related_user_id' => $recipient->id, // Quem estava envolvido na transação original
                        'original_transaction_id' => $transactionToReverse->id, // ID da transação original
                        'description' => "Estorno da transferência para {$recipient->name}. Motivo: {$reason}", 
                        'status' => 'completed', // Status: concluída 
                    ]);

                    // Opcionalmente, registra um estorno correspondente para o destinatário, se necessário para o extrato dele.
                    // Para simplificar, este único estorno afeta ambos os saldos.
                    // Também podemos encontrar a transação 'received_transfer' do destinatário
                    // e marcá-la como 'reversed' ou vinculá-la a este novo estorno.
                    // Procurar a transação do destinatário com base nos detalhes originais
                    $recipientOriginalTransaction = Transaction::where('user_id', $recipient->id)
                                                        ->where('type', 'received_transfer') // Tipo de transação: transferência recebida 
                                                        ->where('related_user_id', $sender->id) // ID do remetente original
                                                        ->where('amount', $amountToReverse)
                                                        // Adicione proximidade de timestamp se IDs não estiverem vinculados
                                                        ->where('created_at', $transactionToReverse->created_at)
                                                        ->first();
                    if($recipientOriginalTransaction) {
                        $recipientOriginalTransaction->status = 'reversed'; // Marcar como estornada 
                        $recipientOriginalTransaction->save();
                    }

                    break;

                case 'received_transfer': // Estornando uma transferência RECEBIDA por $transactionToReverse->user
                    $recipient = $user; // O destinatário original (quem recebeu o dinheiro)
                    $sender = $transactionToReverse->relatedUser; // O remetente original

                    if (!$sender) {
                        throw new Exception("Não é possível estornar transferência: remetente não encontrado ou deletado.");
                    }

                    // O destinatário perde o dinheiro
                    $recipient->balance -= $amountToReverse;
                    $recipient->save();

                    // O remetente recebe o dinheiro de volta
                    $sender->balance += $amountToReverse;
                    $sender->save();

                    $newReversalTransaction = Transaction::create([
                        'user_id' => $recipient->id, // O estorno é logado para o destinatário (quem perdeu o dinheiro agora)
                        'type' => 'reversal', 
                        'amount' => $amountToReverse,
                        'related_user_id' => $sender->id, // Quem estava envolvido na transação original
                        'original_transaction_id' => $transactionToReverse->id, // ID da transação original
                        'description' => "Estorno da transferência de {$sender->name}. Motivo: {$reason}", 
                        'status' => 'completed', 
                    ]);

                    // Marque a transação 'transfer' original do remetente como estornada.
                    // Procurar a transação do remetente com base nos detalhes originais
                    $senderOriginalTransaction = Transaction::where('user_id', $sender->id)
                                                        ->where('type', 'transfer') 
                                                        ->where('related_user_id', $recipient->id) // ID do destinatário original
                                                        ->where('amount', $amountToReverse)
                                                        ->where('created_at', $transactionToReverse->created_at)
                                                        ->first();
                    if($senderOriginalTransaction) {
                        $senderOriginalTransaction->status = 'reversed'; 
                        $senderOriginalTransaction->save();
                    }
                    break;

                case 'reversal': // Não permitir estornar um estorno
                    throw new Exception("Não é possível estornar uma transação de estorno.");

                default: // Tipo de transação desconhecido
                    throw new Exception("Tipo de transação desconhecido para estorno: " . $transactionToReverse->type);
            }

            // Marque a transação original como estornada
            $transactionToReverse->status = 'reversed'; 
            $transactionToReverse->save();

            return $newReversalTransaction;
        });
    }
}