<?php

namespace Tests\Unit\Webhook;

use App\Services\Webhook\Drivers\MockWebhookDriver;
use App\Services\Webhook\Drivers\WebhookInterface;
use App\Services\Webhook\Webhook;
use App\Services\Webhook\WebhookManager;
use App\Services\Webhook\WebhookNotFoundException;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Tests\TestCase;

class WebhookManagerTest extends TestCase
{
    use RefreshDatabase;

    public function testAddDriverClass()
    {
        $manager = new WebhookManager();

        $manager->driver('mock', MockWebhookDriver::class);

        $this->assertInstanceOf(MockWebhookDriver::class, $manager->driver('mock'));
    }

    public function testAddDriverClosure()
    {
        $manager = new WebhookManager();

        $manager->driver('mock', function () {
            return new MockWebhookDriver();
        });

        $this->assertInstanceOf(MockWebhookDriver::class, $manager->driver('mock'));
    }

    public function testAddDriverClosureReturn()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(sprintf(
            'Closure resolver must return an instance of %s',
            WebhookInterface::class
        ));

        $manager = new WebhookManager();
        $manager->driver('mock', function () {
            return $this;
        });

        $manager->driver('mock');
    }

    public function testDriverNotFound()
    {
        $this->expectException(WebhookNotFoundException::class);
        $this->expectExceptionMessage('"mock" not found as a webhook driver');
        $manager = new WebhookManager();
        $manager->driver('mock');
    }

    public function testAddWrongDriver()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(sprintf(
            'Webhook driver must implement [%s] interface',
            WebhookInterface::class
        ));
        $manager = new WebhookManager();
        $manager->driver('mock', WebhookManagerTest::class);
    }

    public function testWrongDriverResolver()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'A webhook driver can only be resolved through class name or closure'
        );
        $manager = new WebhookManager();
        $manager->driver('mock', []);
    }

    public function testMockDriver()
    {
        $driver = new MockWebhookDriver();
        $request = Request::create('/');

        $this->assertSame('mock', $driver->name());
        $this->assertTrue($driver->validate($request, [], '{}'));
        $this->assertInstanceOf(MockWebhookDriver::class, $driver->dontValidate());
        $this->assertNotTrue($driver->validate($request, [], '{}'));
        $this->assertTrue($driver->process($request, [], '{}')->getStatusCode() == 200);
        $this->assertInstanceOf(MockWebhookDriver::class, $driver->setResponse(response()->json([
                'status' => 'failed'
            ], JsonResponse::HTTP_BAD_REQUEST)));
        $this->assertTrue($driver->process($request, [], '{}')->getStatusCode() == 400);

        $driver = new MockWebhookDriver('mocked');
        $this->assertSame('mocked', $driver->name());
    }

    public function testProcessWebhook()
    {
        Webhook::driver('test', TestWebhookDriver::class);
        $request = Request::create('/webhook', 'POST', [], [], [], [], '{"name":"Olawale"}');
        $request->headers->set(
            'signature',
            'c266d9dbbbc362f480ddfc50000b4b6ca9ae042bf358633f439ea3ddffac7e5889a924bfdcd124697047cb305635ee13ba1115659e6a29f6f2bbd5f3cfb7504d'
        );

        $response = Webhook::processWebhook('test', $request);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testProcessWebhookBadRequest()
    {
        Webhook::driver('test', TestWebhookDriver::class);
        $request = Request::create('/webhook', 'POST', [], [], [], [], '{"name":"Olawale"}');
        $request->headers->set(
            'signature',
            'c266d9dbb35ee13ba1115659e6a29f6f2bbd5f3cfb7504d'
        );

        $response = Webhook::processWebhook('test', $request);

        $this->assertSame(400, $response->getStatusCode());
    }

    public function testProcessWebhookNotFound()
    {
        Webhook::driver('test', TestWebhookDriver::class);
        $request = Request::create('/webhook', 'POST', [], [], [], [], '{"name":"Olawale"}');
        $request->headers->set(
            'signature',
            'c266d9dbbbc362f480ddfc50000b4b6ca9ae042bf358633f439ea3ddffac7e5889a924bfdcd124697047cb305635ee13ba1115659e6a29f6f2bbd5f3cfb7504d'
        );

        $response = Webhook::processWebhook('test_', $request);

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testProcessWebhookEndpoint()
    {
        Webhook::driver('test', TestWebhookDriver::class);
        $content = '{"name":"Olawale"}';

        $headers = array_merge([
            'CONTENT_LENGTH' => mb_strlen($content, '8bit'),
            'CONTENT_TYPE' => 'application/json',
            'Accept' => 'application/json',
        ], [
            'signature' => 'c266d9dbbbc362f480ddfc50000b4b6ca9ae042bf358633f439ea3ddffac7e5889a924bfdcd124697047cb305635ee13ba1115659e6a29f6f2bbd5f3cfb7504d'
        ]);

        $response = $this->call(
            'POST',
            '/external/webhook/test',
            [],
            [],
            [],
            $this->transformHeadersToServerVars($headers),
            $content
        );

        $response->assertStatus(200);
        $response->assertJsonPath('status', 'processed');
    }
}
