<?php

namespace Tests\Feature\User;

use App\Enums\BeneficiaryTypeEnum;
use App\Services\Payment\Gateway\Gateway;
use App\Services\Payment\Gateway\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Spatie\TestTime\TestTime;
use Tests\TestCase;
use Walletable\Money\Money;

class SecondWalletTest extends TestCase
{
    use RefreshDatabase;

    public function testTransactionHistory()
    {
        $user = $this->loginUserWithBalance();
        $wallet = $user->wallets()->first();
        //$time = CarbonImmutable::now();
        TestTime::addMinutes(30);
        $wallet->credit(1000000, 'Second Test credit');
        TestTime::addMinutes(30);
        $wallet->credit(1000000, 'Third Test credit');

        $response = $this->get(route('api.v1.user.wallet.transaction.index'));

        $response->assertStatus(JsonResponse::HTTP_OK)
            ->assertJsonStructure([
                'transactions' => [
                    0 => [
                        'id',
                        'type',
                        'title',
                        'amount' => [
                            'amount',
                            'display',
                            'symbol',
                            'currency'
                        ],
                        'balance' => [
                            'amount',
                            'display',
                            'symbol',
                            'currency'
                        ],
                        'action',
                        'session',
                        'created_at',
                    ],
                    2 => [
                        'id',
                        'type',
                        'title',
                        'amount' => [
                            'amount',
                            'display',
                            'symbol',
                            'currency'
                        ],
                        'balance' => [
                            'amount',
                            'display',
                            'symbol',
                            'currency'
                        ],
                        'action',
                        'session',
                        'created_at',
                    ]
                ],
            ]);

        $this->assertCount(3, $response->json('transactions'));
    }

    public function testNoTransactionHistory()
    {
        $user1 = $this->loginUser();

        $response = $this->get(route('api.v1.user.wallet.transaction.index'));

        $response->assertStatus(JsonResponse::HTTP_OK)
            ->assertJsonStructure([
                'transactions'
            ]);

        $this->assertCount(0, $response->json('transactions'));
    }

    public function testTransactionLatest()
    {
        $user = $this->loginUserWithBalance();
        $wallet = $user->wallets()->first();
        TestTime::addMinutes(30);
        $wallet->credit(1000000, 'Second Test credit');
        TestTime::addMinutes(30);
        $wallet->credit(1000000, 'Third Test credit');

        $response = $this->get(route('api.v1.user.wallet.transaction.latest', [
            'count' => 2
        ]));

        $response->assertStatus(JsonResponse::HTTP_OK)
            ->assertJsonStructure([
                'transactions' => [
                    0 => [
                        'id',
                        'type',
                        'title',
                        'amount' => [
                            'amount',
                            'display',
                            'symbol',
                            'currency'
                        ],
                        'balance' => [
                            'amount',
                            'display',
                            'symbol',
                            'currency'
                        ],
                        'action',
                        'session',
                        'created_at',
                    ],
                    1 => [
                        'id',
                        'type',
                        'title',
                        'amount' => [
                            'amount',
                            'display',
                            'symbol',
                            'currency'
                        ],
                        'balance' => [
                            'amount',
                            'display',
                            'symbol',
                            'currency'
                        ],
                        'action',
                        'session',
                        'created_at',
                    ]
                ],
            ]);

        $this->assertCount(2, $response->json('transactions'));
    }


