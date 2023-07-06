<?php

namespace App\Http\Controllers\Api\One\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;

class ServiceProviderController extends Controller
{
    public function getProviders()
    {
        try {
            $data = User::where('artisan', 1)->with('services')->get(['id', 'first_name', 'last_name', 'created_at', 'artisan_bio']);
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function viewProvider($id)
    {
        try {
            $data = User::where('artisan', 1)->where('id', $id)->with('services')->get();
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function suspendProvider($id)
    {
        try {
            $user = User::where('artisan', 1)->where('id', $id)->first();
            if ($user == null) {
                return response()->json(['message' => 'Account not found']);
            }
            if ($user->artisan_suspended == 1) {
                return response()->json(['message' => 'Service provider already suspended', 'data' => $user]);
            } else {
                $user->artisan_suspended = 1;
                $user->save();
                return response()->json(['message' => 'Service provider suspended successfully', 'data' => $user]);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function unsuspendProvider($id)
    {
        try {
            $user = User::where('artisan', 1)->where('id', $id)->first();
            if ($user == null) {
                return response()->json(['message' => 'Account not found']);
            }
            if ($user->artisan_suspended == 0) {
                return response()->json(['message' => 'Service provider already unsuspended', 'data' => $user]);
            } else {
                $user->artisan_suspended = 0;
                $user->save();
                return response()->json(['message' => 'Service provider unsuspended successfully', 'data' => $user]);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function deleteProvider($id)
    {
        try {
            $data = User::where('artisan', 1)->where('id', $id)->with('services')->first();
            $services = Service::where('user_id', $data->id)->delete();
            $data->delete();
            return response()->json(['message' => 'Service provider deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
