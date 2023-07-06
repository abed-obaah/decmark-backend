<?php

namespace Tests\Feature\User;

use App\Enums\ServiceTypeEnum;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ServicesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testCreate()
    {
        $this->loginUser();

        $response = $this->post('/v1/user/services', [
            'coordinate' => [
                7.615736,
                5.235415
            ],
            'title' => 'This is a test service',
            'type' => ServiceTypeEnum::TAYLORING,
            'price' => 1000000,
            'description' => 'I created this during a test case',
            'duration' => 10
        ]);

        //$response->dump();

        $response->assertStatus(201)->assertJsonStructure([
            'id',
            'title',
            'type',
            'coordinate',
            'price',
            'description',
            'duration',
            'status',
        ]);
    }

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testUploadAttachment()
    {
        Storage::fake('public');
        $user = $this->loginUser();

        $service = Service::factory()->user($user)->create();

        $attachment1 = UploadedFile::fake()->image('attachment1.jpg', 700, 1000);
        $attachment2 = UploadedFile::fake()->image('attachment2.jpg', 700, 1000);

        $response = $this->post('/v1/user/services/' . $service->id . '/attachments', [
            'attachments' => [
                $attachment1,
                $attachment2
            ]
        ]);

        //$response->dump();

        $response->assertStatus(200)->assertJsonStructure([
            0 => [
                'id',
                'type',
                'name',
                'mime_type',
                'extention',
                'size',
                'url',
                'user',
                'parent'
            ],
            1 => [
                'id',
                'type',
                'name',
                'mime_type',
                'extention',
                'size',
                'url',
                'user',
                'parent'
            ]
        ]);
    }

    // public function testNearbyArtisans()
    // {
    //     $user = $this->loginUser();
    //     $user1 = $this->newUser();

    //     $service = Service::factory()->user($user)->create();

    //     $user->forceFill([
    //         'artisan' => true,
    //         'artisan_type' => $service->type,
    //         'artisan_bio' => 'I design fashion',
    //         'artisan_status' => 'ACTIVE',
    //         'artisan_coordinate' => DB::raw('POINT(7.615236,5.237213)'),
    //         'artisan_place' => [
    //             'id' => 'knkfvndnuweiunwiunew',
    //             'name' => 'Ado, Ekiti',
    //             'coordinate' => [7.615236, 5.237213],
    //             'provider' => 'google'
    //         ],
    //     ]);


    //     $response = $this->get('/v1/user/services/' . $service->id . '/artisans');

    //     $response->assertStatus(200)->assertJsonStructure([
    //         'artisans' => [
    //             0 => [
    //                 'name',
    //                 'artisan',
    //                 'rider'
    //             ]
    //         ]
    //     ])->assertJsonPath('artisans.0.name', $user1->name);
    // }
}