    public function testFundWithCardFailed()
    {
        $user = $this->loginUser();

        /**
         * @var \App\Models\Wallet
         */
        $wallet = $user->wallets()->first()->setRelation('walletable', $user);

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

        Http::fake([
            config('services.paystack.url') . 'transaction/charge_authorization' => Http::response([
                'status' => true,
                'message' => 'Charge attempted',
                'data' => [
                    'amount' => 10160000,
                    'currency' => 'NGN',
                    'transaction_date' => '2020-05-27T11:45:03.000Z',
                    'status' => 'failed',
                    'reference' => 'f06e7f30-3367-40c3-ada0-87ad683c74c3',
                    'domain' => 'test',
                    'metadata' => '',
                    'gateway_response' => 'Approved',
                    'message' => null,
                    'channel' => 'card',
                    'ip_address' => null,
                    'log' => null,
                    'fees' => 14500,
                    'metadata' => [
                        'fee' => 160000,
                        'label' => 'wallet',
                        'products' => [],
                        'custom_fields' => [
                            [
                                'display_name' => 'Label',
                                'variable_name' => 'label',
                                'value' => 'test'
                            ],
                            [
                                'display_name' => 'Products',
                                'variable_name' => 'products',
                                'value' => '0'
                            ],
                            [
                                'display_name' => 'Total',
                                'variable_name' => 'total',
                                'value' => '₦ 100,000.00'
                            ],
                            [
                                'display_name' => 'Fee',
                                'variable_name' => 'fee',
                                'value' => '₦ 1,600.00'
                            ],
                            [
                                'display_name' => 'Card Id',
                                'variable_name' => 'card_id',
                                'value' => $card->id
                            ],
                            [
                                'display_name' => 'Wallet Id',
                                'variable_name' => 'wallet_id',
                                'value' => $wallet->id
                            ]
                        ]
                    ],
                    'authorization' => [
                        'authorization_code' => 'AUTH_pmx3mgawyd',
                        'bin' => '408408',
                        'last4' => '4081',
                        'exp_month' => '12',
                        'exp_year' => '2020',
                        'channel' => 'card',
                        'card_type' => 'visa DEBIT',
                        'bank' => 'Test Bank',
                        'country_code' => 'NG',
                        'brand' => 'visa',
                        'reusable' => true,
                        'signature' => 'SIG_2Gvc6pNuzJmj4TCchXfp',
                        'account_name' => null
                    ],
                    'customer' => [
                        'id' => 49367561,
                        'first_name' => 'Ilesanmi',
                        'last_name' => 'Olawale',
                        'email' => 'olawale.tester@gmail.com',
                        'customer_code' => 'CUS_ip9mu14k5nupfj7',
                        'phone' => '2348164015051',
                        'metadata' => null,
                        'risk_action' => 'default',
                        'international_format_phone' => null
                    ],
                    'plan' => null,
                    'id' => 696105928
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

        $response = $this->post(route('api.v1.user.wallet.topup.card'), [
            'card' => (string)$card->id,
            'amount' => 10000000
        ]);

        $response->assertStatus(424)->assertExactJson([
            'message' => 'Charge not successful.'
        ]);
    }

    public function testFundWithCard()
    {
        $user = $this->loginUser();

        /**
         * @var \App\Models\Wallet
         */
        $wallet = $user->wallets()->first()->setRelation('walletable', $user);

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

        Http::fake([
            config('services.paystack.url') . 'transaction/charge_authorization' => Http::response([
                'status' => true,
                'message' => 'Charge attempted',
                'data' => [
                    'amount' => 10160000,
                    'currency' => 'NGN',
                    'transaction_date' => '2020-05-27T11:45:03.000Z',
                    'status' => 'success',
                    'reference' => 'f06e7f30-3367-40c3-ada0-87ad683c74c3',
                    'domain' => 'test',
                    'metadata' => '',
                    'gateway_response' => 'Approved',
                    'message' => null,
                    'channel' => 'card',
                    'ip_address' => null,
                    'log' => null,
                    'fees' => 14500,
                    'metadata' => [
                        'fee' => 160000,
                        'label' => 'wallet',
                        'products' => [],
                        'custom_fields' => [
                            [
                                'display_name' => 'Label',
                                'variable_name' => 'label',
                                'value' => 'test'
                            ],
                            [
                                'display_name' => 'Products',
                                'variable_name' => 'products',
                                'value' => '0'
                            ],
                            [
                                'display_name' => 'Total',
                                'variable_name' => 'total',
                                'value' => '₦ 100,000.00'
                            ],
                            [
                                'display_name' => 'Fee',
                                'variable_name' => 'fee',
                                'value' => '₦ 1,600.00'
                            ],
                            [
                                'display_name' => 'Card Id',
                                'variable_name' => 'card_id',
                                'value' => $card->id
                            ],
                            [
                                'display_name' => 'Wallet Id',
                                'variable_name' => 'wallet_id',
                                'value' => $wallet->id
                            ]
                        ]
                    ],
                    'authorization' => [
                        'authorization_code' => 'AUTH_pmx3mgawyd',
                        'bin' => '408408',
                        'last4' => '4081',
                        'exp_month' => '12',
                        'exp_year' => '2020',
                        'channel' => 'card',
                        'card_type' => 'visa DEBIT',
                        'bank' => 'Test Bank',
                        'country_code' => 'NG',
                        'brand' => 'visa',
                        'reusable' => true,
                        'signature' => 'SIG_2Gvc6pNuzJmj4TCchXfp',
                        'account_name' => null
                    ],
                    'customer' => [
                        'id' => 49367561,
                        'first_name' => 'Ilesanmi',
                        'last_name' => 'Olawale',
                        'email' => 'olawale.tester@gmail.com',
                        'customer_code' => 'CUS_ip9mu14k5nupfj7',
                        'phone' => '2348164015051',
                        'metadata' => null,
                        'risk_action' => 'default',
                        'international_format_phone' => null
                    ],
                    'plan' => null,
                    'id' => 696105928
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

        $response = $this->post(route('api.v1.user.wallet.topup.card'), [
            'card' => (string)$card->id,
            'amount' => 10000000
        ]);

        $response->assertStatus(202)->assertExactJson([
            'message' => 'Charge is processing.'
        ]);
    }

    public function testFundWithCardNotOwner()
    {
        $user = $this->loginUser();
        $user1 = $this->newUser();

        $card = $user1->bankCards()->create([
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

        $response = $this->post(route('api.v1.user.wallet.topup.card'), [
            'card' => (string)$card->id,
            'amount' => 10000000
        ]);

        $response->assertStatus(422);
    }

    public function testCardTopUplabel()
    {
        $user = $this->loginUser();

        /**
         * @var \App\Services\Payment\Gateway\Drivers\PaystackGatewayDriver
         */
        $paystack = Gateway::driver('paystack');

        /**
         * @var \App\Models\Wallet
         */
        $wallet = $user->wallets()->first()->setRelation('walletable', $user);

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

        $transaction = new Transaction(
            $paystack,
            $card->reference,
            Transaction::SUCCESS,
            Gateway::label('wallet'),
            Money::NGN(10000000),
            Money::NGN(160000),
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
            $paystack->createProductsFromPayload([]),
            $paystack->createMetaFromPayload([
                [
                    'display_name' => 'Label',
                    'variable_name' => 'label',
                    'value' => 'wallet'
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
                ],
                [
                    'display_name' => 'Card Id',
                    'variable_name' => 'card_id',
                    'value' => $card->id
                ],
                [
                    'display_name' => 'Wallet Id',
                    'variable_name' => 'wallet_id',
                    'value' => $wallet->id
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

        Gateway::label('wallet')->success($transaction);
        $wallet->refresh();

        $this->assertSame(10000000, $wallet->amount->getInt());

        /**
         * @var \App\Models\Transaction
         */
        $transaction = $wallet->transactions()->first();

        $this->assertSame('card_topup', $transaction->getRawOriginal('action'));
        $this->assertSame((string)$card->getKey(), $transaction->getRawOriginal('method_id'));
        $this->assertSame($card->getMorphClass(), $transaction->getRawOriginal('method_type'));
    }

    public function testCardTopUplabelWithUsedReference()
    {
        $user = $this->loginUser();

        /**
         * @var \App\Services\Payment\Gateway\Drivers\PaystackGatewayDriver
         */
        $paystack = Gateway::driver('paystack');

        /**
         * @var \App\Models\Wallet
         */
        $wallet = $user->wallets()->first()->setRelation('walletable', $user);

        $wallet->credit(
            Money::NGN(100000),
        )->getTransactions()->first()->forceFill([
            'reference' => 'f06e7f30-3367-40c3-ada0-87ad683c74c3'
        ])->save();

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

        $transaction = new Transaction(
            $paystack,
            'f06e7f30-3367-40c3-ada0-87ad683c74c3',
            Transaction::SUCCESS,
            Gateway::label('wallet'),
            Money::NGN(10000000),
            Money::NGN(160000),
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
            $paystack->createProductsFromPayload([]),
            $paystack->createMetaFromPayload([
                [
                    'display_name' => 'Label',
                    'variable_name' => 'label',
                    'value' => 'wallet'
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
                ],
                [
                    'display_name' => 'Card Id',
                    'variable_name' => 'card_id',
                    'value' => $card->id
                ],
                [
                    'display_name' => 'Wallet Id',
                    'variable_name' => 'wallet_id',
                    'value' => $wallet->id
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

        Gateway::label('wallet')->success($transaction);
        $wallet->refresh();

        $this->assertSame(100000, $wallet->amount->getInt());

        $this->assertSame(1, $wallet->transactions()->count());
    }
}
