<?php

namespace Tests\Feature\User;

use App\Models\BankCard;
use App\Services\Payment\Gateway\Drivers\PaystackGatewayDriver;
use App\Services\Payment\Gateway\Gateway;
use App\Services\Payment\Gateway\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Spatie\TestTime\TestTime;
use Tests\TestCase;
use Walletable\Money\Money;

class BankCardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testStart()
    {
        $user = $this->loginUser();

        Http::fake([
            config('services.paystack.url') . 'transaction/initialize' => Http::response([
                'status' => true,
                'message' => 'Authorization URL created',
                'data' => [
                    'authorization_url' => 'https://checkout.paystack.com/0peioxfhpn',
                    'access_code' => '0peioxfhpn',
                    'reference' => 'f06e7f30-3367-40c3-ada0-87ad683c74c3'
                ]
            ], 200),

            config('services.paystack.url') . 'customer/' . $user->email => Http::response([
                'status' => true,
                'message' => 'Customer retrieved',
                'data' => [
                    //
                ]
            ], 200),
        ]);

        $response = $this->postJson(route('api.v1.user.bank_cards.start'), [
            'label' => 'My Wema Card'
        ]);

        $response->assertStatus(200)->assertJsonStructure([
            'message',
            'url',
            'id',
            'reference'
        ]);

        $this->assertInstanceOf(BankCard::class, $card = BankCard::find($response->json('id')));

        $response->assertJsonPath('message', 'Initiated successfullly.')
            ->assertJsonPath('url', 'https://checkout.paystack.com/0peioxfhpn')
            ->assertJsonPath('id', $card->id)
            ->assertJsonPath('reference', $card->reference);

        $this->assertSame(1, $user->bankCards()->count());
    }

    public function testLabel()
    {
        TestTime::freeze();
        $user = $this->newUser();
        /**
         * @var PaystackGatewayDriver
         */
        $paystack = Gateway::driver('paystack');

        $card = $user->bankCards()->create([
            'label' => 'My Wema Card',
            'reference' => Str::uuid(),
            'driver' => 'paystack',
        ]);

        Http::fake([
            config('services.paystack.url') . 'transaction/verify/' . $card->reference =>
            Http::response([
                'status' => true,
                'message' => 'Charge attempted',
                'data' => [
                    'id' => 1787520606,
                    'domain' => 'test',
                    'status' => 'success',
                    'reference' => $card->reference,
                    'amount' => 10150,
                    'message' => null,
                    'gateway_response' => 'Successful',
                    'helpdesk_link' => null,
                    'paid_at' => '2022-05-01T02:15:45.000Z',
                    'created_at' => '2022-05-01T02:15:34.000Z',
                    'channel' => 'card',
                    'currency' => 'NGN',
                    'ip_address' => '102.89.42.247',
                    'metadata' => [
                        'fee' => 150,
                        'label' => 'bank_card',
                        'products' => [
                            [
                                'name' => 'My Wema Card',
                                'price' => '10000',
                                'quantity' => '1',
                                'model_id' => (string)$card->id,
                                'model_type' => $card->getMorphClass()
                            ]
                        ],
                        'custom_fields' => [
                            [
                                'display_name' => 'Label',
                                'variable_name' => 'label',
                                'value' => 'bank_card'
                            ],
                            [
                                'display_name' => 'Products',
                                'variable_name' => 'products',
                                'value' => '1'
                            ],
                            [
                                'display_name' => 'Total',
                                'variable_name' => 'total',
                                'value' => '₦ 100.00'
                            ],
                            [
                                'display_name' => 'Fee',
                                'variable_name' => 'fee',
                                'value' => '₦ 101.50'
                            ]
                        ]
                    ],
                    'authorization' => [
                        'authorization_code' => 'AUTH_wvo1t4opcg',
                        'bin' => '408408',
                        'last4' => '4081',
                        'exp_month' => '12',
                        'exp_year' => '2030',
                        'channel' => 'card',
                        'card_type' => 'visa ',
                        'bank' => 'TEST BANK',
                        'country_code' => 'NG',
                        'brand' => 'visa',
                        'reusable' => true,
                        'signature' => 'SIG_L5lLp1d8z3GDXXq6KxSx',
                        'account_name' => null,
                        'receiver_bank_account_number' => null,
                        'receiver_bank' => null
                    ],
                    'customer' => [
                        'id' => 49367561,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'metadata' => null,
                        'risk_action' => 'default',
                        'international_format_phone' => null
                    ],
                ]
            ], 200),

            config('services.paystack.url') . 'refund' => Http::response([
                'status' => true,
                'message' => 'Refund has been queued for processing',
                'data' => [
                    'transaction' => [
                        'id' => 1494587920,
                        'domain' => 'test',
                        'reference' => 'f06e7f30-3367-40c3-ada0-87ad683c74c3',
                        'amount' => 200000,
                        'paid_at' => '2021-12-08T12:53:14.000Z',
                        'channel' => 'card',
                        'currency' => 'NGN',
                        'authorization' => [
                            'exp_month' => null,
                            'exp_year' => null,
                            'account_name' => null
                        ],
                        'customer' => [
                            'international_format_phone' => null
                        ],
                        'plan' => [],
                        'subaccount' => [
                            'currency' => null
                        ],
                        'split' => [],
                        'order_id' => null,
                        'paidAt' => '2021-12-08T12:53:14.000Z',
                        'pos_transaction_data' => null,
                        'source' => null,
                        'fees_breakdown' => null
                    ],
                    'integration' => 412711,
                    'deducted_amount' => 0,
                    'channel' => null,
                    'merchant_note' => 'Refund Bank card fee.',
                    'customer_note' => 'Refund for transaction ' . $card->reference,
                    'status' => 'pending',
                    'refunded_by' => 'themanetech@gmail.com',
                    'expected_at' => '2022-05-10T12:25:18.452Z',
                    'currency' => 'NGN',
                    'domain' => 'test',
                    'amount' => 500000,
                    'fully_deducted' => false,
                    'id' => 4828619,
                    'createdAt' => '2022-05-01T12:25:18.508Z',
                    'updatedAt' => '2022-05-01T12:25:18.508Z'
                ]
            ], 200),
        ]);

        $transaction = new Transaction(
            $paystack,
            $card->reference,
            Transaction::SUCCESS,
            Gateway::label('bank_card'),
            Money::NGN(10000),
            Money::NGN(150),
            $paystack->createCustomerFromPayload([
                'id' => 49367561,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'metadata' => null,
                'risk_action' => 'default',
                'international_format_phone' => null
            ]),
            $paystack->createProductsFromPayload([
                [
                    'name' => 'My Wema Card',
                    'price' => '10000',
                    'quantity' => '1',
                    'model_id' => (string)$card->id,
                    'model_type' => $card->getMorphClass()
                ]
            ])->withModel(),
            $paystack->createMetaFromPayload([
                [
                    'display_name' => 'Label',
                    'variable_name' => 'label',
                    'value' => 'bank_card'
                ],
                [
                    'display_name' => 'Products',
                    'variable_name' => 'products',
                    'value' => '1'
                ],
                [
                    'display_name' => 'Total',
                    'variable_name' => 'total',
                    'value' => '₦ 100.00'
                ],
                [
                    'display_name' => 'Fee',
                    'variable_name' => 'fee',
                    'value' => '₦ 101.50'
                ]
            ]),
            $paystack->createCardFromPayload([
                'authorization_code' => 'AUTH_wvo1t4opcg',
                'bin' => '408408',
                'last4' => '4081',
                'exp_month' => '12',
                'exp_year' => '2030',
                'channel' => 'card',
                'card_type' => 'visa ',
                'bank' => 'TEST BANK',
                'country_code' => 'NG',
                'brand' => 'visa',
                'reusable' => true,
                'signature' => 'SIG_L5lLp1d8z3GDXXq6KxSx',
                'account_name' => 'Olawale Ilesanmi',
                'receiver_bank_account_number' => null,
                'receiver_bank' => null
            ])
        );

        Gateway::label('bank_card')->success($transaction);

        $card->refresh();
        $this->assertSame('AUTH_wvo1t4opcg', $card->token);
        $this->assertSame('408408******4081', $card->number);
        $this->assertSame('12', $card->expiry_month);
        $this->assertSame('2030', $card->expiry_year);
        $this->assertSame('visa', $card->brand);
        $this->assertSame('Olawale Ilesanmi', $card->name);
        $this->assertTrue(!is_null($card->refunded_at));
        $this->assertTrue(!is_null($card->paid_at));
    }

    public function testLabelUnauthorizedCard()
    {
        TestTime::freeze();
        $user = $this->newUser();
        /**
         * @var PaystackGatewayDriver
         */
        $paystack = Gateway::driver('paystack');

        $card = $user->bankCards()->create([
            'label' => 'My Wema Card',
            'reference' => Str::uuid(),
            'driver' => 'paystack',
        ]);

        Http::fake([
            config('services.paystack.url') . 'transaction/verify/' . $card->reference =>
            Http::response([
                'status' => true,
                'message' => 'Charge attempted',
                'data' => [
                    'id' => 1787520606,
                    'domain' => 'test',
                    'status' => 'success',
                    'reference' => $card->reference,
                    'amount' => 10150,
                    'message' => null,
                    'gateway_response' => 'Successful',
                    'helpdesk_link' => null,
                    'paid_at' => '2022-05-01T02:15:45.000Z',
                    'created_at' => '2022-05-01T02:15:34.000Z',
                    'channel' => 'card',
                    'currency' => 'NGN',
                    'ip_address' => '102.89.42.247',
                    'metadata' => [
                        'fee' => 150,
                        'label' => 'bank_card',
                        'products' => [
                            [
                                'name' => 'My Wema Card',
                                'price' => '10000',
                                'quantity' => '1',
                                'model_id' => (string)$card->id,
                                'model_type' => $card->getMorphClass()
                            ]
                        ],
                        'custom_fields' => [
                            [
                                'display_name' => 'Label',
                                'variable_name' => 'label',
                                'value' => 'bank_card'
                            ],
                            [
                                'display_name' => 'Products',
                                'variable_name' => 'products',
                                'value' => '1'
                            ],
                            [
                                'display_name' => 'Total',
                                'variable_name' => 'total',
                                'value' => '₦ 100.00'
                            ],
                            [
                                'display_name' => 'Fee',
                                'variable_name' => 'fee',
                                'value' => '₦ 101.50'
                            ]
                        ]
                    ],
                    'authorization' => [
                        'authorization_code' => 'AUTH_wvo1t4opcg',
                        'bin' => '408408',
                        'last4' => '4081',
                        'exp_month' => '12',
                        'exp_year' => '2030',
                        'channel' => 'card',
                        'card_type' => 'visa ',
                        'bank' => 'TEST BANK',
                        'country_code' => 'NG',
                        'brand' => 'visa',
                        'reusable' => false,
                        'signature' => 'SIG_L5lLp1d8z3GDXXq6KxSx',
                        'account_name' => null,
                        'receiver_bank_account_number' => null,
                        'receiver_bank' => null
                    ],
                    'customer' => [
                        'id' => 49367561,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'metadata' => null,
                        'risk_action' => 'default',
                        'international_format_phone' => null
                    ],
                ]
            ], 200),

            config('services.paystack.url') . 'refund' => Http::response([
                'status' => true,
                'message' => 'Refund has been queued for processing',
                'data' => [
                    'transaction' => [
                        'id' => 1494587920,
                        'domain' => 'test',
                        'reference' => 'f06e7f30-3367-40c3-ada0-87ad683c74c3',
                        'amount' => 200000,
                        'paid_at' => '2021-12-08T12:53:14.000Z',
                        'channel' => 'card',
                        'currency' => 'NGN',
                        'authorization' => [
                            'exp_month' => null,
                            'exp_year' => null,
                            'account_name' => null
                        ],
                        'customer' => [
                            'international_format_phone' => null
                        ],
                        'plan' => [],
                        'subaccount' => [
                            'currency' => null
                        ],
                        'split' => [],
                        'order_id' => null,
                        'paidAt' => '2021-12-08T12:53:14.000Z',
                        'pos_transaction_data' => null,
                        'source' => null,
                        'fees_breakdown' => null
                    ],
                    'integration' => 412711,
                    'deducted_amount' => 0,
                    'channel' => null,
                    'merchant_note' => 'Refund Bank card fee.',
                    'customer_note' => 'Refund for transaction ' . $card->reference,
                    'status' => 'pending',
                    'refunded_by' => 'themanetech@gmail.com',
                    'expected_at' => '2022-05-10T12:25:18.452Z',
                    'currency' => 'NGN',
                    'domain' => 'test',
                    'amount' => 500000,
                    'fully_deducted' => false,
                    'id' => 4828619,
                    'createdAt' => '2022-05-01T12:25:18.508Z',
                    'updatedAt' => '2022-05-01T12:25:18.508Z'
                ]
            ], 200),
        ]);

        $transaction = new Transaction(
            $paystack,
            $card->reference,
            Transaction::SUCCESS,
            Gateway::label('bank_card'),
            Money::NGN(10000),
            Money::NGN(150),
            $paystack->createCustomerFromPayload([
                'id' => 49367561,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'metadata' => null,
                'risk_action' => 'default',
                'international_format_phone' => null
            ]),
            $paystack->createProductsFromPayload([
                [
                    'name' => 'My Wema Card',
                    'price' => '10000',
                    'quantity' => '1',
                    'model_id' => (string)$card->id,
                    'model_type' => $card->getMorphClass()
                ]
            ])->withModel(),
            $paystack->createMetaFromPayload([
                [
                    'display_name' => 'Label',
                    'variable_name' => 'label',
                    'value' => 'bank_card'
                ],
                [
                    'display_name' => 'Products',
                    'variable_name' => 'products',
                    'value' => '1'
                ],
                [
                    'display_name' => 'Total',
                    'variable_name' => 'total',
                    'value' => '₦ 100.00'
                ],
                [
                    'display_name' => 'Fee',
                    'variable_name' => 'fee',
                    'value' => '₦ 101.50'
                ]
            ]),
            $paystack->createCardFromPayload([
                'authorization_code' => 'AUTH_wvo1t4opcg',
                'bin' => '408408',
                'last4' => '4081',
                'exp_month' => '12',
                'exp_year' => '2030',
                'channel' => 'card',
                'card_type' => 'visa ',
                'bank' => 'TEST BANK',
                'country_code' => 'NG',
                'brand' => 'visa',
                'reusable' => false,
                'signature' => 'SIG_L5lLp1d8z3GDXXq6KxSx',
                'account_name' => 'Olawale Ilesanmi',
                'receiver_bank_account_number' => null,
                'receiver_bank' => null
            ])
        );

        Gateway::label('bank_card')->success($transaction);

        $this->assertSame(0, BankCard::whereId($card->id)->count());
    }

    public function testLabelModelNotLoaded()
    {
        TestTime::freeze();
        $user = $this->newUser();
        /**
         * @var PaystackGatewayDriver
         */
        $paystack = Gateway::driver('paystack');

        $card = $user->bankCards()->create([
            'label' => 'My Wema Card',
            'reference' => Str::uuid(),
            'driver' => 'paystack',
        ]);

        Http::fake([
            config('services.paystack.url') . 'transaction/verify/' . $card->reference =>
            Http::response([
                'status' => true,
                'message' => 'Charge attempted',
                'data' => [
                    'id' => 1787520606,
                    'domain' => 'test',
                    'status' => 'success',
                    'reference' => $card->reference,
                    'amount' => 10150,
                    'message' => null,
                    'gateway_response' => 'Successful',
                    'helpdesk_link' => null,
                    'paid_at' => '2022-05-01T02:15:45.000Z',
                    'created_at' => '2022-05-01T02:15:34.000Z',
                    'channel' => 'card',
                    'currency' => 'NGN',
                    'ip_address' => '102.89.42.247',
                    'metadata' => [
                        'fee' => 150,
                        'label' => 'bank_card',
                        'products' => [
                            [
                                'name' => 'My Wema Card',
                                'price' => '10000',
                                'quantity' => '1',
                                'model_id' => (string)$card->id,
                                'model_type' => $card->getMorphClass()
                            ]
                        ],
                        'custom_fields' => [
                            [
                                'display_name' => 'Label',
                                'variable_name' => 'label',
                                'value' => 'bank_card'
                            ],
                            [
                                'display_name' => 'Products',
                                'variable_name' => 'products',
                                'value' => '1'
                            ],
                            [
                                'display_name' => 'Total',
                                'variable_name' => 'total',
                                'value' => '₦ 100.00'
                            ],
                            [
                                'display_name' => 'Fee',
                                'variable_name' => 'fee',
                                'value' => '₦ 101.50'
                            ]
                        ]
                    ],
                    'authorization' => [
                        'authorization_code' => 'AUTH_wvo1t4opcg',
                        'bin' => '408408',
                        'last4' => '4081',
                        'exp_month' => '12',
                        'exp_year' => '2030',
                        'channel' => 'card',
                        'card_type' => 'visa ',
                        'bank' => 'TEST BANK',
                        'country_code' => 'NG',
                        'brand' => 'visa',
                        'reusable' => true,
                        'signature' => 'SIG_L5lLp1d8z3GDXXq6KxSx',
                        'account_name' => null,
                        'receiver_bank_account_number' => null,
                        'receiver_bank' => null
                    ],
                    'customer' => [
                        'id' => 49367561,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'metadata' => null,
                        'risk_action' => 'default',
                        'international_format_phone' => null
                    ],
                ]
            ], 200),

            config('services.paystack.url') . 'refund' => Http::response([
                'status' => true,
                'message' => 'Refund has been queued for processing',
                'data' => [
                    'transaction' => [
                        'id' => 1494587920,
                        'domain' => 'test',
                        'reference' => 'f06e7f30-3367-40c3-ada0-87ad683c74c3',
                        'amount' => 200000,
                        'paid_at' => '2021-12-08T12:53:14.000Z',
                        'channel' => 'card',
                        'currency' => 'NGN',
                        'authorization' => [
                            'exp_month' => null,
                            'exp_year' => null,
                            'account_name' => null
                        ],
                        'customer' => [
                            'international_format_phone' => null
                        ],
                        'plan' => [],
                        'subaccount' => [
                            'currency' => null
                        ],
                        'split' => [],
                        'order_id' => null,
                        'paidAt' => '2021-12-08T12:53:14.000Z',
                        'pos_transaction_data' => null,
                        'source' => null,
                        'fees_breakdown' => null
                    ],
                    'integration' => 412711,
                    'deducted_amount' => 0,
                    'channel' => null,
                    'merchant_note' => 'Refund Bank card fee.',
                    'customer_note' => 'Refund for transaction ' . $card->reference,
                    'status' => 'pending',
                    'refunded_by' => 'themanetech@gmail.com',
                    'expected_at' => '2022-05-10T12:25:18.452Z',
                    'currency' => 'NGN',
                    'domain' => 'test',
                    'amount' => 500000,
                    'fully_deducted' => false,
                    'id' => 4828619,
                    'createdAt' => '2022-05-01T12:25:18.508Z',
                    'updatedAt' => '2022-05-01T12:25:18.508Z'
                ]
            ], 200),
        ]);

        $transaction = new Transaction(
            $paystack,
            $card->reference,
            Transaction::SUCCESS,
            Gateway::label('bank_card'),
            Money::NGN(10000),
            Money::NGN(150),
            $paystack->createCustomerFromPayload([
                'id' => 49367561,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'metadata' => null,
                'risk_action' => 'default',
                'international_format_phone' => null
            ]),
            $paystack->createProductsFromPayload([
                [
                    'name' => 'My Wema Card',
                    'price' => '10000',
                    'quantity' => '1',
                    'model_id' => (string)$card->id,
                    'model_type' => $card->getMorphClass()
                ]
            ]),
            $paystack->createMetaFromPayload([
                [
                    'display_name' => 'Label',
                    'variable_name' => 'label',
                    'value' => 'bank_card'
                ],
                [
                    'display_name' => 'Products',
                    'variable_name' => 'products',
                    'value' => '1'
                ],
                [
                    'display_name' => 'Total',
                    'variable_name' => 'total',
                    'value' => '₦ 100.00'
                ],
                [
                    'display_name' => 'Fee',
                    'variable_name' => 'fee',
                    'value' => '₦ 101.50'
                ]
            ]),
            $paystack->createCardFromPayload([
                'authorization_code' => 'AUTH_wvo1t4opcg',
                'bin' => '408408',
                'last4' => '4081',
                'exp_month' => '12',
                'exp_year' => '2030',
                'channel' => 'card',
                'card_type' => 'visa ',
                'bank' => 'TEST BANK',
                'country_code' => 'NG',
                'brand' => 'visa',
                'reusable' => true,
                'signature' => 'SIG_L5lLp1d8z3GDXXq6KxSx',
                'account_name' => 'Olawale Ilesanmi',
                'receiver_bank_account_number' => null,
                'receiver_bank' => null
            ])
        );

        Gateway::label('bank_card')->success($transaction);

        $card->refresh();
        $this->assertSame('AUTH_wvo1t4opcg', $card->token);
        $this->assertSame('408408******4081', $card->number);
        $this->assertSame('12', $card->expiry_month);
        $this->assertSame('2030', $card->expiry_year);
        $this->assertSame('visa', $card->brand);
        $this->assertSame('Olawale Ilesanmi', $card->name);
        $this->assertTrue(!is_null($card->refunded_at));
        $this->assertTrue(!is_null($card->paid_at));
    }

    public function testLabelNoModel()
    {
        TestTime::freeze();
        $user = $this->newUser();
        /**
         * @var PaystackGatewayDriver
         */
        $paystack = Gateway::driver('paystack');

        $card = $user->bankCards()->create([
            'label' => 'My Wema Card',
            'reference' => Str::uuid(),
            'driver' => 'paystack',
        ]);

        $transaction = new Transaction(
            $paystack,
            $card->reference,
            Transaction::SUCCESS,
            Gateway::label('bank_card'),
            Money::NGN(10000),
            Money::NGN(150),
            $paystack->createCustomerFromPayload([
                'id' => 49367561,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'metadata' => null,
                'risk_action' => 'default',
                'international_format_phone' => null
            ]),
            $paystack->createProductsFromPayload([
                [
                    'name' => 'My Wema Card',
                    'price' => '10000',
                    'quantity' => '1',
                ]
            ]),
            $paystack->createMetaFromPayload([
                [
                    'display_name' => 'Label',
                    'variable_name' => 'label',
                    'value' => 'bank_card'
                ],
                [
                    'display_name' => 'Products',
                    'variable_name' => 'products',
                    'value' => '1'
                ],
                [
                    'display_name' => 'Total',
                    'variable_name' => 'total',
                    'value' => '₦ 100.00'
                ],
                [
                    'display_name' => 'Fee',
                    'variable_name' => 'fee',
                    'value' => '₦ 101.50'
                ]
            ]),
            $paystack->createCardFromPayload([
                'authorization_code' => 'AUTH_wvo1t4opcg',
                'bin' => '408408',
                'last4' => '4081',
                'exp_month' => '12',
                'exp_year' => '2030',
                'channel' => 'card',
                'card_type' => 'visa ',
                'bank' => 'TEST BANK',
                'country_code' => 'NG',
                'brand' => 'visa',
                'reusable' => true,
                'signature' => 'SIG_L5lLp1d8z3GDXXq6KxSx',
                'account_name' => 'Olawale Ilesanmi',
                'receiver_bank_account_number' => null,
                'receiver_bank' => null
            ])
        );

        Gateway::label('bank_card')->success($transaction);

        $card->refresh();
        $this->assertNotSame('AUTH_wvo1t4opcg', $card->token);
        $this->assertNotSame('408408******4081', $card->number);
        $this->assertNotSame('12', $card->expiry_month);
        $this->assertNotSame('2030', $card->expiry_year);
        $this->assertNotSame('visa', $card->brand);
        $this->assertNotSame('Olawale Ilesanmi', $card->name);
        $this->assertTrue(is_null($card->refunded_at));
        $this->assertTrue(is_null($card->paid_at));
    }

    public function testLabelWrongModel()
    {
        TestTime::freeze();
        $user = $this->newUser();
        /**
         * @var PaystackGatewayDriver
         */
        $paystack = Gateway::driver('paystack');

        $card = $user->bankCards()->create([
            'label' => 'My Wema Card',
            'reference' => Str::uuid(),
            'driver' => 'paystack',
        ]);

        $transaction = new Transaction(
            $paystack,
            $card->reference,
            Transaction::SUCCESS,
            Gateway::label('bank_card'),
            Money::NGN(10000),
            Money::NGN(150),
            $paystack->createCustomerFromPayload([
                'id' => 49367561,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'metadata' => null,
                'risk_action' => 'default',
                'international_format_phone' => null
            ]),
            $paystack->createProductsFromPayload([
                [
                    'name' => 'My Wema Card',
                    'price' => '10000',
                    'quantity' => '1',
                    'model_id' => (string)$user->id,
                    'model_type' => $user->getMorphClass()
                ]
            ]),
            $paystack->createMetaFromPayload([
                [
                    'display_name' => 'Label',
                    'variable_name' => 'label',
                    'value' => 'bank_card'
                ],
                [
                    'display_name' => 'Products',
                    'variable_name' => 'products',
                    'value' => '1'
                ],
                [
                    'display_name' => 'Total',
                    'variable_name' => 'total',
                    'value' => '₦ 100.00'
                ],
                [
                    'display_name' => 'Fee',
                    'variable_name' => 'fee',
                    'value' => '₦ 101.50'
                ]
            ]),
            $paystack->createCardFromPayload([
                'authorization_code' => 'AUTH_wvo1t4opcg',
                'bin' => '408408',
                'last4' => '4081',
                'exp_month' => '12',
                'exp_year' => '2030',
                'channel' => 'card',
                'card_type' => 'visa ',
                'bank' => 'TEST BANK',
                'country_code' => 'NG',
                'brand' => 'visa',
                'reusable' => true,
                'signature' => 'SIG_L5lLp1d8z3GDXXq6KxSx',
                'account_name' => 'Olawale Ilesanmi',
                'receiver_bank_account_number' => null,
                'receiver_bank' => null
            ])
        );

        Gateway::label('bank_card')->success($transaction);

        $card->refresh();
        $this->assertNotSame('AUTH_wvo1t4opcg', $card->token);
        $this->assertNotSame('408408******4081', $card->number);
        $this->assertNotSame('12', $card->expiry_month);
        $this->assertNotSame('2030', $card->expiry_year);
        $this->assertNotSame('visa', $card->brand);
        $this->assertNotSame('Olawale Ilesanmi', $card->name);
        $this->assertTrue(is_null($card->refunded_at));
        $this->assertTrue(is_null($card->paid_at));
    }

    public function testBankCardResource()
    {
        $user = $this->loginUser();

        $card = $user->bankCards()->create([
            'label' => 'My Card',
            'reference' => Str::uuid(),
            'token' => 'AUTH_wvo1t4opcg',
            'name' => 'Olawale Ilesanmi',
            'number' => '408408******4081',
            'expiry_month' => '10',
            'expiry_year' => '2023',
            'brand' => 'visa',
            'driver' => 'paystack',
            'paid_at' => now(),
            'refunded_at' => now(),
        ]);

        $response = $this->get(route('api.v1.user.bank_cards.show', [
            'card' => $card->id
        ]));

        $response->assertJsonStructure([
            'id',
            'label',
            'name',
            'number',
            'expiry_month',
            'expiry_year',
            'brand',
        ]);
    }

    public function testDeleteBankCard()
    {
        $user = $this->loginUser();

        $card = $user->bankCards()->create([
            'label' => 'My Card',
            'reference' => Str::uuid(),
            'token' => 'AUTH_wvo1t4opcg',
            'name' => 'Olawale Ilesanmi',
            'number' => '408408******4081',
            'expiry_month' => '10',
            'expiry_year' => '2023',
            'brand' => 'visa',
            'driver' => 'paystack',
            'paid_at' => now(),
            'refunded_at' => now(),
        ]);

        $response = $this->delete(route('api.v1.user.bank_cards.delete', [
            'card' => $card->id
        ]));

        $response->assertStatus(200)->assertExactJson([
            'message' => 'Bank card deleted.'
        ]);

        $this->get(route('api.v1.user.bank_cards.show', [
            'card' => $card->id
        ]))->assertStatus(404)->assertExactJson([
            'message' => 'Resource not found.'
        ]);

        $this->assertTrue(is_null(BankCard::find($card->id)));
    }

    public function testIndex()
    {
        $user = $this->loginUser();

        foreach (range(1, 5) as $value) {
            TestTime::addDay();
            $user->bankCards()->create([
                'label' => 'My Card',
                'reference' => Str::uuid(),
                'token' => 'AUTH_wvo1t4opcg',
                'name' => 'Olawale Ilesanmi',
                'number' => '408408******4081',
                'expiry_month' => '10',
                'expiry_year' => '2023',
                'brand' => 'visa',
                'driver' => 'paystack',
                'paid_at' => now(),
                'refunded_at' => now(),
            ]);
        }

        $response = $this->get(route('api.v1.user.bank_cards.index'));
        $response->assertStatus(200)->assertJsonStructure([
            'bank_cards' => [
                0 => [
                    'id',
                    'label',
                    'name',
                    'number',
                    'expiry_month',
                    'expiry_year',
                    'brand',
                ],
                4 => [
                    'id',
                    'label',
                    'name',
                    'number',
                    'expiry_month',
                    'expiry_year',
                    'brand',
                ],
            ]
        ]);

        $this->assertCount(5, $response->json('bank_cards'));
    }
}
