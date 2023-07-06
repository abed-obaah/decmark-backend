<?php

namespace App\Http\Controllers\Api\One\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;

class ServiceRecieverController extends Controller
{
    public function getAll()
    {
        try {
            $data = User::where('artisan', 0)->where('rider',0)->get(['id', 'first_name', 'last_name', 'created_at', 'phone']);
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $data = User::where('artisan', 0)->where('rider', 0)->where('id', $id)->get();
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function delete($id)
    {
        try {
            $data = User::where('artisan', 0)->where('rider', 0)->where('id', $id)->first();
            $services = Service::where('user_id', $data->id)->delete();
            $data->delete();
            return response()->json(['message' => 'Service reciever deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function suspend($id)
    {
        try {
            $user = User::where('artisan', 0)->where('rider', 0)->where('id', $id)->first();
            if ($user == null) {
                return response()->json(['message' => 'Account not found']);
            }
            if ($user->user_suspended == 1) {
                return response()->json(['message' => 'Service reciever already suspended', 'data' => $user]);
            } else {
                $user->user_suspended = 1;
                $user->save();
                return response()->json(['message' => 'Service reciever suspended successfully', 'data' => $user]);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function unsuspend($id)
    {
        try {
            $user = User::where('artisan', 0)->where('id', $id)->first();
            if ($user == null) {
                return response()->json(['message' => 'Account not found']);
            }
            if ($user->user_suspended == 0) {
                return response()->json(['message' => 'Service reciever already unsuspended', 'data' => $user]);
            } else {
                $user->user_suspended = 0;
                $user->save();
                return response()->json(['message' => 'Service reciever unsuspended successfully', 'data' => $user]);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
