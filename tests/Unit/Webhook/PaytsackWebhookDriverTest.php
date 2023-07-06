<?php

namespace Tests\Unit\Webhook;

use App\Services\Payment\Gateway\Gateway;
use App\Services\Webhook\Drivers\PaystackWebhookDriver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Tests\Unit\Payment\Gateway\TestGatewayLabel;

class PaytsackWebhookDriverTest extends TestCase
{
    use RefreshDatabase;

    public function testValidateWebhook()
    {
        config([
            'services.paystack.secret' => 'sk_test_cc576tetfhey4567fdecdgfhjjfcfd6d8286b41'
        ]);

        $driver = new PaystackWebhookDriver();
        $request = Request::create('/webhook', 'POST', [], [], [], [], '{
    "event": "charge.success",
    "data": {
        "id": 1813090891,
        "domain": "test",
        "status": "success",
        "reference": "78d786ed-7bdf-4cdb-bd5f-877aa7316b44",
        "amount": 240451,
        "channel": "card",
        "currency": "NGN",
        "metadata": {
			"fee": "17766",
			"label": "test",
			"products": [
				{
					"name": "First Product",
					"price": "200000",
					"quantity": "1"
				},
				{
					"name": "Second Product",
					"price": "50000",
					"quantity": "2"
				}
			],
			"custom_filters": {
				"recurring": "true"
			},
			"custom_fields": [
				{
					"display_name": "Label",
					"variable_name": "label",
					"value": "test"
				},
				{
					"display_name": "Products",
					"variable_name": "products",
					"value": "2"
				},
				{
					"display_name": "Total",
					"variable_name": "total",
					"value": "₦ 3,000.00"
				},
				{
					"display_name": "Fee",
					"variable_name": "fee",
					"value": "₦ 177.66"
				}
			]
		},
        "fees_breakdown": null,
        "log": null,
        "fees": 3607,
        "fees_split": null,
        "authorization": {
            "authorization_code": "AUTH_d4g7a2vf89",
            "bin": "408408",
            "last4": "4081",
            "exp_month": "12",
            "exp_year": "2030",
            "channel": "card",
            "card_type": "visa ",
            "bank": "TEST BANK",
            "country_code": "NG",
            "brand": "visa",
            "reusable": true,
            "signature": "SIG_L5lLp1d8z3GDXXq6KxSx",
            "account_name": null,
            "receiver_bank_account_number": null,
            "receiver_bank": null
        },
        "customer": {
            "id": 79570651,
            "first_name": "Olawale",
            "last_name": "Ilesanmi",
            "email": "user@user.com",
            "customer_code": "CUS_pebmgp41jcic003",
            "phone": "08147386465",
            "metadata": null,
            "risk_action": "default",
            "international_format_phone": null
        }
    }
}');
        $request->headers->set(
            'x-paystack-signature',
            '6136a1741ac16e38d532d818cb608abba95f8aa6f0a3f7f52a533ee586f9c08280738d0f972dfb03a6c47d72a5e67b0f4d8f8423043cc9c5755ff9739f46ef42'
        );

        $this->assertTrue($driver->validate($request, $request->toArray(), $request->getContent()));
    }

    public function testProcessWebhook()
    {
        config([
            'services.paystack.secret' => 'sk_test_cc576tetfhey4567fdecdgfhjjfcfd6d8286b41'
        ]);

        $driver = new PaystackWebhookDriver();
        Gateway::label('test', TestGatewayLabel::class);

        $request = Request::create('/webhook', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json'
        ], $content = '{
    "event": "charge.success",
    "data": {
        "id": 1813090891,
        "domain": "test",
        "status": "success",
        "reference": "78d786ed-7bdf-4cdb-bd5f-877aa7316b44",
        "amount": 240451,
        "channel": "card",
        "currency": "NGN",
        "metadata": {
			"fee": "17766",
			"label": "test",
			"products": [
				{
					"name": "First Product",
					"price": "200000",
					"quantity": "1"
				},
				{
					"name": "Second Product",
					"price": "50000",
					"quantity": "2"
				}
			],
			"custom_filters": {
				"recurring": "true"
			},
			"custom_fields": [
				{
					"display_name": "Label",
					"variable_name": "label",
					"value": "test"
				},
				{
					"display_name": "Products",
					"variable_name": "products",
					"value": "2"
				},
				{
					"display_name": "Total",
					"variable_name": "total",
					"value": "₦ 3,000.00"
				},
				{
					"display_name": "Fee",
					"variable_name": "fee",
					"value": "₦ 177.66"
				}
			]
		},
        "fees_breakdown": null,
        "log": null,
        "fees": 3607,
        "fees_split": null,
        "authorization": {
            "authorization_code": "AUTH_d4g7a2vf89",
            "bin": "408408",
            "last4": "4081",
            "exp_month": "12",
            "exp_year": "2030",
            "channel": "card",
            "card_type": "visa ",
            "bank": "TEST BANK",
            "country_code": "NG",
            "brand": "visa",
            "reusable": true,
            "signature": "SIG_L5lLp1d8z3GDXXq6KxSx",
            "account_name": null,
            "receiver_bank_account_number": null,
            "receiver_bank": null
        },
        "customer": {
            "id": 79570651,
            "first_name": "Olawale",
            "last_name": "Ilesanmi",
            "email": "user@user.com",
            "customer_code": "CUS_pebmgp41jcic003",
            "phone": "08147386465",
            "metadata": null,
            "risk_action": "default",
            "international_format_phone": null
        }
    }
}');
        Http::fake([
            config('services.paystack.url') . 'transaction/verify/78d786ed-7bdf-4cdb-bd5f-877aa7316b44' =>
            Http::response([
                'status' => true,
                'message' => 'Verification successful',
                'data' => json_decode($content, true)['data']
            ], 200),
        ]);

        $request->headers->set(
            'x-paystack-signature',
            '6136a1741ac16e38d532d818cb608abba95f8aa6f0a3f7f52a533ee586f9c08280738d0f972dfb03a6c47d72a5e67b0f4d8f8423043cc9c5755ff9739f46ef42'
        );

        $response = $driver->process($request, $request->toArray(), $request->getContent());

        $this->assertSame(200, $response->getStatusCode());
    }
}
