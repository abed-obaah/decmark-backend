<?php

namespace App\Http\Controllers\Api\One\User;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Classes\SenderHelper;
use App\Enums\AppScreensEnum;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\LogResource;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Pagination\Paginator;
use App\Http\Requests\User\UpdateFCMRequest;
use Illuminate\Database\Eloquent\Collection;
use App\Http\Requests\User\UpdateUserRequest;
use Illuminate\Pagination\LengthAwarePaginator;

class AccountController extends Controller
{
    /**
     * Account index
     */
    public function index(Request $request)
    {
        /**
         * @var \App\Models\User
         */
        $user = $request->user();

        // $user->wallet = $user->wallets()->first();

        return new UserResource($request->user());
    }

    public function checkVerification(Request $request)
    {
        /**
         * @var \App\Models\User
         */
        $user = $request->user();

        $isVerified = [
            'verified' => boolval($user->artisan_verified)
        ];

        return response()->json($isVerified);
    }

    /**
     * Account Notifications
     */
    public function notifications(Request $request)
    {
        return $request->user()->notifications()->paginate(30);
    }

    /**
     * Account Logs
     */
    public function logs(Request $request)
    {

        return $this->paginate($request->user()->logs()->first()->getData(true))->setPath($request->url());
        //return new LogResource($request->user()->logs()->first());
    }

    /**
     * Paginate Array as Collection
     *
     * @var array
     */
    public function paginate($items, $perPage = 30, $page = null, $options = [])
    {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $items = $items instanceof Collection ? $items : Collection::make($items);
        return new LengthAwarePaginator($items->forPage($page, $perPage)->values(), $items->count(), $perPage, $page, $options);
    }

    /**
     * Account fcm_token update
     */
    public function addFcm(UpdateFCMRequest $request)
    {
        try {
            $tokens = json_decode($request->user()->fcm_tokens) ?? [];

            if (!in_array($request->token, $tokens)) {
                array_push($tokens, $request->token);
                $request->user()->update(['fcm_tokens' => json_encode(array_values($tokens))]);
            }

            return response()->json([
                'success' => true
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function removeFcm(UpdateFCMRequest $request)
    {
        try {
            $tokens = json_decode($request->user()->fcm_tokens) ?? [];

            if (in_array($request->token, $tokens)) {
                $tokens = array_diff($tokens, [$request->token]);
                $request->user()->update(['fcm_tokens' => json_encode(array_values($tokens))]);
            }

            return response()->json([
                'success' => true
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update user's profile.
     */
    public function update(UpdateUserRequest $request): JsonResponse
    {
        // dd($request);
        if (!is_null($request->image)) {
            $image = $request->image->getClientOriginalExtension();
            $image_name = 'img_' . Str::uuid() . '_' . date("Ym_dh_is", time()) . '.' . $image;
            $request->image->move(public_path('uploads/profile_images'), $image_name);
            $request->profile_img = env('APP_URL', 'https://decmark.com') . '/uploads/profile_images/' . $image_name;
        }
        //Update Data
        $request
            ->user()
            ->update($request->validated());

        //Update Image
        if (!is_null($request->image)) $request
            ->user()
            ->update([
                'profile_img' => $request->profile_img
            ]);

        SenderHelper::userLog($request->user(), 'You updated your profile.', AppScreensEnum::ACCOUNT);

        return response()->json([
            'message' => 'Profile updated.'
        ], JsonResponse::HTTP_OK);
    }
}
