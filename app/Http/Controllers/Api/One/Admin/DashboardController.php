<?php

namespace App\Http\Controllers\Api\One\Admin;

use stdClass;
use App\Models\User;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DashboardRequest;

class DashboardController extends Controller
{
    public function getData(DashboardRequest $request)
    {
        try {
            $newUsers = User::latest()->take(3)->get();
            $activeUsers = User::where('user_suspended',0)->count();
            $yearlyPayments = [
                "jan" => Transaction::whereMonth('created_at', "01")->whereYear('created_at',$request->year)->count(),
                "feb" => Transaction::whereMonth('created_at', "02")->whereYear('created_at',$request->year)->count(),
                "mar" => Transaction::whereMonth('created_at', "03")->whereYear('created_at',$request->year)->count(),
                "apr" => Transaction::whereMonth('created_at', "04")->whereYear('created_at',$request->year)->count(),
                "may" => Transaction::whereMonth('created_at', "05")->whereYear('created_at',$request->year)->count(),
                "jun" => Transaction::whereMonth('created_at', "06")->whereYear('created_at',$request->year)->count(),
                "jul" => Transaction::whereMonth('created_at', "07")->whereYear('created_at',$request->year)->count(),
                "aug" => Transaction::whereMonth('created_at', "08")->whereYear('created_at',$request->year)->count(),
                "sep" => Transaction::whereMonth('created_at', "09")->whereYear('created_at',$request->year)->count(),
                "oct" => Transaction::whereMonth('created_at', "10")->whereYear('created_at',$request->year)->count(),
                "nov" => Transaction::whereMonth('created_at', "11")->whereYear('created_at',$request->year)->count(),
                "dec" => Transaction::whereMonth('created_at', "12")->whereYear('created_at',$request->year)->count(),
            ];
            $monthPaymentOverview = [
                'q1' => Transaction::whereMonth('created_at', $request->month)->whereYear('created_at',$request->year)->whereDay('created_at', '<=', '05')->sum('amount'),
                'q2' => Transaction::whereMonth('created_at', $request->month)->whereYear('created_at',$request->year)->whereDay('created_at', '>', '05')->whereDay('created_at', '<=', '10')->sum('amount'),
                'q3' => Transaction::whereMonth('created_at', $request->month)->whereYear('created_at',$request->year)->whereDay('created_at', '>', '10')->whereDay('created_at', '<=', '15')->sum('amount'),
                'q4' => Transaction::whereMonth('created_at', $request->month)->whereYear('created_at',$request->year)->whereDay('created_at', '>', '15')->whereDay('created_at', '<=', '20')->sum('amount'),
                'q5' => Transaction::whereMonth('created_at', $request->month)->whereYear('created_at',$request->year)->whereDay('created_at', '>', '20')->whereDay('created_at', '<=', '25')->sum('amount'),
                'q6' => Transaction::whereMonth('created_at', $request->month)->whereYear('created_at',$request->year)->whereDay('created_at', '>', '25')->whereDay('created_at', '<=', '31')->sum('amount'),
            ];
            $monthPayments = Transaction::whereMonth('created_at', $request->month)->whereYear('created_at',$request->year)->count();
            $totalPayments = Transaction::count();
            $data = new stdClass();
            $data->newUsers = $newUsers;
            $data->activeUsers = $activeUsers;
            $data->yearlyPayments = $yearlyPayments;
            $data->monthPaymentOverview = $monthPaymentOverview;
            $data->totalPayments = $totalPayments;
            $data->monthPayments = $monthPayments;

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function viewUser($id)
    {
        try {
            $user = User::findOrFail($id);

            return response()->json($user);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
