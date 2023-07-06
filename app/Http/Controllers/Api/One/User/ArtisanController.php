<?php

namespace App\Http\Controllers\Api\One\User;

use App\Models\User;
use App\Models\Service;
use App\Models\Schedule;
use Illuminate\Http\Request;
use App\Classes\SenderHelper;
use App\Enums\AppScreensEnum;
use App\Enums\ScheduleStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\ScheduleResource;

class ArtisanController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show_new_appointments(Request $request)
    {
        try {
            $services = $request->user()->services()->get('id');
            $serviceIds = [];
            foreach ($services as $service) {
                array_push($serviceIds, $service->id);
            }
            $schedules = Schedule::where(
                [
                    ['service_id', $serviceIds],
                    ['status', ScheduleStatusEnum::BOOKED]
                ]
            )->paginate();

            foreach ($schedules as $schedule) {
                $reciever = [];
                $recieverType = "Individual";

                if (!User::where('id', $schedule->user_id)->get()[0]->business) {
                    $reciever['name'] = User::where('id', $schedule->user_id)->get()[0]->getNameAttribute();
                    $reciever['image'] = User::where('id', $schedule->user_id)->get()[0]->profile_img;
                } else {
                    $reciever['name'] = User::where('id', $schedule->user_id)->get()[0]->rep_name . ' @ ' . User::where('id', $schedule->user_id)->get()[0]->business_name;

                    $reciever['image'] = User::where('id', $schedule->user_id)->get()[0]->profile_img;
                }

                $schedule['service_title'] = Service::where('id', $schedule->service_id)->first()->title ?? "";
                $schedule['reciever'] = $reciever;
                $schedule['recieverType'] = $recieverType;
            }
            return response()->json($schedules);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show_current(Request $request)
    {
        try {
            $services = $request->user()->services()->get('id');
            $serviceIds = [];
            $accepted = [ScheduleStatusEnum::BOOKED, ScheduleStatusEnum::OPENED, ScheduleStatusEnum::ONGOING];
            foreach ($services as $service) {
                array_push($serviceIds, $service->id);
            }
            $schedules = Schedule::where(
                [
                    ['service_id', $serviceIds],
                    //['status' , $accepted]
                ]
            )->whereIn('status', $accepted)->paginate();

            foreach ($schedules as $schedule) {
                $reciever = [];
                $recieverType = "Individual";

                if (!User::where('id', $schedule->user_id)->get()[0]->business) {
                    $reciever['name'] = User::where('id', $schedule->user_id)->get()[0]->getNameAttribute();
                    $reciever['image'] = User::where('id', $schedule->user_id)->get()[0]->profile_img;
                } else {
                    $reciever['name'] = User::where('id', $schedule->user_id)->get()[0]->rep_name . ' @ ' . User::where('id', $schedule->user_id)->get()[0]->business_name;

                    $reciever['image'] = User::where('id', $schedule->user_id)->get()[0]->profile_img;
                }

                $schedule['service_title'] = Service::where('id', $schedule->service_id)->first()->title ?? "";
                $schedule['reciever'] = $reciever;
                $schedule['recieverType'] = $recieverType;
            }
            return response()->json($schedules);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function show_ongoing(Request $request)
    {
        try {
            $services = $request->user()->services()->get('id');
            $serviceIds = [];
            foreach ($services as $service) {
                array_push($serviceIds, $service->id);
            }
            $schedules = Schedule::where(
                [
                    ['service_id', $serviceIds],
                    ['status', [ScheduleStatusEnum::ONGOING]]
                ]
            )->paginate();

            foreach ($schedules as $schedule) {
                $reciever = [];
                $recieverType = "Individual";

                if (!User::where('id', $schedule->user_id)->get()[0]->business) {
                    $reciever['name'] = User::where('id', $schedule->user_id)->get()[0]->getNameAttribute();
                    $reciever['image'] = User::where('id', $schedule->user_id)->get()[0]->profile_img;
                } else {
                    $reciever['name'] = User::where('id', $schedule->user_id)->get()[0]->rep_name . ' @ ' . User::where('id', $schedule->user_id)->get()[0]->business_name;

                    $reciever['image'] = User::where('id', $schedule->user_id)->get()[0]->profile_img;
                }

                $schedule['service_title'] = Service::where('id', $schedule->service_id)->first()->title ?? "";
                $schedule['reciever'] = $reciever;
                $schedule['recieverType'] = $recieverType;
            }
            return response()->json($schedules);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function show_settled(Request $request)
    {
        try {
            $services = $request->user()->services()->get('id');
            $serviceIds = [];
            foreach ($services as $service) {
                array_push($serviceIds, $service->id);
            }
            $schedules = Schedule::where(
                [
                    ['service_id', $serviceIds],
                    ['status', [ScheduleStatusEnum::SETTELED]]
                ]
            )->paginate();

            foreach ($schedules as $schedule) {
                $reciever = [];
                $recieverType = "Individual";

                if (!User::where('id', $schedule->user_id)->get()[0]->business) {
                    $reciever['name'] = User::where('id', $schedule->user_id)->get()[0]->getNameAttribute();
                    $reciever['image'] = User::where('id', $schedule->user_id)->get()[0]->profile_img;
                } else {
                    $reciever['name'] = User::where('id', $schedule->user_id)->get()[0]->rep_name . ' @ ' . User::where('id', $schedule->user_id)->get()[0]->business_name;

                    $reciever['image'] = User::where('id', $schedule->user_id)->get()[0]->profile_img;
                }

                $schedule['service_title'] = Service::where('id', $schedule->service_id)->first()->title ?? "";
                $schedule['reciever'] = $reciever;
                $schedule['recieverType'] = $recieverType;
            }
            return response()->json($schedules);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function open(Request $request, Schedule $schedule)
    {
        try {

            $services = $request->user()->services()->get('id');
            $serviceIds = [];
            foreach ($services as $service) {
                array_push($serviceIds, $service->id);
            }

            if (in_array($schedule->service_id, $serviceIds)) {
                if ($schedule->status == ScheduleStatusEnum::BOOKED) {
                    $schedule->update([
                        'status' => ScheduleStatusEnum::OPENED
                    ]);
                }
                return new ScheduleResource($schedule);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have access to this resource.'
                ], 403);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function accept(Request $request, Schedule $schedule)
    {
        try {

            $services = $request->user()->services()->get('id');
            $serviceIds = [];
            foreach ($services as $service) {
                array_push($serviceIds, $service->id);
            }

            if (in_array($schedule->service_id, $serviceIds)) {
                if ($schedule->status != ScheduleStatusEnum::DECLINED && $schedule->status != ScheduleStatusEnum::ONGOING && $schedule->status != ScheduleStatusEnum::SETTELED) {
                    $schedule->update([
                        'status' => ScheduleStatusEnum::ONGOING
                    ]);
                    SenderHelper::appNotification($schedule->user(), 'Appointment Approved', 'Your appointment with ' . $request->user()->name . ' has been approved.', AppScreensEnum::SERVICES);
                    SenderHelper::userLog($request->user(), 'Your appointment with ' . $request->user()->name . ' was approved.', AppScreensEnum::SERVICES);
                    return response()->json([
                        'success' => true,
                        'message' => 'Appointment accepted successfully'
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'This appointment has expired or has already been accepted.'
                    ], 403);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have access to this resource.'
                ], 403);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function decline(Request $request, Schedule $schedule)
    {
        try {

            $services = $request->user()->services()->get('id');
            $serviceIds = [];
            foreach ($services as $service) {
                array_push($serviceIds, $service->id);
            }

            if (in_array($schedule->service_id, $serviceIds)) {
                if ($schedule->status != ScheduleStatusEnum::SETTELED && $schedule->status != ScheduleStatusEnum::ONGOING && $schedule->status != ScheduleStatusEnum::DECLINED) {
                    $schedule->update([
                        'status' => ScheduleStatusEnum::DECLINED
                    ]);
                    SenderHelper::appNotification($schedule->user(), 'Appointment Declined', 'Your appointment with ' . $request->user()->name . ' has been declined.', AppScreensEnum::SERVICES);
                    SenderHelper::userLog($request->user(), 'Your appointment with ' . $request->user()->name . ' was declined.', AppScreensEnum::SERVICES);
                    return response()->json([
                        'success' => true,
                        'message' => 'Appointment declined successfully'
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'This appointment has expired or has already been declined.'
                    ], 403);
                }
                return new ScheduleResource($schedule);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have access to this resource.'
                ], 403);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
