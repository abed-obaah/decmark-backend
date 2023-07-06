<?php

namespace App\Http\Controllers\Api\One\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\RideHailing\CreateTripRequest;
use App\Http\Requests\User\RideHailing\RateRiderRequest;
use App\Http\Requests\User\RideHailing\UpdateRiderRequest;
use App\Http\Resources\RideCollection;
use App\Http\Resources\RideResource;
use App\Models\RideHailing;
use App\Models\User;
use Grimzy\LaravelMysqlSpatial\Types\Point;
use Illuminate\Http\Request;

class RideController extends Controller
{
    public function store(CreateTripRequest $request)
    {
        $ride = $request->user()->userRides()->create([
            'current_coordinate' => new Point($request->current_location_coordinate_lat, $request->current_location_coordinate_long),
            'destination_coordinate' => new Point($request->destination_coordinate_lat, $request->destination_coordinate_long),
            'ride_type' => $request->ride_type,
            'passengers_count' => $request->passengers_count,
            'seats_count' => $request->passengers_count,
            'scheduled_at' => $request->scheduled_at,
        ]);

        return new RideResource($ride);
    }

    public function updateRiderLocation(UpdateRiderRequest $request)
    {
        try {
            $isUpdated = $request->user()->update([
                'rider_coordinate' => new Point($request->current_coordinate_lat, $request->current_coordinate_long)
            ]);
            return response()->json(['updated' => $isUpdated, 'data' => $request->user(), 'status' => 'success'], 200);
        } catch (\Exception $e) {
            return response()->json(['updated' => false, 'message' => $e->getMessage(), 'status' => 'error'], 500);
        }
    }

    public function nearbyRiders(Request $request, $meter = 1000)
    {
        // dd(auth()->user()->id);
        $nearbyRider = User::riders()->ridersCloseTo(
            $request->current_coordinate_long,
            $request->current_coordinate_lat,
            (int)$meter,
        )->select('id', 'first_name', 'last_name', 'gender', 'phone', 'rider_vehicle', 'rider_bio', 'rider_status', 'rider_image', 'rider_charge_per_km', 'rider_place', 'rider_coordinate')->first();

        $matchRideHail = RideHailing::where('user_id', auth()->user()->id)->first();
        if ($matchRideHail == null) {
            return response()->json(['status' => 'error', 'message' => 'Rider matching error'], 404);
        }

        $matchRideHail->rider_id = $nearbyRider->id;
        $matchRideHail->save();
        $matchRideHail->refresh();

        return response()->json(['status' => 'success', 'nearbyRider' => $nearbyRider, 'matched_rider' => $matchRideHail], 200);
    }

    public function getRideRequests(Request $request)
    {
        if (!User::isRider()) {
            return response()->json(['status' => 'error', 'message' => 'Verify that user is a rider'], 400);
        }

        $rideRequests = RideHailing::where('rider_id', auth()->user()->id)->where('rider_request_status', 'pending')->get();
        return response()->json(['status' => 'success', 'ride_requests' => $rideRequests], 200);
    }

    public function rideRequestAction(Request $request, $action)
    {
        try {
            $rideHailing = RideHailing::where('rider_id', auth()->user()->id)->first();

            if ($rideHailing->rider_request_status != 'pending') {
                return response()->json(['status' => 'error', 'message' => 'This trip is no longer available'], 400);
            }

            if ($action == 'accept') {
                $rideHailing->rider_request_status = 'accept';
                $rideHailing->payment_method = $request->payment_method;
                $rideHailing->payment_method = $request->payment_method;
            }

            if ($action == 'reject') {
                $rideHailing->rider_request_status = 'reject';
                $rideHailing->payment_method = $request->payment_method;
            }

            if ($action == 'cancel_trip') {
                $rideHailing->cancel_ride = 1;
                $rideHailing->rider_request_status = 'cancel_trip';
                $rideHailing->reason_for_cancel = $request->reason_for_cancel;
            }

            if ($action == 'end_trip') {
                $rideHailing->rider_request_status = 'end_trip';
                $rideHailing->end_trip = 1;
                $rideHailing->ended_by = $request->ended_by;
            }

            $rideHailing->save();
            $rideHailing->refresh();

            return response()->json(['status' => 'success', 'data' => $rideHailing], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function rateRiderWithReview(RateRiderRequest $request, RideHailing $ride)
    {
        try {
            $ride->update([
                'rider_rating' => $request->rating,
                'rider_review' => $request->review
            ]);

            return response()->json(['status' => 'success', 'data' => $ride], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function getRiderRatings(RideHailing $ride)
    {
        $rating = $ride->pluck('rider_rating')->avg();
        $user = User::where('id',$ride->rider_id)->select('id', 'first_name', 'last_name', 'gender', 'phone', 'rider_vehicle', 'rider_bio', 'rider_status', 'rider_image')->first();
        return response()->json(['status' => 'success', 'rider_rating' => (int)round($rating), 'rider' => $user],200);
    }
}
