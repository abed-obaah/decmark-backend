<?php

namespace Tests\Unit\Payment\Gateway;

use App\Services\Payment\Gateway\Card;
use App\Services\Payment\Gateway\Customer;
use App\Services\Payment\Gateway\Drivers\PaystackGatewayDriver;
use App\Services\Payment\Gateway\Gateway;
use App\Services\Payment\Gateway\Meta;
use App\Services\Payment\Gateway\Product;
use App\Services\Payment\Gateway\Products;
use App\Services\Payment\Gateway\Refund;
use App\Services\Payment\Gateway\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Walletable\Money\Money;

class PaystackGatewayDriverTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testCheckout()
    {
        $paystack = new PaystackGatewayDriver();

        $this->assertSame('paystack', $paystack->name());

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

            config('services.paystack.url') . 'customer/wale@wale.com' => Http::response([
                'status' => false,
                'message' => 'Customer retrieved',
                'data' => [
                    //
                ]
            ], 200),
        ]);

        $checkout = $paystack->checkout(
            'f06e7f30-3367-40c3-ada0-87ad683c74c3',
            new TestGatewayLabel(),
            new Customer(
                'wale@wale.com',
                Customer::TYPE_EMAIL,
                'Ilesanmi Olawale',
                'wale@wale.com',
                '08147386362'
            ),
            Money::NGN(500000),
            new Products(
                new Product(
                    'First product',
                    Money::NGN(50000),
                    2
                ),
                new Product(
                    'Second product',
                    Money::NGN(50000),
                    1
                )
            ),
            Meta::data([
                'env' => 'test'
            ]),
        );

        $this->assertSame('f06e7f30-3367-40c3-ada0-87ad683c74c3', $checkout->reference());
        $this->assertSame('wale@wale.com', $checkout->customer()->id());
        $this->assertTrue($checkout->customer()->is(Customer::TYPE_EMAIL));
        $this->assertSame('Ilesanmi Olawale', $checkout->customer()->name());
        $this->assertSame('wale@wale.com', $checkout->customer()->email());
        $this->assertSame(2, $checkout->products()->count());
        $this->assertSame('test', $checkout->meta()->get('env'));
        $this->assertSame('test', $checkout->meta()->get('label'));
        $this->assertSame(Money::NGN(17766)->display(), $checkout->meta()->get('fee'));
        $this->assertSame(2, $checkout->meta()->get('products'));
        $this->assertSame(Money::NGN(150000)->display(), $checkout->meta()->get('total'));

        $this->assertSame([
            [
                'display_name' => 'Env',
                'variable_name' => 'env',
                'value' => 'test'
            ],
            [
                'display_name' => 'Label',
                'variable_name' => 'label',
                'value' => 'test'
            ],
            [
                'display_name' => 'Products',
                'variable_name' => 'products',
                'value' => 2
            ],
            [
                'display_name' => 'Total',
                'variable_name' => 'total',
                'value' => Money::NGN(150000)->display(),
            ],
            [
                'display_name' => 'Fee',
                'variable_name' => 'fee',
                'value' => Money::NGN(17766)->display(),
            ]
        ], invade($paystack)->processMeta($checkout->meta()));
    }

    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testCardOnly()
    {
        $paystack = new PaystackGatewayDriver();

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

            config('services.paystack.url') . 'customer/wale@wale.com' => Http::response([
                'status' => true,
                'message' => 'Customer retrieved',
                'data' => [
                    //
                ]
            ], 200),
        ]);

        $checkout = $paystack->cardOnly(
            'f06e7f30-3367-40c3-ada0-87ad683c74c3',
            new TestGatewayLabel(),
            new Customer(
                'wale@wale.com',
                Customer::TYPE_EMAIL,
                'Ilesanmi Olawale',
                'wale@wale.com',
                '08147386362'
            ),
            Money::NGN(500000),
            new Products(
                new Product(
                    'First product',
                    Money::NGN(50000),
                    2
                ),
                new Product(
                    'Second product',
                    Money::NGN(50000),
                    1
                )
            ),
            Meta::data([
                'env' => 'test'
            ]),
        );

        $this->assertSame('f06e7f30-3367-40c3-ada0-87ad683c74c3', $checkout->reference());
        $this->assertSame('wale@wale.com', $checkout->customer()->id());
        $this->assertTrue($checkout->customer()->is(Customer::TYPE_EMAIL));
        $this->assertSame('Ilesanmi Olawale', $checkout->customer()->name());
        $this->assertSame('wale@wale.com', $checkout->customer()->email());
        $this->assertSame(2, $checkout->products()->count());
        $this->assertSame('test', $checkout->meta()->get('env'));
        $this->assertSame('test', $checkout->meta()->get('label'));
        $this->assertSame(Money::NGN(17766)->display(), $checkout->meta()->get('fee'));
        $this->assertSame(2, $checkout->meta()->get('products'));
        $this->assertSame(Money::NGN(150000)->display(), $checkout->meta()->get('total'));

        $this->assertSame([
            [
                'display_name' => 'Env',
                'variable_name' => 'env',
                'value' => 'test'
            ],
            [
                'display_name' => 'Label',
                'variable_name' => 'label',
                'value' => 'test'
            ],
            [
                'display_name' => 'Products',
                'variable_name' => 'products',
                'value' => 2
            ],
            [
                'display_name' => 'Total',
                'variable_name' => 'total',
                'value' => Money::NGN(150000)->display(),
            ],
            [
                'display_name' => 'Fee',
                'variable_name' => 'fee',
                'value' => Money::NGN(17766)->display(),
            ]
        ], invade($paystack)->processMeta($checkout->meta()));
    }

    public function testCreateCustomerIfNotExist()
    {
        Http::fake([
            config('services.paystack.url') . 'customer/wale@wale.com' => Http::response([
                'status' => false,
                'message' => 'Customer retrieved',
                'data' => [
                    //
                ]
            ], 404),
            config('services.paystack.url') . 'customer' => Http::response([
                'status' => true,
                'message' => 'Customer retrieved',
                'data' => [
                    //
                ]
            ], 200)
        ]);

        $paystack = invade(new PaystackGatewayDriver());

        $created = $paystack->createCustomerIfNotExist(new Customer(
            'wale@wale.com',
            Customer::TYPE_EMAIL,
            'Ilesanmi Olawale',
            'wale@wale.com',
            '08147386362'
        ));

        $this->assertTrue($created);
    }

    public function testCreateCustomerIfNotExistFalse()
    {
        Http::fake([
            config('services.paystack.url') . 'customer/wale@wale.com' => Http::response([
                'status' => true,
                'message' => 'Customer retrieved',
                'data' => [
                    //
                ]
            ], 200),
            config('services.paystack.url') . 'customer' => Http::response([
                'status' => true,
                'message' => 'Customer retrieved',
                'data' => [
                    //
                ]
            ], 200)
        ]);

        $paystack = invade(new PaystackGatewayDriver());

        $created = $paystack->createCustomerIfNotExist(new Customer(
            'wale@wale.com',
            Customer::TYPE_EMAIL,
            'Ilesanmi Olawale',
            'wale@wale.com',
            '08147386362'
        ));

        $this->assertNotTrue($created);
    }

    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testToken()
    {
        $paystack = new PaystackGatewayDriver();
        Gateway::label('test', TestGatewayLabel::class);

        Http::fake([
            config('services.paystack.url') . 'transaction/charge_authorization' => Http::response([
                'status' => true,
                'message' => 'Charge attempted',
                'data' => [
                    'amount' => 317766,
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
                        'fee' => 17766,
                        'label' => 'test',
                        'products' => [
                            [
                                'name' => 'First Product',
                                'price' => '200000',
                                'quantity' => '1'
                            ],
                            [
                                'name' => 'Second Product',
                                'price' => '50000',
                                'quantity' => '2'
                            ]
                        ],
                        'custom_fields' => [
                            [
                                'display_name' => 'Label',
                                'variable_name' => 'label',
                                'value' => 'test'
                            ],
                            [
                                'display_name' => 'Products',
                                'variable_name' => 'products',
                                'value' => '2'
                            ],
                            [
                                'display_name' => 'Total',
                                'variable_name' => 'total',
                                'value' => '₦ 3,000.00'
                            ],
                            [
                                'display_name' => 'Fee',
                                'variable_name' => 'fee',
                                'value' => '₦ 177.66'
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

            config('services.paystack.url') . 'customer/wale@wale.com' => Http::response([
                'status' => true,
                'message' => 'Customer retrieved',
                'data' => [
                    //
                ]
            ], 200),
        ]);

        $transaction = $paystack->token(
            'fbb3ee88-bb2b-49f0-96e1-7586351ca856',
            'f06e7f30-3367-40c3-ada0-87ad683c74c3',
            new TestGatewayLabel(),
            new Customer(
                'wale@wale.com',
                Customer::TYPE_EMAIL,
                'Ilesanmi Olawale',
                'wale@wale.com',
                '08147386362'
            ),
            Money::NGN(500000),
            new Products(
                new Product(
                    'First product',
                    Money::NGN(50000),
                    2
                ),
                new Product(
                    'Second product',
                    Money::NGN(50000),
                    1
                )
            ),
            Meta::data([
                'env' => 'test'
            ]),
        );

        $this->assertSame('f06e7f30-3367-40c3-ada0-87ad683c74c3', $transaction->reference());
        $this->assertTrue($transaction->hasCard());
        $this->assertInstanceOf(Card::class, $transaction->card());
        $this->assertSame('AUTH_pmx3mgawyd', $transaction->card()->authorization());

        $this->assertSame('olawale.tester@gmail.com', $transaction->customer()->id());
        $this->assertTrue($transaction->customer()->is(Customer::TYPE_EMAIL));
        $this->assertSame('Ilesanmi Olawale', $transaction->customer()->name());
        $this->assertSame('olawale.tester@gmail.com', $transaction->customer()->email());
        $this->assertSame('2348164015051', $transaction->customer()->phone());
        $this->assertSame(2, $transaction->products()->count());
        $this->assertSame('test', $transaction->meta()->get('label'));
        $this->assertSame('₦ 177.66', $transaction->meta()->get('fee'));
        $this->assertSame(2, $transaction->meta()->get('products'));
        $this->assertSame('₦ 3,000.00', $transaction->meta()->get('total'));

        $this->assertSame([
            [
                'display_name' => 'Label',
                'variable_name' => 'label',
                'value' => 'test'
            ],
            [
                'display_name' => 'Products',
                'variable_name' => 'products',
                'value' => 2
            ],
            [
                'display_name' => 'Total',
                'variable_name' => 'total',
                'value' => '₦ 3,000.00',
            ],
            [
                'display_name' => 'Fee',
                'variable_name' => 'fee',
                'value' => '₦ 177.66',
            ]
        ], invade($paystack)->processMeta($transaction->meta()));
    }

    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testRefund()
    {
        $paystack = new PaystackGatewayDriver();
        Gateway::label('test', TestGatewayLabel::class);

        Http::fake([
            config('services.paystack.url') . 'transaction/verify/f06e7f30-3367-40c3-ada0-87ad683c74c3' =>
            Http::response([
                'status' => true,
                'message' => 'Charge attempted',
                'data' => [
                    'id' => 1787520606,
                    'domain' => 'test',
                    'status' => 'success',
                    'reference' => 'f06e7f30-3367-40c3-ada0-87ad683c74c3',
                    'amount' => 317766,
                    'message' => null,
                    'gateway_response' => 'Successful',
                    'helpdesk_link' => null,
                    'paid_at' => '2022-05-01T02:15:45.000Z',
                    'created_at' => '2022-05-01T02:15:34.000Z',
                    'channel' => 'card',
                    'currency' => 'NGN',
                    'ip_address' => '102.89.42.247',
                    'metadata' => [
                        'fee' => 17766,
                        'label' => 'test',
                        'products' => [
                            [
                                'name' => 'First Product',
                                'price' => '200000',
                                'quantity' => '1'
                            ],
                            [
                                'name' => 'Second Product',
                                'price' => '50000',
                                'quantity' => '2'
                            ]
                        ],
                        'custom_fields' => [
                            [
                                'display_name' => 'Label',
                                'variable_name' => 'label',
                                'value' => 'test'
                            ],
                            [
                                'display_name' => 'Products',
                                'variable_name' => 'products',
                                'value' => '2'
                            ],
                            [
                                'display_name' => 'Total',
                                'variable_name' => 'total',
                                'value' => '₦ 3,000.00'
                            ],
                            [
                                'display_name' => 'Fee',
                                'variable_name' => 'fee',
                                'value' => '₦ 177.66'
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
                        'first_name' => 'Ilesanmi',
                        'last_name' => 'Olawale',
                        'email' => 'olawale.tester@gmail.com',
                        'customer_code' => 'CUS_ip9mu14k5nupfj7',
                        'phone' => null,
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
                    'merchant_note' => 'This is a test reason',
                    'customer_note' => 'Refund for transaction ewrywecrcrgwrwrwtrwtrwtrwtrwtr',
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

        $refund = $paystack->refund(
            'f06e7f30-3367-40c3-ada0-87ad683c74c3',
            Gateway::label('test'),
            'This is a test reason'
        );

        $this->assertSame('f06e7f30-3367-40c3-ada0-87ad683c74c3', $refund->reference());
        $this->assertSame('4828619', $refund->id());
        $this->assertSame(500000, $refund->amount()->getInt());
        $this->assertTrue($refund->transaction()->reference() === $refund->reference());
        $this->assertNotTrue($refund->transaction()->reference() === $refund->id());
        $this->assertSame('This is a test reason', $refund->reason());
        $this->assertTrue($refund->is(Refund::FULL));
        $this->assertTrue($refund->check(Refund::SUCCESS));


        $this->assertSame(300000, $refund->transaction()->amount()->getInt());
        $this->assertTrue($refund->transaction()->hasCard());
        $this->assertInstanceOf(Card::class, $refund->transaction()->card());
        $this->assertSame('AUTH_wvo1t4opcg', $refund->transaction()->card()->authorization());
        $this->assertSame('olawale.tester@gmail.com', $refund->transaction()->customer()->id());
        $this->assertSame('f06e7f30-3367-40c3-ada0-87ad683c74c3', $refund->transaction()->reference());
        $this->assertTrue($refund->transaction()->customer()->is(Customer::TYPE_EMAIL));
        $this->assertSame('Ilesanmi Olawale', $refund->transaction()->customer()->name());
        $this->assertSame('olawale.tester@gmail.com', $refund->transaction()->customer()->email());
        $this->assertSame(2, $refund->transaction()->products()->count());
        $this->assertSame('test', $refund->transaction()->meta()->get('label'));
        $this->assertSame('₦ 177.66', $refund->transaction()->meta()->get('fee'));
        $this->assertSame(2, $refund->transaction()->meta()->get('products'));
        $this->assertSame('₦ 3,000.00', $refund->transaction()->meta()->get('total'));
    }

    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testRefundPatial()
    {
        $paystack = new PaystackGatewayDriver();
        Gateway::label('test', TestGatewayLabel::class);

        Http::fake([
            config('services.paystack.url') . 'transaction/verify/f06e7f30-3367-40c3-ada0-87ad683c74c3' =>
            Http::response([
                'status' => true,
                'message' => 'Charge attempted',
                'data' => [
                    'id' => 1787520606,
                    'domain' => 'test',
                    'status' => 'success',
                    'reference' => 'f06e7f30-3367-40c3-ada0-87ad683c74c3',
                    'amount' => 317766,
                    'message' => null,
                    'gateway_response' => 'Successful',
                    'helpdesk_link' => null,
                    'paid_at' => '2022-05-01T02:15:45.000Z',
                    'created_at' => '2022-05-01T02:15:34.000Z',
                    'channel' => 'card',
                    'currency' => 'NGN',
                    'ip_address' => '102.89.42.247',
                    'metadata' => [
                        'fee' => 17766,
                        'label' => 'test',
                        'products' => [
                            [
                                'name' => 'First Product',
                                'price' => '200000',
                                'quantity' => '1'
                            ],
                            [
                                'name' => 'Second Product',
                                'price' => '50000',
                                'quantity' => '2'
                            ]
                        ],
                        'custom_fields' => [
                            [
                                'display_name' => 'Label',
                                'variable_name' => 'label',
                                'value' => 'test'
                            ],
                            [
                                'display_name' => 'Products',
                                'variable_name' => 'products',
                                'value' => '2'
                            ],
                            [
                                'display_name' => 'Total',
                                'variable_name' => 'total',
                                'value' => '₦ 3,000.00'
                            ],
                            [
                                'display_name' => 'Fee',
                                'variable_name' => 'fee',
                                'value' => '₦ 177.66'
                            ]
                        ]
                    ],
                    'log' => [
                        'start_time' => 1651371341,
                        'time_spent' => 5,
                        'attempts' => 1,
                        'errors' => 0,
                        'success' => true,
                        'mobile' => false,
                        'input' => [],
                        'history' => [
                            [
                                'type' => 'action',
                                'message' => 'Attempted to pay with card',
                                'time' => 4
                            ],
                            [
                                'type' => 'success',
                                'message' => 'Successfully paid with card',
                                'time' => 5
                            ]
                        ]
                    ],
                    'fees' => 3000,
                    'fees_split' => null,
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
                        'first_name' => 'Ilesanmi',
                        'last_name' => 'Olawale',
                        'email' => 'olawale.tester@gmail.com',
                        'customer_code' => 'CUS_ip9mu14k5nupfj7',
                        'phone' => null,
                        'metadata' => null,
                        'risk_action' => 'default',
                        'international_format_phone' => null
                    ],
                    'plan' => [],
                    'subaccount' => [],
                    'split' => [],
                    'order_id' => null,
                    'paidAt' => '2022-05-01T02:15:45.000Z',
                    'createdAt' => '2022-05-01T02:15:34.000Z',
                    'requested_amount' => 200000,
                    'pos_transaction_data' => null,
                    'source' => [
                        'type' => 'api',
                        'source' => 'merchant_api',
                        'identifier' => null
                    ],
                    'fees_breakdown' => null
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
                    'merchant_note' => 'This is a test reason',
                    'customer_note' => 'Refund for transaction ewrywecrcrgwrwrwtrwtrwtrwtrwtr',
                    'status' => 'pending',
                    'refunded_by' => 'themanetech@gmail.com',
                    'expected_at' => '2022-05-10T12:25:18.452Z',
                    'currency' => 'NGN',
                    'domain' => 'test',
                    'amount' => 100000,
                    'fully_deducted' => false,
                    'id' => 4828619,
                    'createdAt' => '2022-05-01T12:25:18.508Z',
                    'updatedAt' => '2022-05-01T12:25:18.508Z'
                ]
            ], 200),
        ]);

        $refund = $paystack->partialRefund(
            'f06e7f30-3367-40c3-ada0-87ad683c74c3',
            Gateway::label('test'),
            Money::NGN(100000),
            'This is a test reason'
        );

        $this->assertSame('f06e7f30-3367-40c3-ada0-87ad683c74c3', $refund->reference());
        $this->assertSame('4828619', $refund->id());
        $this->assertSame(100000, $refund->amount()->getInt());
        $this->assertTrue($refund->transaction()->reference() === $refund->reference());
        $this->assertNotTrue($refund->transaction()->reference() === $refund->id());
        $this->assertSame('This is a test reason', $refund->reason());
        $this->assertTrue($refund->is(Refund::PARTIAL));
        $this->assertTrue($refund->check(Refund::SUCCESS));

        $this->assertSame(300000, $refund->transaction()->amount()->getInt());
        $this->assertTrue($refund->transaction()->hasCard());
        $this->assertInstanceOf(Card::class, $refund->transaction()->card());
        $this->assertSame('AUTH_wvo1t4opcg', $refund->transaction()->card()->authorization());
        $this->assertSame('olawale.tester@gmail.com', $refund->transaction()->customer()->id());
        $this->assertSame('f06e7f30-3367-40c3-ada0-87ad683c74c3', $refund->transaction()->reference());
        $this->assertTrue($refund->transaction()->customer()->is(Customer::TYPE_EMAIL));
        $this->assertSame('Ilesanmi Olawale', $refund->transaction()->customer()->name());
        $this->assertSame('olawale.tester@gmail.com', $refund->transaction()->customer()->email());
        $this->assertSame(2, $refund->transaction()->products()->count());
        $this->assertSame('test', $refund->transaction()->meta()->get('label'));
        $this->assertSame('₦ 177.66', $refund->transaction()->meta()->get('fee'));
        $this->assertSame(2, $refund->transaction()->meta()->get('products'));
        $this->assertSame('₦ 3,000.00', $refund->transaction()->meta()->get('total'));
    }

    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testRefundFailed()
    {
        $paystack = new PaystackGatewayDriver();
        Gateway::label('test', TestGatewayLabel::class);

        Http::fake([
            config('services.paystack.url') . 'refund' => Http::response([
                'status' => false,
                'message' => 'Refund has been queued for processing',
                'data' => [
                    //
                ]
            ], 400),
        ]);

        $refund = $paystack->refund(
            'f06e7f30-3367-40c3-ada0-87ad683c74c3',
            Gateway::label('test'),
            'This is a test reason'
        );

        $this->assertTrue($refund->is(Refund::FULL));
        $this->assertTrue($refund->check(Refund::FAILED));
        $this->assertSame(0, $refund->amount()->getInt());
    }

    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testRefundPatialFailed()
    {
        $paystack = new PaystackGatewayDriver();
        Gateway::label('test', TestGatewayLabel::class);

        Http::fake([
            config('services.paystack.url') . 'refund' => Http::response([
                'status' => false,
                'message' => 'Refund has been queued for processing',
                'data' => [
                    //
                ]
            ], 400),
        ]);

        $refund = $paystack->partialRefund(
            'f06e7f30-3367-40c3-ada0-87ad683c74c3',
            Gateway::label('test'),
            Money::NGN(100000),
            'This is a test reason'
        );

        $this->assertTrue($refund->is(Refund::PARTIAL));
        $this->assertTrue($refund->check(Refund::FAILED));
        $this->assertSame(0, $refund->amount()->getInt());
    }

    public function testCreateFromPayload()
    {
        $paystack = new PaystackGatewayDriver();
        $customer = $paystack->createCustomerFromPayload([
            'id' => 49367561,
            'first_name' => 'Ilesanmi',
            'last_name' => 'Olawale',
            'email' => 'olawale.tester@gmail.com',
            'customer_code' => 'CUS_ip9mu14k5nupfj7',
            'phone' => '08147386362',
            'metadata' => null,
            'risk_action' => 'default',
            'international_format_phone' => null
        ]);

        $this->assertSame('olawale.tester@gmail.com', $customer->id());
        $this->assertTrue($customer->is(Customer::TYPE_EMAIL));
        $this->assertSame('Ilesanmi Olawale', $customer->name());
        $this->assertSame('olawale.tester@gmail.com', $customer->email());
        $this->assertSame('08147386362', $customer->phone());

        $meta = $paystack->createMetaFromPayload([
            [
                'display_name' => 'Label',
                'variable_name' => 'label',
                'value' => 'test'
            ],
            [
                'display_name' => 'Products',
                'variable_name' => 'products',
                'value' => '2'
            ],
            [
                'display_name' => 'Total',
                'variable_name' => 'total',
                'value' => '₦ 3,000.00'
            ],
            [
                'display_name' => 'Fee',
                'variable_name' => 'fee',
                'value' => '₦ 177.66'
            ]
        ]);

        $this->assertSame('test', $meta->get('label'));
        $this->assertSame(2, $meta->get('products'));
        $this->assertSame('₦ 3,000.00', $meta->get('total'));
        $this->assertSame('₦ 177.66', $meta->get('fee'));
        $this->assertCount(4, $meta);

        $products = $paystack->createProductsFromPayload([
            [
                'name' => 'First Product',
                'price' => '200000',
                'quantity' => '1'
            ],
            [
                'name' => 'Second Product',
                'price' => '50000',
                'quantity' => '2'
            ]
        ]);

        $this->assertSame(2, $products->count());
        $this->assertCount(2, $products);
        $this->assertSame(300000, $products->amount()->getInt());
        $this->assertTrue(is_array($products->get()));
        $this->assertSame([[
            'name' => 'First Product',
            'price' => 200000,
            'quantity' => 1
        ],[
            'name' => 'Second Product',
            'price' => 50000,
            'quantity' => 2
        ]], $products->toArray());
        $products->each(function ($value) {
            $this->assertTrue(in_array($value->name(), ['First Product', 'Second Product']));
            $this->assertTrue(in_array($value->price()->getInt(), [200000, 50000]));
            $this->assertTrue(in_array($value->quantity(), [2, 1]));
        });
        $products->collect()->each(function ($value) {
            $this->assertTrue(in_array($value->name(), ['First Product', 'Second Product']));
            $this->assertTrue(in_array($value->price()->getInt(), [200000, 50000]));
            $this->assertTrue(in_array($value->quantity(), [2, 1]));
        });

        $card = $paystack->createCardFromPayload([
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
        ]);

        $this->assertSame('408408******4081', $card->number());
        $this->assertSame('4081', $card->lastFour());
        $this->assertSame('408408', $card->bin());
        $this->assertSame('visa', $card->brand());
        $this->assertSame('12', $card->expiryMonth());
        $this->assertSame('2030', $card->expiryYear());
        $this->assertSame('12/30', $card->expiry());
        $this->assertSame('', $card->name());
        $this->assertSame('TEST BANK', $card->bank());
        $this->assertSame('NG', $card->countryCode());
        $this->assertSame('AUTH_wvo1t4opcg', $card->authorization());
        $this->assertTrue($card->authorized());
    }

    public function testProductModel()
    {
        $paystack = new PaystackGatewayDriver();
        $model = $this->newUser();

        $products = $paystack->createProductsFromPayload([
            [
                'name' => 'First Product',
                'price' => '200000',
                'quantity' => '1',
                'model_id' => (string)$model->getKey(),
                'model_type' => 'user'
            ]
        ]);

        $this->assertSame([[
            'name' => 'First Product',
            'price' => 200000,
            'quantity' => 1,
            'model_id' => (string)$model->getKey(),
            'model_type' => 'user'
        ]], $products->toArray());
        $products->each(function ($value) use ($model) {
            $this->assertSame($value->modelId(), (string)$model->getKey());
            $this->assertSame($value->modelType(), 'user');
        });
        $products->collect()->each(function ($value) use ($model) {
            $this->assertSame($value->modelId(), (string)$model->getKey());
            $this->assertSame($value->modelType(), 'user');
        });
    }

    public function testProductNoModel()
    {
        $paystack = new PaystackGatewayDriver();
        $model = $this->newUser();

        $products = $paystack->createProductsFromPayload([
            [
                'name' => 'First Product',
                'price' => '200000',
                'quantity' => '1'
            ]
        ]);

        $this->assertSame([[
            'name' => 'First Product',
            'price' => 200000,
            'quantity' => 1
        ]], $products->toArray());
        $products->each(function ($value) use ($model) {
            $this->assertNotSame($value->modelId(), (string)$model->getKey());
            $this->assertNotSame($value->modelType(), 'user');
        });
        $products->collect()->each(function ($value) use ($model) {
            $this->assertNotSame($value->modelId(), (string)$model->getKey());
            $this->assertNotSame($value->modelType(), 'user');
        });
    }

    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testTransaction()
    {
        $paystack = new PaystackGatewayDriver();
        Gateway::label('test', TestGatewayLabel::class);

        Http::fake([
            config('services.paystack.url') . 'transaction/verify/f06e7f30-3367-40c3-ada0-87ad683c74c3' =>
            Http::response([
                'status' => true,
                'message' => 'Verification successful',
                'data' => [
                    'id' => 1787520606,
                    'domain' => 'test',
                    'status' => 'success',
                    'reference' => 'f06e7f30-3367-40c3-ada0-87ad683c74c3',
                    'amount' => 317766,
                    'message' => null,
                    'gateway_response' => 'Successful',
                    'helpdesk_link' => null,
                    'paid_at' => '2022-05-01T02:15:45.000Z',
                    'created_at' => '2022-05-01T02:15:34.000Z',
                    'channel' => 'card',
                    'currency' => 'NGN',
                    'ip_address' => '102.89.42.247',
                    'metadata' => [
                        'fee' => 17766,
                        'label' => 'test',
                        'products' => [
                            [
                                'name' => 'First Product',
                                'price' => '200000',
                                'quantity' => '1'
                            ],
                            [
                                'name' => 'Second Product',
                                'price' => '50000',
                                'quantity' => '2'
                            ]
                        ],
                        'custom_fields' => [
                            [
                                'display_name' => 'Label',
                                'variable_name' => 'label',
                                'value' => 'test'
                            ],
                            [
                                'display_name' => 'Products',
                                'variable_name' => 'products',
                                'value' => '2'
                            ],
                            [
                                'display_name' => 'Total',
                                'variable_name' => 'total',
                                'value' => '₦ 3,000.00'
                            ],
                            [
                                'display_name' => 'Fee',
                                'variable_name' => 'fee',
                                'value' => '₦ 177.66'
                            ]
                        ]
                    ],
                    'log' => [
                        'start_time' => 1651371341,
                        'time_spent' => 5,
                        'attempts' => 1,
                        'errors' => 0,
                        'success' => true,
                        'mobile' => false,
                        'input' => [],
                        'history' => [
                            [
                                'type' => 'action',
                                'message' => 'Attempted to pay with card',
                                'time' => 4
                            ],
                            [
                                'type' => 'success',
                                'message' => 'Successfully paid with card',
                                'time' => 5
                            ]
                        ]
                    ],
                    'fees' => 3000,
                    'fees_split' => null,
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
                        'first_name' => 'Ilesanmi',
                        'last_name' => 'Olawale',
                        'email' => 'olawale.tester@gmail.com',
                        'customer_code' => 'CUS_ip9mu14k5nupfj7',
                        'phone' => null,
                        'metadata' => null,
                        'risk_action' => 'default',
                        'international_format_phone' => null
                    ],
                    'plan' => [],
                    'subaccount' => [],
                    'split' => [],
                    'order_id' => null,
                    'paidAt' => '2022-05-01T02:15:45.000Z',
                    'createdAt' => '2022-05-01T02:15:34.000Z',
                    'requested_amount' => 200000,
                    'pos_transaction_data' => null,
                    'source' => [
                        'type' => 'api',
                        'source' => 'merchant_api',
                        'identifier' => null
                    ],
                    'fees_breakdown' => null
                ]
            ], 200),
        ]);

        $transaction = $paystack->transaction('f06e7f30-3367-40c3-ada0-87ad683c74c3');

        $this->assertSame(300000, $transaction->amount()->getInt());
        $this->assertTrue($transaction->hasCard());
        $this->assertInstanceOf(Card::class, $transaction->card());
        $this->assertSame('AUTH_wvo1t4opcg', $transaction->card()->authorization());
        $this->assertSame('olawale.tester@gmail.com', $transaction->customer()->id());
        $this->assertSame('f06e7f30-3367-40c3-ada0-87ad683c74c3', $transaction->reference());
        $this->assertTrue($transaction->customer()->is(Customer::TYPE_EMAIL));
        $this->assertTrue($transaction->check(Transaction::SUCCESS));
        $this->assertSame('Ilesanmi Olawale', $transaction->customer()->name());
        $this->assertSame('olawale.tester@gmail.com', $transaction->customer()->email());
        $this->assertSame(2, $transaction->products()->count());
        $this->assertSame('test', $transaction->meta()->get('label'));
        $this->assertSame('₦ 177.66', $transaction->meta()->get('fee'));
        $this->assertSame(2, $transaction->meta()->get('products'));
        $this->assertSame('₦ 3,000.00', $transaction->meta()->get('total'));
    }

    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testTransactionNoProduct()
    {
        $paystack = new PaystackGatewayDriver();
        Gateway::label('test', TestGatewayLabel::class);

        Http::fake([
            config('services.paystack.url') . 'transaction/verify/f06e7f30-3367-40c3-ada0-87ad683c74c3' =>
            Http::response([
                'status' => true,
                'message' => 'Verification successful',
                'data' => [
                    'id' => 1787520606,
                    'domain' => 'test',
                    'status' => 'success',
                    'reference' => 'f06e7f30-3367-40c3-ada0-87ad683c74c3',
                    'amount' => 317766,
                    'message' => null,
                    'gateway_response' => 'Successful',
                    'helpdesk_link' => null,
                    'paid_at' => '2022-05-01T02:15:45.000Z',
                    'created_at' => '2022-05-01T02:15:34.000Z',
                    'channel' => 'card',
                    'currency' => 'NGN',
                    'ip_address' => '102.89.42.247',
                    'metadata' => [
                        'fee' => 17766,
                        'label' => 'test',
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
                                'value' => '₦ 3,000.00'
                            ],
                            [
                                'display_name' => 'Fee',
                                'variable_name' => 'fee',
                                'value' => '₦ 177.66'
                            ]
                        ]
                    ],
                    'log' => [
                        'start_time' => 1651371341,
                        'time_spent' => 5,
                        'attempts' => 1,
                        'errors' => 0,
                        'success' => true,
                        'mobile' => false,
                        'input' => [],
                        'history' => [
                            [
                                'type' => 'action',
                                'message' => 'Attempted to pay with card',
                                'time' => 4
                            ],
                            [
                                'type' => 'success',
                                'message' => 'Successfully paid with card',
                                'time' => 5
                            ]
                        ]
                    ],
                    'fees' => 3000,
                    'fees_split' => null,
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
                        'first_name' => 'Ilesanmi',
                        'last_name' => 'Olawale',
                        'email' => 'olawale.tester@gmail.com',
                        'customer_code' => 'CUS_ip9mu14k5nupfj7',
                        'phone' => null,
                        'metadata' => null,
                        'risk_action' => 'default',
                        'international_format_phone' => null
                    ],
                    'plan' => [],
                    'subaccount' => [],
                    'split' => [],
                    'order_id' => null,
                    'paidAt' => '2022-05-01T02:15:45.000Z',
                    'createdAt' => '2022-05-01T02:15:34.000Z',
                    'requested_amount' => 200000,
                    'pos_transaction_data' => null,
                    'source' => [
                        'type' => 'api',
                        'source' => 'merchant_api',
                        'identifier' => null
                    ],
                    'fees_breakdown' => null
                ]
            ], 200),
        ]);

        $transaction = $paystack->transaction('f06e7f30-3367-40c3-ada0-87ad683c74c3');

        $this->assertSame(300000, $transaction->amount()->getInt());
        $this->assertTrue($transaction->hasCard());
        $this->assertInstanceOf(Card::class, $transaction->card());
        $this->assertSame('AUTH_wvo1t4opcg', $transaction->card()->authorization());
        $this->assertSame('olawale.tester@gmail.com', $transaction->customer()->id());
        $this->assertSame('f06e7f30-3367-40c3-ada0-87ad683c74c3', $transaction->reference());
        $this->assertTrue($transaction->customer()->is(Customer::TYPE_EMAIL));
        $this->assertTrue($transaction->check(Transaction::SUCCESS));
        $this->assertSame('Ilesanmi Olawale', $transaction->customer()->name());
        $this->assertSame('olawale.tester@gmail.com', $transaction->customer()->email());
        $this->assertSame(0, $transaction->products()->count());
        $this->assertSame('test', $transaction->meta()->get('label'));
        $this->assertSame('₦ 177.66', $transaction->meta()->get('fee'));
        $this->assertSame(0, $transaction->meta()->get('products'));
        $this->assertSame('₦ 3,000.00', $transaction->meta()->get('total'));
    }

    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testTransactionFailed()
    {
        $paystack = new PaystackGatewayDriver();
        Gateway::label('test', TestGatewayLabel::class);

        Http::fake([
            config('services.paystack.url') . 'transaction/verify/f06e7f30-3367-40c3-ada0-87ad683c74c3' =>
            Http::response([
                'status' => true,
                'message' => 'Verification successful',
                'data' => [
                    'id' => 1787520606,
                    'domain' => 'test',
                    'status' => 'failed',
                    'reference' => 'f06e7f30-3367-40c3-ada0-87ad683c74c3',
                    'amount' => 317766,
                    'message' => null,
                    'channel' => 'card',
                    'currency' => 'NGN',
                    'metadata' => [
                        'fee' => 17766,
                        'label' => 'test',
                        'products' => [
                            [
                                'name' => 'First Product',
                                'price' => '200000',
                                'quantity' => '1'
                            ],
                            [
                                'name' => 'Second Product',
                                'price' => '50000',
                                'quantity' => '2'
                            ]
                        ],
                        'custom_fields' => [
                            [
                                'display_name' => 'Label',
                                'variable_name' => 'label',
                                'value' => 'test'
                            ],
                            [
                                'display_name' => 'Products',
                                'variable_name' => 'products',
                                'value' => '2'
                            ],
                            [
                                'display_name' => 'Total',
                                'variable_name' => 'total',
                                'value' => '₦ 3,000.00'
                            ],
                            [
                                'display_name' => 'Fee',
                                'variable_name' => 'fee',
                                'value' => '₦ 177.66'
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
                        'first_name' => 'Ilesanmi',
                        'last_name' => 'Olawale',
                        'email' => 'olawale.tester@gmail.com',
                        'customer_code' => 'CUS_ip9mu14k5nupfj7',
                        'phone' => null,
                        'metadata' => null,
                        'risk_action' => 'default',
                        'international_format_phone' => null
                    ]
                ]
            ], 200),
        ]);

        $transaction = $paystack->transaction('f06e7f30-3367-40c3-ada0-87ad683c74c3');

        $this->assertSame(300000, $transaction->amount()->getInt());
        $this->assertTrue($transaction->hasCard());
        $this->assertInstanceOf(Card::class, $transaction->card());
        $this->assertSame('AUTH_wvo1t4opcg', $transaction->card()->authorization());
        $this->assertSame('olawale.tester@gmail.com', $transaction->customer()->id());
        $this->assertSame('f06e7f30-3367-40c3-ada0-87ad683c74c3', $transaction->reference());
        $this->assertTrue($transaction->customer()->is(Customer::TYPE_EMAIL));
        $this->assertTrue($transaction->check(Transaction::FAILED));
        $this->assertSame('Ilesanmi Olawale', $transaction->customer()->name());
        $this->assertSame('olawale.tester@gmail.com', $transaction->customer()->email());
        $this->assertSame(2, $transaction->products()->count());
        $this->assertSame('test', $transaction->meta()->get('label'));
        $this->assertSame('₦ 177.66', $transaction->meta()->get('fee'));
        $this->assertSame(2, $transaction->meta()->get('products'));
        $this->assertSame('₦ 3,000.00', $transaction->meta()->get('total'));
    }

    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testTransactionNotFound()
    {
        $paystack = new PaystackGatewayDriver();
        Gateway::label('test', TestGatewayLabel::class);

        Http::fake([
            config('services.paystack.url') . 'transaction/verify/f06e7f30-3367-40c3-ada0-87ad683c74c3' =>
            Http::response([
                'status' => false,
                'message' => 'Transaction reference not found',
                'data' => [
                    //
                ]
            ], 400),
        ]);

        $transaction = $paystack->transaction('f06e7f30-3367-40c3-ada0-87ad683c74c3');

        $this->assertTrue(is_null($transaction));
    }

    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testTransactionReversed()
    {
        $paystack = new PaystackGatewayDriver();
        Gateway::label('test', TestGatewayLabel::class);

        Http::fake([
            config('services.paystack.url') . 'transaction/verify/f06e7f30-3367-40c3-ada0-87ad683c74c3' =>
            Http::response([
                'status' => true,
                'message' => 'Verification successful',
                'data' => [
                    'status' => 'reversed',
                    'reference' => 'f06e7f30-3367-40c3-ada0-87ad683c74c3',
                    'amount' => 317766,
                    'channel' => 'card',
                    'metadata' => [
                        'fee' => 17766,
                        'label' => 'test',
                        'products' => [
                            [
                                'name' => 'First Product',
                                'price' => '200000',
                                'quantity' => '1'
                            ],
                            [
                                'name' => 'Second Product',
                                'price' => '50000',
                                'quantity' => '2'
                            ]
                        ],
                        'custom_fields' => [
                            [
                                'display_name' => 'Label',
                                'variable_name' => 'label',
                                'value' => 'test'
                            ],
                            [
                                'display_name' => 'Products',
                                'variable_name' => 'products',
                                'value' => '2'
                            ],
                            [
                                'display_name' => 'Total',
                                'variable_name' => 'total',
                                'value' => '₦ 3,000.00'
                            ],
                            [
                                'display_name' => 'Fee',
                                'variable_name' => 'fee',
                                'value' => '₦ 177.66'
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
                        'first_name' => 'Ilesanmi',
                        'last_name' => 'Olawale',
                        'email' => 'olawale.tester@gmail.com',
                        'customer_code' => 'CUS_ip9mu14k5nupfj7',
                        'phone' => null,
                        'metadata' => null,
                        'risk_action' => 'default',
                        'international_format_phone' => null
                    ]
                ]
            ], 200),
        ]);

        $transaction = $paystack->transaction('f06e7f30-3367-40c3-ada0-87ad683c74c3');

        $this->assertTrue($transaction->check(Transaction::REVERSED));
    }

    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testTransactionQueued()
    {
        $paystack = new PaystackGatewayDriver();
        Gateway::label('test', TestGatewayLabel::class);

        Http::fake([
            config('services.paystack.url') . 'transaction/verify/f06e7f30-3367-40c3-ada0-87ad683c74c3' =>
            Http::response([
                'status' => true,
                'message' => 'Verification successful',
                'data' => [
                    'status' => 'queued',
                    'reference' => 'f06e7f30-3367-40c3-ada0-87ad683c74c3',
                    'amount' => 317766,
                    'channel' => 'card',
                    'metadata' => [
                        'fee' => 17766,
                        'label' => 'test',
                        'products' => [
                            [
                                'name' => 'First Product',
                                'price' => '200000',
                                'quantity' => '1'
                            ],
                            [
                                'name' => 'Second Product',
                                'price' => '50000',
                                'quantity' => '2'
                            ]
                        ],
                        'custom_fields' => [
                            [
                                'display_name' => 'Label',
                                'variable_name' => 'label',
                                'value' => 'test'
                            ],
                            [
                                'display_name' => 'Products',
                                'variable_name' => 'products',
                                'value' => '2'
                            ],
                            [
                                'display_name' => 'Total',
                                'variable_name' => 'total',
                                'value' => '₦ 3,000.00'
                            ],
                            [
                                'display_name' => 'Fee',
                                'variable_name' => 'fee',
                                'value' => '₦ 177.66'
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
                        'first_name' => 'Ilesanmi',
                        'last_name' => 'Olawale',
                        'email' => 'olawale.tester@gmail.com',
                        'customer_code' => 'CUS_ip9mu14k5nupfj7',
                        'phone' => null,
                        'metadata' => null,
                        'risk_action' => 'default',
                        'international_format_phone' => null
                    ],
                ]
            ], 200),
        ]);

        $transaction = $paystack->transaction('f06e7f30-3367-40c3-ada0-87ad683c74c3');

        $this->assertTrue($transaction->check(Transaction::PROCESSING));
    }

    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testTransactionUnknown()
    {
        $paystack = new PaystackGatewayDriver();
        Gateway::label('test', TestGatewayLabel::class);

        Http::fake([
            config('services.paystack.url') . 'transaction/verify/f06e7f30-3367-40c3-ada0-87ad683c74c3' =>
            Http::response([
                'status' => true,
                'message' => 'Verification successful',
                'data' => [
                    'status' => 'processing',
                    'reference' => 'f06e7f30-3367-40c3-ada0-87ad683c74c3',
                    'amount' => 317766,
                    'channel' => 'card',
                    'metadata' => [
                        'fee' => 17766,
                        'label' => 'test',
                        'products' => [
                            [
                                'name' => 'First Product',
                                'price' => '200000',
                                'quantity' => '1'
                            ],
                            [
                                'name' => 'Second Product',
                                'price' => '50000',
                                'quantity' => '2'
                            ]
                        ],
                        'custom_fields' => [
                            [
                                'display_name' => 'Label',
                                'variable_name' => 'label',
                                'value' => 'test'
                            ],
                            [
                                'display_name' => 'Products',
                                'variable_name' => 'products',
                                'value' => '2'
                            ],
                            [
                                'display_name' => 'Total',
                                'variable_name' => 'total',
                                'value' => '₦ 3,000.00'
                            ],
                            [
                                'display_name' => 'Fee',
                                'variable_name' => 'fee',
                                'value' => '₦ 177.66'
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
                        'first_name' => 'Ilesanmi',
                        'last_name' => 'Olawale',
                        'email' => 'olawale.tester@gmail.com',
                        'customer_code' => 'CUS_ip9mu14k5nupfj7',
                        'phone' => null,
                        'metadata' => null,
                        'risk_action' => 'default',
                        'international_format_phone' => null
                    ],
                ]
            ], 200),
        ]);

        $transaction = $paystack->transaction('f06e7f30-3367-40c3-ada0-87ad683c74c3');

        $this->assertTrue($transaction->check(Transaction::PROCESSING));
    }

    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testTransactionNoCard()
    {
        $paystack = new PaystackGatewayDriver();
        Gateway::label('test', TestGatewayLabel::class);

        Http::fake([
            config('services.paystack.url') . 'transaction/verify/f06e7f30-3367-40c3-ada0-87ad683c74c3' =>
            Http::response([
                'status' => true,
                'message' => 'Verification successful',
                'data' => [
                    'status' => 'reversed',
                    'reference' => 'f06e7f30-3367-40c3-ada0-87ad683c74c3',
                    'amount' => 317766,
                    'channel' => 'bank_transfer',
                    'metadata' => [
                        'fee' => 17766,
                        'label' => 'test',
                        'products' => [
                            [
                                'name' => 'First Product',
                                'price' => '200000',
                                'quantity' => '1'
                            ],
                            [
                                'name' => 'Second Product',
                                'price' => '50000',
                                'quantity' => '2'
                            ]
                        ],
                        'custom_fields' => [
                            [
                                'display_name' => 'Label',
                                'variable_name' => 'label',
                                'value' => 'test'
                            ],
                            [
                                'display_name' => 'Products',
                                'variable_name' => 'products',
                                'value' => '2'
                            ],
                            [
                                'display_name' => 'Total',
                                'variable_name' => 'total',
                                'value' => '₦ 3,000.00'
                            ],
                            [
                                'display_name' => 'Fee',
                                'variable_name' => 'fee',
                                'value' => '₦ 177.66'
                            ]
                        ]
                    ],
                    'customer' => [
                        'id' => 49367561,
                        'first_name' => 'Ilesanmi',
                        'last_name' => 'Olawale',
                        'email' => 'olawale.tester@gmail.com',
                        'customer_code' => 'CUS_ip9mu14k5nupfj7',
                        'phone' => null,
                        'metadata' => null,
                        'risk_action' => 'default',
                        'international_format_phone' => null
                    ],
                ]
            ], 200),
        ]);

        $transaction = $paystack->transaction('f06e7f30-3367-40c3-ada0-87ad683c74c3');

        $this->assertNotTrue($transaction->hasCard());
    }
}
