<?php

namespace App\Http\Controllers\Api\One\Admin;

use stdClass;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PaymentsController extends Controller
{
    public function index(Request $request){
        try {
            $data = [];
            $transactions = Transaction::paginate();
            foreach ($transactions as $transaction) {
                $user = Wallet::where('id', $transaction->wallet_id)->get('walletable_id')[0]->walletable_id;
                $transaction->user_details = [
                    'id' =>  $user,
                    'first_name' => User::where('id', $user)->get()[0]->first_name,
                    'last_name' => User::where('id', $user)->get()[0]->last_name,
                    'profile_img' => User::where('id', $user)->get()[0]->profile_img,
                ];
                array_push($data, $transaction);
            }
            return response()->json($transactions);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
