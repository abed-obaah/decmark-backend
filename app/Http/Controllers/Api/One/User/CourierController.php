<?php

namespace App\Http\Controllers\Api\One\User;

use App\Models\Courier;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Enums\CourierStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\CourierResource;
use Grimzy\LaravelMysqlSpatial\Types\Point;
use App\Http\Requests\User\Courier\CreateRequest;
use App\Http\Requests\User\Courier\SearchCourierRequest;

class CourierController extends Controller
{
    /**
     * Post courier service
     *
     * @param CreateRequest $request
     * @return Response
     */
    public function create(CreateRequest $request)
    {
        $couriers = $request->user()->userCouriers()->create([
            'title' => $request->title,
            'destination' => new Point($request->destination[0], $request->destination[1]),
            'origin' => new Point($request->origin[0], $request->origin[1]),
            'description' => $request->description,
            'price' => $request->price,
            'status' => CourierStatusEnum::PENDING,
        ]);

        return new CourierResource($couriers);
    }

    /**
     * Search for a courier service going to your package destination
     * 
     * @param SearchCourierRequest $request
     * @return Response
     */
    public function search(SearchCourierRequest $request)
    {
        // dd($request->all());
        $meter = 0;
        $courier = Courier::query()->depaturePoint(
            $request->depature_point[0],
            $request->depature_point[1],
        )
        ->arrivalPoint(
            $request->arrival_point[0],
            $request->arrival_point[1],
        )
        ->get();
        
        return CourierResource::collection($courier);
    }
}
