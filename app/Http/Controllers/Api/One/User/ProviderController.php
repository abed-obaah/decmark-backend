<?php

namespace App\Http\Controllers\Api\One\User;

use App\Models\User;
use App\Models\Service;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\Service\SearchRequest;
use App\Http\Resources\ProviderResource;

class ProviderController extends Controller
{

    /**
     * Display listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $data = User::where('artisan', 1)->with('services')->paginate(15, ['id', 'first_name', 'last_name', 'profile_img', 'created_at', 'artisan_bio', 'artisan_place'], 'page');
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Display specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            // $data = User::where('artisan', 1)->where('id', $id)->with('services')->get(['id', 'first_name', 'last_name', 'profile_img', 'artisan_bio', 'artisan_place', 'artisan_type', 'artisan_status', 'created_at']);
            $data = User::where('artisan', 1)->where('id', $id)->with('services.ratings')->with('attachments')->get();
            return response()->json(ProviderResource::collection($data));
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function most_rated()
    {
        // try {
        //     // $data = User::where('artisan', 1)->with('services')->paginate(15, ['id', 'first_name', 'last_name', 'profile_img', 'created_at', 'artisan_bio', 'artisan_place'], 'page');
        //     $data = User::where('artisan', 1)->with('services')->paginate(15);
        //     return response()->json($data);
        // } catch (\Exception $e) {
        //     return response()->json(['message' => $e->getMessage()], 500);
        // }

        try {
            $services = Service::with('ratings')->paginate();
            foreach ($services as $service) {
                $provider = [];


                if (!$service->user()->get()[0]->business) {
                    $provider['name'] = $service->user()->get()[0]->getNameAttribute();
                    $provider['image'] = $service->user()->get()[0]->profile_img;
                    $provider['address'] = $service->user()->get()[0]->address;
                    $providerType = "Business";
                } else {
                    $provider['name'] = $service->user()->get()[0]->rep_name . ' @ ' . $service->user()->get()[0]->business_name;
                    $provider['image'] = $service->user()->get()[0]->profile_img;
                    $provider['address'] = $service->user()->get()[0]->address;
                    $providerType = "Individual";
                }

                $service['provider'] = $provider;
                $service['providerType'] = $providerType;
            }
            return response()->json($services);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Query the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function search(SearchRequest $request)
    {
        try {
            $providers = User::where("first_name", "like", "%" . $request->search . "%")
                ->orWhere("last_name", "like", "%" . $request->search . "%")
                ->orWhere("artisan_bio", "like", "%" . $request->search . "%")
                ->orWhere("artisan_place", "like", "%" . $request->search . "%")
                ->paginate(15, ['id', 'first_name', 'last_name', 'profile_img', 'artisan_bio', 'artisan_place'], 'search_page');
            return response()->json($providers);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
