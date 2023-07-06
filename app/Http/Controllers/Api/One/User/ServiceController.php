<?php

namespace App\Http\Controllers\Api\One\User;

use App\Models\User;
use App\Models\Rating;
use App\Models\Service;
use App\Models\Schedule;
use App\Models\Attachment;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Classes\SenderHelper;
use App\Enums\AppScreensEnum;
use App\Enums\AttachmentTypeEnum;
use App\Enums\ScheduleStatusEnum;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceResource;
use League\OAuth1\Client\Server\Server;
use App\Http\Resources\CustomerCollection;
use Grimzy\LaravelMysqlSpatial\Types\Point;
use App\Http\Resources\AttachmentCollection;
use App\Http\Requests\User\Service\CreateRequest;
use App\Http\Requests\User\Service\SearchRequest;
use App\Http\Requests\User\Service\UpdateRequest;
use App\Http\Requests\User\Service\ScheduleRequest;
use App\Http\Requests\User\Service\StoreRatingRequest;
use App\Http\Requests\User\Service\LocationSearchRequest;
use App\Http\Requests\User\Service\UploadAttachmentRequest;

class ServiceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $services = Service::paginate();
            foreach ($services as $service) {
                $provider = [];
                $providerType = "Individual";

                if (!$service->user()->get()[0]->business) {
                    $provider['name'] = $service->user()->get()[0]->getNameAttribute();
                    $provider['image'] = $service->user()->get()[0]->profile_img;
                } else {
                    $provider['name'] = $service->user()->get()[0]->rep_name . ' @ ' . $service->user()->get()[0]->business_name;
                    $provider['image'] = $service->user()->get()[0]->profile_img;
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
     * Display a listing most rated services.
     *
     * @return \Illuminate\Http\Response
     */
    public function most_rated()
    {
        try {
            $services = Service::with('ratings')->paginate();
            foreach ($services as $service) {
                $provider = [];


                if (!$service->user()->get()[0]->business) {
                    $provider['name'] = $service->user()->get()[0]->getNameAttribute();
                    $provider['image'] = $service->user()->get()[0]->profile_img;
                    $providerType = "Business";
                } else {
                    $provider['name'] = $service->user()->get()[0]->rep_name . ' @ ' . $service->user()->get()[0]->business_name;
                    $provider['image'] = $service->user()->get()[0]->profile_img;
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
     * Schedule a service.
     *
     * @return \Illuminate\Http\Response
     */
    public function schedule(ScheduleRequest $request, Service $service)
    {
        if ($request->user()->getKey() != $service->user_id) {
            $schedule = new Schedule();
            try {
                $values = [
                    'user_id' => $request->user()->getKey(),
                    'service_id' => $service->id,
                    'description' => $request->description,
                    'location' => new Point($request->location[0], $request->location[1]),
                    'dueDate' => $request->dueDate,
                    'status' => ScheduleStatusEnum::BOOKED,
                ];
                if (Schedule::where([
                    ['user_id', $request->user()->getKey()],
                    ['service_id', $service->getKey()]
                ])->first() != null) {
                    if (
                        Schedule::where([
                            ['user_id', $request->user()->getKey()],
                            ['service_id', $service->getKey()]
                        ])->count() > 0
                        &&
                        Schedule::where([
                            ['user_id', $request->user()->getKey()],
                            ['service_id', $service->getKey()]
                        ])->first()->status != ScheduleStatusEnum::DECLINED
                        &&
                        Schedule::where([
                            ['user_id', $request->user()->getKey()],
                            ['service_id', $service->getKey()]
                        ])->first()->status != ScheduleStatusEnum::SETTELED
                    ) {
                        return response()->json([
                            'success' => false,
                            'message' => "You have already scheduled this service"
                        ])
                            ->setStatusCode(403);
                    } elseif (
                        Schedule::where([
                            ['user_id', $request->user()->getKey()],
                            ['service_id', $service->getKey()]
                        ])->count() > 0
                        &&
                        (Schedule::where([
                            ['user_id', $request->user()->getKey()],
                            ['service_id', $service->getKey()]
                        ])->first()->status == ScheduleStatusEnum::DECLINED)
                        ||
                        Schedule::where([
                            ['user_id', $request->user()->getKey()],
                            ['service_id', $service->getKey()]
                        ])->first()->status == ScheduleStatusEnum::SETTELED
                    ) {
                        Schedule::where([
                            ['user_id', $request->user()->getKey()],
                            ['service_id', $service->getKey()]
                        ])->update($values);
                        $schedule = Schedule::where([
                            ['user_id', $request->user()->getKey()],
                            ['service_id', $service->getKey()]
                        ])->first();

                        $schedule->attachments()->delete();
                    }
                } else {
                    $schedule = $request->user()->schedules()->create($values);
                    SenderHelper::appNotification($schedule->user(), 'New Appointment', $request->user()->name . ' scheduled an appointment with you', AppScreensEnum::MYSERVICES);
                    SenderHelper::userLog($request->user(), 'You scheduled an appointment with ' . $request->user()->name . '.', AppScreensEnum::SERVICES);
                }

                // Upload attachments? if any
                $attachments = [];

                foreach ($request->attachments as $attachment) {
                    /**
                     * @var \Illuminate\Http\UploadedFile $attachment
                     */
                    $attachment->storeAs(
                        'attachments',
                        $fileName = Str::orderedUuid() . '.' . $attachment->getClientOriginalExtension()
                    );

                    $type = match (\explode('/', $attachment->getMimeType())[0]) {
                        'image' => AttachmentTypeEnum::IMAGE,
                        'video' => AttachmentTypeEnum::VIDEO,
                        'audio' => AttachmentTypeEnum::AUDIO,
                        default => AttachmentTypeEnum::DOCUMENT,
                    };

                    $attachments[] = $schedule->attachments()->create([
                        'user_id' => $request->user()->getKey(),
                        'type' => $type,
                        'name' => $attachment->getClientOriginalName(),
                        'mime_type' => $attachment->getMimeType(),
                        'extention' => $attachment->extension(),
                        'size' => $attachment->getSize(),
                        'file' => $fileName
                    ]);
                }

                $attachments = \collect($attachments);

                return response()->json([
                    'success' => true,
                    'message' => 'Schedule created successfully'
                ]);
            } catch (\Exception $e) {
                return response()->json(['message' => $e->getMessage(), 'error' => $e->getTrace()]);
                //return response()->json(['message' => $e->getMessage()]);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => "You can not schedule your service"
            ])
                ->setStatusCode(403);
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
            $services = Service::where("title", "like", "%" . $request->search . "%")
                ->orWhere("type", "like", "%" . $request->search . "%")
                ->orWhere("description", "like", "%" . $request->search . "%")
                ->with('user')
                ->with('ratings')
                ->paginate();

            return response()->json([
                'services' => $services,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateRequest $request)
    {
        $attachments = [];

        $service = $request->user()->services()->create([
            'coordinate' => new Point($request->coordinate[1], $request->coordinate[0]),
            'title' => $request->title,
            'type' => $request->type,
            'price' => $request->price,
            'description' => $request->description,
            'duration' => $request->duration,
        ]);


        foreach ($request->attachments as $attachment) {
            /**
             * @var \Illuminate\Http\UploadedFile $attachment
             */
            $attachment->storeAs(
                'attachments',
                $fileName = Str::orderedUuid() . '.' . $attachment->getClientOriginalExtension()
            );

            $type = match (\explode('/', $attachment->getMimeType())[0]) {
                'image' => AttachmentTypeEnum::IMAGE,
                'video' => AttachmentTypeEnum::VIDEO,
                'audio' => AttachmentTypeEnum::AUDIO,
                default => AttachmentTypeEnum::DOCUMENT,
            };

            $attachments[] = $service->attachments()->create([
                'user_id' => $request->user()->getKey(),
                'type' => $type,
                'name' => $attachment->getClientOriginalName(),
                'mime_type' => $attachment->getMimeType(),
                'extention' => $attachment->extension(),
                'size' => $attachment->getSize(),
                'file' => $fileName
            ]);
        }

        $attachments = \collect($attachments);

        return new ServiceResource($service);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \  $request
     * @return \Illuminate\Http\Response
     */
    public function uploadAttachments(UploadAttachmentRequest $request, Service $service)
    {
        if ($request->user()->getKey() == $service->user_id) {
            $attachments = [];

            foreach ($request->attachments as $attachment) {
                /**
                 * @var \Illuminate\Http\UploadedFile $attachment
                 */
                $attachment->storeAs(
                    'attachments',
                    $fileName = Str::orderedUuid() . '.' . $attachment->getClientOriginalExtension()
                );

                $type = match (\explode('/', $attachment->getMimeType())[0]) {
                    'image' => AttachmentTypeEnum::IMAGE,
                    'video' => AttachmentTypeEnum::VIDEO,
                    'audio' => AttachmentTypeEnum::AUDIO,
                    default => AttachmentTypeEnum::DOCUMENT,
                };

                $attachments[] = $service->attachments()->create([
                    'user_id' => $request->user()->getKey(),
                    'type' => $type,
                    'name' => $attachment->getClientOriginalName(),
                    'mime_type' => $attachment->getMimeType(),
                    'extention' => $attachment->extension(),
                    'size' => $attachment->getSize(),
                    'file' => $fileName
                ]);
            }

            $attachments = \collect($attachments);

            return new ServiceResource($service);
        } else {
            return response()->json([
                'success' => false,
                'message' => "You do not have permission to access this resource"
            ])
                ->setStatusCode(403);
        }
    }

    /**
     * Get artisans within a particular radius
     *
     * @param Service $service
     * @param integer $meter
     */
    public function artisans(LocationSearchRequest $request, $meter = 1000)
    {
        $artisans = User::artisans()->artisansCloseTo(
            $request->coordinate[0],
            $request->coordinate[1],
            (int)$meter,
        )->paginate(20);

        CustomerCollection::wrap('artisans');

        return new CustomerCollection($artisans);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Service $service)
    {
        return new ServiceResource($service);
    }

    public function rate(StoreRatingRequest $request, Service $service)
    {
        if ($request->user()->getKey() != $service->user_id) {
            try {
                $values = [
                    'by' => $request->user()->getKey(),
                    'type' => 'SERVICE',
                    'for' => $service->id,
                    'review' => $request->review,
                    'score' => $request->rating
                ];
                if (Rating::where([
                    ['by', $request->user()->getKey()],
                    ['type', 'SERVICE'],
                    ['for', $service->getKey()]
                ])->count() > 0) {
                    Rating::where([
                        ['by', $request->user()->getKey()],
                        ['type', 'SERVICE'],
                        ['for', $service->getKey()]
                    ])->update($values);
                } else {
                    Rating::create($values);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Review submitted successfully'
                ]);
            } catch (\Exception $e) {
                return response()->json(['message' => $e->getMessage()]);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => "You can not rate your service"
            ])
                ->setStatusCode(403);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateRequest $request, Service $service)
    {
        if ($request->user()->getKey() == $service->user_id) {
            try {
                Service::where('id', $service->getKey())->update($request->validated());
                return response()->json([
                    'success' => true,
                    'message' => 'Service updated successfully'
                ]);
            } catch (\Exception $e) {
                return response()->json(['message' => $e->getMessage()]);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => "You do not have access to this resource."
            ])
                ->setStatusCode(403);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Service $service)
    {
        if ($request->user()->getKey() == $service->user_id) {
            try {
                Service::where('id', $service->getKey())->delete();
                return response()->json([
                    'success' => true,
                    'message' => 'Service removed successfully'
                ]);
            } catch (\Exception $e) {
                return response()->json(['message' => $e->getMessage()]);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => "You do not have access to this resource."
            ])
                ->setStatusCode(403);
        }
    }


    public function bookAService(Request $request)
    {
    }
}
