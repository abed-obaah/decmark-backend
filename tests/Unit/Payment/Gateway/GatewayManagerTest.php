<?php

namespace Tests\Unit\Payment\Gateway;

use App\Models\Airtime;
use App\Models\Wallet;
use App\Services\Payment\Gateway\Card;
use App\Services\Payment\Gateway\Checkout;
use App\Services\Payment\Gateway\Customer;
use App\Services\Payment\Gateway\Customization;
use App\Services\Payment\Gateway\Drivers\MockGatewayDriver;
use App\Services\Payment\Gateway\GatewayManager;
use App\Services\Payment\Gateway\Meta;
use App\Services\Payment\Gateway\Product;
use App\Services\Payment\Gateway\Products;
use App\Services\Payment\Gateway\Drivers\DriverInterface;
use App\Services\Payment\Gateway\LabelInterface;
use App\Services\Payment\Gateway\Refund;
use App\Services\Payment\Gateway\Transaction;
use Exception;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\TestCase;
use TypeError;
use Walletable\Money\Money;

class GatewayManagerTest extends TestCase
{
    use RefreshDatabase;

    public function testCustomer()
    {
        $customer = new Customer(
            'wale@wale.com',
            Customer::TYPE_EMAIL,
            'Ilesanmi Olawale',
            'wale@wale.com',
            '08147386362'
        );

        $this->assertSame('wale@wale.com', $customer->id());
        $this->assertTrue($customer->is(Customer::TYPE_EMAIL));
        $this->assertSame('Ilesanmi Olawale', $customer->name());
        $this->assertSame('wale@wale.com', $customer->email());
        $this->assertSame('08147386362', $customer->phone());

        $customer = new Customer(
            '08147386362',
            Customer::TYPE_PHONE,
            'Ilesanmi Olawale',
            'wale@wale.com',
            '08147386362'
        );

        $this->assertNotSame('wale@wale.com', $customer->id());
        $this->assertTrue($customer->is(Customer::TYPE_PHONE));
        $this->assertNotSame($customer->email(), $customer->id());
        $this->assertSame($customer->phone(), $customer->id());
    }

    public function testCard()
    {
        $driver = new MockGatewayDriver();

        $card = new Card(
            $driver,
            '5060666666666666853',
            'visa',
            '09',
            '2021',
            'Olawale Ilesanmi',
            'Zenith Bank',
            'NG',
            'erjbvjryhbjrwhbjhr'
        );

        $this->assertSame('5060666666666666853', $card->number());
        $this->assertSame('6853', $card->lastFour());
        $this->assertSame('506066', $card->bin());
        $this->assertSame('visa', $card->brand());
        $this->assertSame('09', $card->expiryMonth());
        $this->assertSame('2021', $card->expiryYear());
        $this->assertSame('09/21', $card->expiry());
        $this->assertSame('Olawale Ilesanmi', $card->name());
        $this->assertSame('Zenith Bank', $card->bank());
        $this->assertSame('NG', $card->countryCode());
        $this->assertSame('erjbvjryhbjrwhbjhr', $card->authorization());
        $this->assertTrue($card->authorized());


        $card = new Card(
            $driver,
            '5060666666666666853',
            'visa',
            '09',
            '21',
            'Olawale Ilesanmi',
            'Zenith Bank',
            'NG',
            ''
        );

        $this->assertSame('09/21', $card->expiry());
        $this->assertNotTrue($card->authorized());

        $card = new Card(
            $driver,
            '5060666666666666853',
            'visa',
            '09',
            '21',
            'Olawale Ilesanmi',
            'Zenith Bank',
            'NG'
        );

        $this->assertNotTrue($card->authorized());
    }

    public function testMeta()
    {
        $meta = Meta::data([
            'name' => 'Olawale',
            'gender' => 'male'
        ]);

        $this->assertSame(2, $meta->count());
        $this->assertCount(2, $meta);
        $meta->each(function ($value, $key) {
            $this->assertTrue(in_array($value, ['Olawale', 'male']));
            $this->assertTrue(in_array($key, ['name', 'gender']));
        });
        $this->assertSame('Olawale', $meta->get('name'));
        $this->assertSame('male', $meta->get('gender'));
        $this->assertSame([
            'name' => 'Olawale',
            'gender' => 'male'
        ], $meta->toArray());
        $this->assertSame([
            'name',
            'gender'
        ], $meta->keys());
        $this->assertSame([
            'Olawale',
            'male'
        ], $meta->values());
        $meta->collect()->each(function ($value, $key) {
            $this->assertTrue(in_array($value, ['Olawale', 'male']));
            $this->assertTrue(in_array($key, ['name', 'gender']));
        });

        $meta->add('age', 16)->add('work', 'student');

        $this->assertSame(4, $meta->count());
        $this->assertCount(4, $meta);
        $meta->each(function ($value, $key) {
            $this->assertTrue(in_array($value, ['Olawale', 'male', 16, 'student']));
            $this->assertTrue(in_array($key, ['name', 'gender', 'age', 'work']));
        });
        $this->assertSame('Olawale', $meta->get('name'));
        $this->assertSame('male', $meta->get('gender'));
        $this->assertSame(16, $meta->get('age'));
        $this->assertSame('student', $meta->get('work'));
        $this->assertSame([
            'name' => 'Olawale',
            'gender' => 'male',
            'age' => 16,
            'work' => 'student'
        ], $meta->toArray());
        $this->assertSame([
            'name',
            'gender',
            'age',
            'work'
        ], $meta->keys());
        $this->assertSame([
            'Olawale',
            'male',
            16,
            'student'
        ], $meta->values());
        $meta->collect()->each(function ($value, $key) {
            $this->assertTrue(in_array($value, ['Olawale', 'male', 16, 'student']));
            $this->assertTrue(in_array($key, ['name', 'gender', 'age', 'work']));
        });
    }

    public function testMetaNonStringKey()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Meta key at 2 can only be type string, integer given');

        Meta::data([
            'name' => 'Olawale',
            16 => 'male'
        ]);
    }

    public function testMetaAddNonStringKey()
    {
        $this->expectException(TypeError::class);

        $meta = Meta::data([
            'name' => 'Olawale'
        ]);

        /**
         * @var string
         */
        $array = [];

        $meta->add($array, 'male');
    }

    public function testMetaNonStringValue()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf(
            'Value at 2 can only be type string, %s or implements Class::__toString() magic method, %s given',
            Arrayable::class,
            gettype($this)
        ));

        Meta::data([
            'name' => 'Olawale',
            'age' => $this
        ]);
    }

    public function testMetaAddNonStringValue()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf(
            'Value can only be of type string, %s or implements Class::__toString() magic method, %s given',
            Arrayable::class,
            gettype($this)
        ));

        $meta = Meta::data([
            'name' => 'Olawale'
        ]);

        $meta->add('gender', $this);
    }

    public function testMetaToStringValue()
    {
        $meta = Meta::data([
            'name' => 'Olawale',
            'age' => new ToStringForTest()
        ]);
        $this->assertSame('This is the test string', $meta->get('age'));
    }

    public function testMetaAddToStringValue()
    {
        $meta = Meta::data([
            'name' => 'Olawale'
        ]);

        $meta->add('gender', new ToStringForTest());
        $this->assertSame('This is the test string', $meta->get('gender'));
    }

    public function testMetaArrayableValue()
    {
        $meta = Meta::data([
            'name' => 'Olawale',
            'age' => new ArrayableForTest()
        ]);
        $this->assertSame('["This is the test array"]', $meta->get('age'));
    }

    public function testMetaAddArrayableValue()
    {
        $meta = Meta::data([
            'name' => 'Olawale'
        ]);

        $meta->add('gender', new ArrayableForTest());
        $this->assertSame('["This is the test array"]', $meta->get('gender'));
    }

    public function testProduct()
    {
        $product = new Product(
            'The product name',
            Money::NGN(1000000),
            2
        );

        $this->assertSame('The product name', $product->name());
        $this->assertSame(1000000, $product->price()->getInt());
        $this->assertSame(2, $product->quantity());
        $this->assertSame([
            'name' => 'The product name',
            'price' => 1000000,
            'quantity' => 2
        ], $product->toArray());
        $this->assertNotTrue($product->modelLoaded());
        $this->assertNotTrue($product->hasModel());
    }

    public function testProductModel()
    {
        $model = $this->newUser();

        $product = new Product(
            'The product name',
            Money::NGN(1000000),
            2,
            $model
        );

        $this->assertSame('The product name', $product->name());
        $this->assertSame(1000000, $product->price()->getInt());
        $this->assertSame(2, $product->quantity());
        $this->assertTrue($product->modelLoaded());
        $this->assertTrue($product->hasModel());
        $this->assertSame([
            'name' => 'The product name',
            'price' => 1000000,
            'quantity' => 2,
            'model_id' => (string)$model->getKey(),
            'model_type' => 'user'
        ], $product->toArray());
        $this->assertSame((string)$model->getKey(), $product->modelId());
        $this->assertSame('user', $product->modelType());
    }

    public function testProductLazyModel()
    {
        $model = $this->newUser();

        $product = new Product(
            'The product name',
            Money::NGN(1000000),
            2
        );

        $product->setModelMorph($model->getKey(), $model->getMorphClass());

        $this->assertSame('The product name', $product->name());
        $this->assertSame(1000000, $product->price()->getInt());
        $this->assertSame(2, $product->quantity());
        $this->assertSame([
            'name' => 'The product name',
            'price' => 1000000,
            'quantity' => 2,
            'model_id' => (string)$model->getKey(),
            'model_type' => 'user'
        ], $product->toArray());
        $this->assertNotTrue($product->modelLoaded());
        $this->assertTrue($product->hasModel());
        $this->assertTrue(is_null($product->model()));

        $product->loadModel();
        $this->assertTrue($product->modelLoaded());
        $this->assertTrue($model !== $product->model());
    }

    public function testProducts()
    {
        $products = new Products(
            $first = new Product(
                'First product',
                Money::NGN(500000),
                2
            ),
            new Product(
                'Second product',
                Money::NGN(1000000),
                3
            )
        );

        $this->assertSame(2, $products->count());
        $this->assertCount(2, $products);
        $this->assertSame(4000000, $products->amount()->getInt());
        $this->assertSame($first, $products->first());
        $this->assertTrue(is_array($products->get()));
        $this->assertSame([[
            'name' => 'First product',
            'price' => 500000,
            'quantity' => 2
        ],[
            'name' => 'Second product',
            'price' => 1000000,
            'quantity' => 3
        ]], $products->toArray());
        $products->each(function ($value) {
            $this->assertTrue(in_array($value->name(), ['First product', 'Second product']));
            $this->assertTrue(in_array($value->price()->getInt(), [500000, 1000000]));
            $this->assertTrue(in_array($value->quantity(), [2, 3]));
        });
        $products->collect()->each(function ($value) {
            $this->assertTrue(in_array($value->name(), ['First product', 'Second product']));
            $this->assertTrue(in_array($value->price()->getInt(), [500000, 1000000]));
            $this->assertTrue(in_array($value->quantity(), [2, 3]));
        });
    }

    public function testProductsEagerLoading()
    {
        $user = $this->newUser();
        $wallet = $user->wallets()->first();

        $products = new Products(
            $first = new Product(
                'First product',
                Money::NGN(500000),
                2
            ),
            $second = new Product(
                'Second product',
                Money::NGN(1000000),
                3
            ),
            $third = new Product(
                'Third product',
                Money::NGN(1000000),
                3
            ),
            $fourth = new Product(
                'Fourth product',
                Money::NGN(1000000),
                3
            ),
            $fifth = new Product(
                'Fourth product',
                Money::NGN(1000000),
                3
            )
        );

        $first->setModelMorph($user->getKey(), $user->getMorphClass());
        $second->setModelMorph($wallet->getKey(), $wallet->getMorphClass());
        $third->setModelMorph('hgwvcshrjvc', 'unknown_morph');
        $fifth->setModelMorph($wallet->getKey(), Wallet::class);
        $products->withModel();

        $this->assertTrue($first->modelLoaded());
        $this->assertTrue($second->modelLoaded());
        $this->assertNotTrue($first->model() === $user);
        $this->assertNotTrue($second->model() === $wallet);
        $this->assertSame((string)$first->model()->getKey(), (string)$user->getKey());
        $this->assertSame((string)$second->model()->getKey(), (string)$wallet->getKey());
        $this->assertSame((string)$fifth->model()->getKey(), (string)$wallet->getKey());
        $this->assertNotTrue($fifth->model() === $wallet);
        $this->assertTrue($fifth->modelLoaded());

        $this->assertNotTrue($third->modelLoaded());
        $this->assertNotTrue($fourth->modelLoaded());
    }

    public function testCustomization()
    {
        $customization = new Customization(
            'Custom title',
            'https://domain.com/logo.png',
            'https://domain.com/success',
            'https://domain.com/cancel',
            'https://domain.com/failed'
        );

        $this->assertSame('Custom title', $customization->title());
        $this->assertSame('https://domain.com/logo.png', $customization->logo());
        $this->assertSame('https://domain.com/success', $customization->success());
        $this->assertSame('https://domain.com/cancel', $customization->cancel());
        $this->assertSame('https://domain.com/failed', $customization->failed());
    }

    public function testCustomizationDefault()
    {
        Customization::defaultTitle('Default title');
        Customization::defaultLogo('https://domain.com/default-logo.png');
        $customization = new Customization();

        $this->assertSame('Default title', $customization->title());
        $this->assertSame('https://domain.com/default-logo.png', $customization->logo());
    }

    public function testMockDriver()
    {
        $driver = new MockGatewayDriver();

        $this->assertSame('mock', $driver->name());
        $this->assertSame(0, $driver->charge(Money::NGN(100000))->getInt());
        $this->assertInstanceOf(Checkout::class, $checkout = $driver->checkout(
            'jfhbjrgbfvhj',
            new TestGatewayLabel(),
            $customer = new Customer(
                'wale@wale.com',
                Customer::TYPE_EMAIL,
                'Ilesanmi Olawale',
                'wale@wale.com',
                '08147386362'
            ),
            $amount = Money::NGN(150000),
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
        ));
        $this->assertSame('jfhbjrgbfvhj', $checkout->reference());
        $this->assertSame($customer->id(), $checkout->customer()->id());
        $this->assertTrue($checkout->customer()->is(Customer::TYPE_EMAIL));
        $this->assertSame($customer->name(), $checkout->customer()->name());
        $this->assertSame($customer->email(), $checkout->customer()->email());
        $this->assertSame($amount->getInt(), $checkout->amount()->getInt());
        $this->assertSame(0, $checkout->charge()->getInt());
        $this->assertSame(2, $checkout->products()->count());
        $this->assertSame('test', $checkout->meta()->get('env'));


        $driver = new MockGatewayDriver('mocked');
        $mockedCheckout = new Checkout(
            $driver,
            'yjsyrgvjdvbsjf',
            'https://gateway.com/pay/yjsyrgvjdvbsjf',
            Checkout::SUCCESS,
            $mockedAmount = Money::NGN(100000),
            Money::NGN(1000),
            $mockedCustomer = new Customer(
                'olawale@olawale.com',
                Customer::TYPE_EMAIL,
                'Ilesanmi Olawale Adedotun',
                'olawale@olawale.com',
                '08147386372'
            ),
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
                ),
                new Product(
                    'Third product',
                    Money::NGN(50000),
                    1
                )
            ),
            Meta::data([
                'env' => 'test_mocked'
            ])
        );

        $driver->mock($mockedCheckout);

        $this->assertSame('mocked', $driver->name());
        $this->assertSame(0, $driver->charge(Money::NGN(100000))->getInt());
        $this->assertInstanceOf(Checkout::class, $checkout = $driver->checkout(
            'jfhbjrgbfvhj',
            new TestGatewayLabel(),
            new Customer(
                'wale@wale.com',
                Customer::TYPE_EMAIL,
                'Ilesanmi Olawale',
                'wale@wale.com',
                '08147386362'
            ),
            Money::NGN(150000),
            new Products(
                new Product(
                    'First product',
                    Money::NGN(50000),
                    2
                ),
                new Product(
                    'First product',
                    Money::NGN(50000),
                    1
                )
            ),
            Meta::data([
                'env' => 'test'
            ]),
        ));
        $this->assertNotSame('jfhbjrgbfvhj', $checkout->reference());
        $this->assertNotSame($customer->id(), $checkout->customer()->id());
        $this->assertTrue($checkout->customer()->is(Customer::TYPE_EMAIL));
        $this->assertTrue($checkout->check(Checkout::SUCCESS));
        $this->assertSame(Checkout::SUCCESS, $checkout->status());
        $this->assertNotSame($customer->name(), $checkout->customer()->name());
        $this->assertNotSame($customer->email(), $checkout->customer()->email());
        $this->assertNotSame($amount->getInt(), $checkout->amount()->getInt());
        $this->assertNotSame(0, $checkout->charge()->getInt());
        $this->assertNotSame(2, $checkout->products()->count());
        $this->assertNotSame('test', $checkout->meta()->get('env'));


        $this->assertSame('yjsyrgvjdvbsjf', $checkout->reference());
        $this->assertTrue($mockedCheckout->check(Checkout::SUCCESS));
        $this->assertSame($mockedCustomer->id(), $checkout->customer()->id());
        $this->assertTrue($checkout->customer()->is(Customer::TYPE_EMAIL));
        $this->assertSame($mockedCustomer->name(), $checkout->customer()->name());
        $this->assertSame($mockedCustomer->email(), $checkout->customer()->email());
        $this->assertSame($mockedAmount->getInt(), $checkout->amount()->getInt());
        $this->assertSame(1000, $checkout->charge()->getInt());
        $this->assertSame(3, $checkout->products()->count());
        $this->assertSame('test_mocked', $checkout->meta()->get('env'));
    }

    public function testMockDriverCardOnly()
    {
        $driver = new MockGatewayDriver();

        $this->assertSame('mock', $driver->name());
        $this->assertSame(0, $driver->charge(Money::NGN(100000))->getInt());
        $this->assertInstanceOf(Checkout::class, $checkout = $driver->cardOnly(
            'jfhbjrgbfvhj',
            new TestGatewayLabel(),
            $customer = new Customer(
                'wale@wale.com',
                Customer::TYPE_EMAIL,
                'Ilesanmi Olawale',
                'wale@wale.com',
                '08147386362'
            ),
            $amount = Money::NGN(150000),
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
        ));
        $this->assertSame('jfhbjrgbfvhj', $checkout->reference());
        $this->assertSame($customer->id(), $checkout->customer()->id());
        $this->assertTrue($checkout->customer()->is(Customer::TYPE_EMAIL));
        $this->assertSame($customer->name(), $checkout->customer()->name());
        $this->assertSame($customer->email(), $checkout->customer()->email());
        $this->assertSame($amount->getInt(), $checkout->amount()->getInt());
        $this->assertSame(0, $checkout->charge()->getInt());
        $this->assertSame(2, $checkout->products()->count());
        $this->assertSame('test', $checkout->meta()->get('env'));


        $driver = new MockGatewayDriver('mocked');
        $mockedCheckout = new Checkout(
            $driver,
            'yjsyrgvjdvbsjf',
            'https://gateway.com/pay/yjsyrgvjdvbsjf',
            Checkout::SUCCESS,
            $mockedAmount = Money::NGN(100000),
            Money::NGN(1000),
            $mockedCustomer = new Customer(
                'olawale@olawale.com',
                Customer::TYPE_EMAIL,
                'Ilesanmi Olawale Adedotun',
                'olawale@olawale.com',
                '08147386372'
            ),
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
                ),
                new Product(
                    'Third product',
                    Money::NGN(50000),
                    1
                )
            ),
            Meta::data([
                'env' => 'test_mocked'
            ])
        );

        $driver->mock($mockedCheckout);

        $this->assertSame('mocked', $driver->name());
        $this->assertSame($driver, $mockedCheckout->driver());
        $this->assertSame(0, $driver->charge(Money::NGN(100000))->getInt());
        $this->assertInstanceOf(Checkout::class, $checkout = $driver->cardOnly(
            'jfhbjrgbfvhj',
            new TestGatewayLabel(),
            new Customer(
                'wale@wale.com',
                Customer::TYPE_EMAIL,
                'Ilesanmi Olawale',
                'wale@wale.com',
                '08147386362'
            ),
            Money::NGN(150000),
            new Products(
                new Product(
                    'First product',
                    Money::NGN(50000),
                    2
                ),
                new Product(
                    'First product',
                    Money::NGN(50000),
                    1
                )
            ),
            Meta::data([
                'env' => 'test'
            ]),
        ));
        $this->assertNotSame('jfhbjrgbfvhj', $checkout->reference());
        $this->assertNotSame($customer->id(), $checkout->customer()->id());
        $this->assertTrue($checkout->customer()->is(Customer::TYPE_EMAIL));
        $this->assertTrue($checkout->check(Checkout::SUCCESS));
        $this->assertSame(Checkout::SUCCESS, $checkout->status());
        $this->assertNotSame($customer->name(), $checkout->customer()->name());
        $this->assertNotSame($customer->email(), $checkout->customer()->email());
        $this->assertNotSame($amount->getInt(), $checkout->amount()->getInt());
        $this->assertNotSame(0, $checkout->charge()->getInt());
        $this->assertNotSame(2, $checkout->products()->count());
        $this->assertNotSame('test', $checkout->meta()->get('env'));


        $this->assertSame($driver, $checkout->driver());
        $this->assertSame('yjsyrgvjdvbsjf', $checkout->reference());
        $this->assertTrue($mockedCheckout->check(Checkout::SUCCESS));
        $this->assertSame($mockedCustomer->id(), $checkout->customer()->id());
        $this->assertTrue($checkout->customer()->is(Customer::TYPE_EMAIL));
        $this->assertSame($mockedCustomer->name(), $checkout->customer()->name());
        $this->assertSame($mockedCustomer->email(), $checkout->customer()->email());
        $this->assertSame($mockedAmount->getInt(), $checkout->amount()->getInt());
        $this->assertSame(1000, $checkout->charge()->getInt());
        $this->assertSame(3, $checkout->products()->count());
        $this->assertSame('test_mocked', $checkout->meta()->get('env'));
    }

    public function testMockDriverTransaction()
    {
        $transaction = new Transaction(
            $driver = new MockGatewayDriver(),
            'rhjbtgjhrtk',
            Transaction::SUCCESS,
            new TestGatewayLabel(),
            $amount = Money::NGN(100000),
            Money::NGN(1000),
            $customer = new Customer(
                'wale@wale.com',
                Customer::TYPE_EMAIL,
                'Ilesanmi Olawale',
                'wale@wale.com',
                '08147386362'
            ),
            new Products(
                new Product(
                    'First product',
                    Money::NGN(50000),
                    2
                ),
                new Product(
                    'First product',
                    Money::NGN(50000),
                    1
                )
            ),
            Meta::data([
                'env' => 'test'
            ])
        );

        $driver->mock(null, $transaction);

        $this->assertTrue($transaction === $driver->transaction('hhefvjbhfhjdbf'));
    }

    public function testMockDriverTransactionNull()
    {
        $driver = new MockGatewayDriver();

        $this->assertTrue(is_null($driver->transaction('hhefvjbhfhjdbf')));
    }

    public function testTransaction()
    {
        $transaction = new Transaction(
            $driver = new MockGatewayDriver(),
            'rhjbtgjhrtk',
            Transaction::SUCCESS,
            new TestGatewayLabel(),
            $amount = Money::NGN(100000),
            Money::NGN(1000),
            $customer = new Customer(
                'wale@wale.com',
                Customer::TYPE_EMAIL,
                'Ilesanmi Olawale',
                'wale@wale.com',
                '08147386362'
            ),
            new Products(
                new Product(
                    'First product',
                    Money::NGN(50000),
                    2
                ),
                new Product(
                    'First product',
                    Money::NGN(50000),
                    1
                )
            ),
            Meta::data([
                'env' => 'test'
            ])
        );

        $this->assertSame($driver, $transaction->driver());
        $this->assertSame($customer->id(), $transaction->customer()->id());
        $this->assertTrue($transaction->customer()->is(Customer::TYPE_EMAIL));
        $this->assertTrue($transaction->check(Transaction::SUCCESS));
        $this->assertNotTrue($transaction->hasCard());
        $this->assertSame(Transaction::SUCCESS, $transaction->status());
        $this->assertSame($customer->name(), $transaction->customer()->name());
        $this->assertSame($customer->email(), $transaction->customer()->email());
        $this->assertSame($amount->getInt(), $transaction->amount()->getInt());
        $this->assertSame(1000, $transaction->charge()->getInt());
        $this->assertSame(2, $transaction->products()->count());
        $this->assertSame('test', $transaction->meta()->get('env'));
        $this->assertSame('test', $transaction->label()->name());
    }

    public function testTransactionResuable()
    {
        $transaction = new Transaction(
            $driver = new MockGatewayDriver(),
            'rhjbtgjhrtk',
            Transaction::SUCCESS,
            new TestGatewayLabel(),
            Money::NGN(100000),
            Money::NGN(1000),
            $customer = new Customer(
                'wale@wale.com',
                Customer::TYPE_EMAIL,
                'Ilesanmi Olawale',
                'wale@wale.com',
                '08147386362'
            ),
            new Products(),
            Meta::data([
                'env' => 'test'
            ]),
            $card = new Card(
                $driver,
                '5060666666666666853',
                'visa',
                '09',
                '2021',
                'Olawale Ilesanmi',
                'Zenith Bank',
                'NG',
                'erjbvjryhbjrwhbjhr'
            )
        );

        $this->assertSame($card, $transaction->card());
        $this->assertTrue($transaction->hasCard());
    }

    public function testExecuteLabel()
    {
        $driver = new MockGatewayDriver();

        $success = new Transaction(
            $driver,
            'rhjbtgjhrtk',
            Transaction::SUCCESS,
            new TestGatewayLabel(),
            Money::NGN(100000),
            Money::NGN(1000),
            new Customer(
                'wale@wale.com',
                Customer::TYPE_EMAIL,
                'Ilesanmi Olawale',
                'wale@wale.com',
                '08147386362'
            ),
            new Products(),
            Meta::data()
        );
        $failed = new Transaction(
            $driver,
            'rhjbtgjhrtk',
            Transaction::FAILED,
            new TestGatewayLabel(),
            Money::NGN(100000),
            Money::NGN(1000),
            new Customer(
                'wale@wale.com',
                Customer::TYPE_EMAIL,
                'Ilesanmi Olawale',
                'wale@wale.com',
                '08147386362'
            ),
            new Products(),
            Meta::data()
        );
        $reversed = new Transaction(
            $driver,
            'rhjbtgjhrtk',
            Transaction::REVERSED,
            new TestGatewayLabel(),
            Money::NGN(100000),
            Money::NGN(1000),
            new Customer(
                'wale@wale.com',
                Customer::TYPE_EMAIL,
                'Ilesanmi Olawale',
                'wale@wale.com',
                '08147386362'
            ),
            new Products(),
            Meta::data()
        );
        $processing = new Transaction(
            $driver,
            'rhjbtgjhrtk',
            Transaction::PROCESSING,
            new TestGatewayLabel(),
            Money::NGN(100000),
            Money::NGN(1000),
            new Customer(
                'wale@wale.com',
                Customer::TYPE_EMAIL,
                'Ilesanmi Olawale',
                'wale@wale.com',
                '08147386362'
            ),
            new Products(),
            Meta::data()
        );

        $manager = new GatewayManager();
        $label = new TestGatewayLabel();

        $manager->processLabel($label, $success);
        $this->assertSame('success', $success->meta()->get('marked'));
        $this->assertSame('Testing', $label->displayName());
        $this->assertSame('Test For ' . $success->reference(), $label->displayName($success));

        $manager->processLabel($label, $failed);
        $this->assertSame('failed', $failed->meta()->get('marked'));
        $manager->processLabel($label, $reversed);
        $this->assertSame('reversed', $reversed->meta()->get('marked'));
        $manager->processLabel($label, $processing);
        $this->assertSame('processing', $processing->meta()->get('marked'));
    }

    public function testExecuteLabelRefund()
    {
        $driver = new MockGatewayDriver();
        $manager = new GatewayManager();
        $label = new TestGatewayLabel();

        $transaction = new Transaction(
            $driver,
            'rhjbtgjhrtk',
            Transaction::SUCCESS,
            $label,
            Money::NGN(100000),
            Money::NGN(1000),
            new Customer(
                'wale@wale.com',
                Customer::TYPE_EMAIL,
                'Ilesanmi Olawale',
                'wale@wale.com',
                '08147386362'
            ),
            new Products(),
            Meta::data()
        );

        $refund = new Refund(
            $driver,
            Str::uuid(),
            $transaction->reference(),
            Refund::FAILED,
            Refund::FULL,
            Money::NGN(100000),
            $transaction->label(),
            $transaction,
            'This is a mocked reason'
        );

        $manager->processRefund($label, $refund);
        $this->assertSame('refund', $refund->transaction()->meta()->get('marked'));
    }

    public function testExecuteLabelFailed()
    {
        $transaction = new Transaction(
            new MockGatewayDriver(),
            'rhjbtgjhrtk',
            Transaction::FAILED,
            new TestGatewayLabel(),
            $amount = Money::NGN(100000),
            Money::NGN(1000),
            $customer = new Customer(
                'wale@wale.com',
                Customer::TYPE_EMAIL,
                'Ilesanmi Olawale',
                'wale@wale.com',
                '08147386362'
            ),
            new Products(
                new Product(
                    'First product',
                    Money::NGN(50000),
                    2
                ),
                new Product(
                    'First product',
                    Money::NGN(50000),
                    1
                )
            ),
            Meta::data([
                'env' => 'test'
            ])
        );

        $manager = new GatewayManager();

        $manager->processLabel(new TestGatewayLabel(), $transaction);
        $this->assertSame('failed', $transaction->meta()->get('marked'));
    }

    public function testMockDriverToken()
    {
        $driver = new MockGatewayDriver();

        $this->assertSame('mock', $driver->name());
        $this->assertSame(0, $driver->charge(Money::NGN(100000))->getInt());
        $this->assertInstanceOf(Transaction::class, $transaction = $driver->token(
            'jfhbjrgbfvhj',
            'jfhbjrgbfvhj',
            new TestGatewayLabel(),
            $customer = new Customer(
                'wale@wale.com',
                Customer::TYPE_EMAIL,
                'Ilesanmi Olawale',
                'wale@wale.com',
                '08147386362'
            ),
            $amount = Money::NGN(150000),
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
        ));
        $this->assertSame('jfhbjrgbfvhj', $transaction->reference());
        $this->assertSame($customer->id(), $transaction->customer()->id());
        $this->assertTrue($transaction->customer()->is(Customer::TYPE_EMAIL));
        $this->assertSame($customer->name(), $transaction->customer()->name());
        $this->assertSame($customer->email(), $transaction->customer()->email());
        $this->assertSame($amount->getInt(), $transaction->amount()->getInt());
        $this->assertSame(0, $transaction->charge()->getInt());
        $this->assertSame(2, $transaction->products()->count());
        $this->assertSame('test', $transaction->meta()->get('env'));


        $driver = new MockGatewayDriver('mocked');
        $mockedTransaction = new Transaction(
            $driver,
            'yjsyrgvjdvbsjf',
            Transaction::SUCCESS,
            new TestGatewayLabel(),
            $mockedAmount = Money::NGN(100000),
            Money::NGN(1000),
            $mockedCustomer = new Customer(
                'olawale@olawale.com',
                Customer::TYPE_EMAIL,
                'Ilesanmi Olawale Adedotun',
                'olawale@olawale.com',
                '08147386372'
            ),
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
                ),
                new Product(
                    'Third product',
                    Money::NGN(50000),
                    1
                )
            ),
            Meta::data([
                'env' => 'test_mocked'
            ])
        );

        $driver->mock(null, $mockedTransaction);

        $this->assertSame('mocked', $driver->name());
        $this->assertSame($driver, $mockedTransaction->driver());
        $this->assertSame(0, $driver->charge(Money::NGN(100000))->getInt());
        $this->assertInstanceOf(Transaction::class, $transaction = $driver->token(
            'jfhbjrgbfvhj',
            'jfhbjrgbfvhj',
            new TestGatewayLabel(),
            new Customer(
                'wale@wale.com',
                Customer::TYPE_EMAIL,
                'Ilesanmi Olawale',
                'wale@wale.com',
                '08147386362'
            ),
            Money::NGN(150000),
            new Products(
                new Product(
                    'First product',
                    Money::NGN(50000),
                    2
                ),
                new Product(
                    'First product',
                    Money::NGN(50000),
                    1
                )
            ),
            Meta::data([
                'env' => 'test'
            ]),
        ));
        $this->assertNotSame('jfhbjrgbfvhj', $transaction->reference());
        $this->assertNotSame($customer->id(), $transaction->customer()->id());
        $this->assertTrue($transaction->customer()->is(Customer::TYPE_EMAIL));
        $this->assertTrue($transaction->check(Transaction::SUCCESS));
        $this->assertSame(Transaction::SUCCESS, $transaction->status());
        $this->assertNotSame($customer->name(), $transaction->customer()->name());
        $this->assertNotSame($customer->email(), $transaction->customer()->email());
        $this->assertNotSame($amount->getInt(), $transaction->amount()->getInt());
        $this->assertNotSame(0, $transaction->charge()->getInt());
        $this->assertNotSame(2, $transaction->products()->count());
        $this->assertNotSame('test', $transaction->meta()->get('env'));


        $this->assertSame($driver, $transaction->driver());
        $this->assertSame('yjsyrgvjdvbsjf', $transaction->reference());
        $this->assertTrue($mockedTransaction->check(Transaction::SUCCESS));
        $this->assertSame($mockedCustomer->id(), $transaction->customer()->id());
        $this->assertTrue($transaction->customer()->is(Customer::TYPE_EMAIL));
        $this->assertSame($mockedCustomer->name(), $transaction->customer()->name());
        $this->assertSame($mockedCustomer->email(), $transaction->customer()->email());
        $this->assertSame($mockedAmount->getInt(), $transaction->amount()->getInt());
        $this->assertSame(1000, $transaction->charge()->getInt());
        $this->assertSame(3, $transaction->products()->count());
        $this->assertSame('test_mocked', $transaction->meta()->get('env'));
    }

    public function testMockDriverRefund()
    {
        $driver = new MockGatewayDriver();

        $mockedTransaction = new Transaction(
            $driver,
            'yjsyrgvjdvbsjf',
            Transaction::SUCCESS,
            new TestGatewayLabel(),
            Money::NGN(100000),
            Money::NGN(1000),
            new Customer(
                'olawale@olawale.com',
                Customer::TYPE_EMAIL,
                'Ilesanmi Olawale Adedotun',
                'olawale@olawale.com',
                '08147386372'
            ),
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
                ),
                new Product(
                    'Third product',
                    Money::NGN(50000),
                    1
                )
            ),
            Meta::data([
                'env' => 'test_mocked'
            ])
        );

        $driver->mock(null, $mockedTransaction);

        $this->assertInstanceOf(Refund::class, $refund = $driver->refund(
            $mockedTransaction->reference(),
            $mockedTransaction->label(),
            'The reason is test.'
        ));
        $this->assertSame($mockedTransaction->amount()->getInt(), $refund->amount()->getInt());
        $this->assertTrue($mockedTransaction->reference() === $refund->reference());
        $this->assertNotTrue($mockedTransaction->reference() === $refund->id());
        $this->assertSame('The reason is test.', $refund->reason());
        $this->assertTrue($refund->is(Refund::FULL));
        $this->assertTrue($refund->check(Refund::SUCCESS));
        $this->assertSame($mockedTransaction, $refund->transaction());

        $driver = new MockGatewayDriver('mocked');

        $mockedRefund = new Refund(
            $driver,
            $id = Str::uuid(),
            $mockedTransaction->reference(),
            Refund::FAILED,
            Refund::FULL,
            Money::NGN(100000),
            $mockedTransaction->label(),
            $mockedTransaction,
            'This is a mocked reason'
        );

        $driver->mock(null, null, null, $mockedRefund);

        $this->assertSame('mocked', $mockedRefund->driver()->name());
        $this->assertSame(0, $driver->charge(Money::NGN(100000))->getInt());
        $this->assertInstanceOf(Refund::class, $refund = $driver->refund(
            $mockedTransaction->reference(),
            $mockedTransaction->label(),
            'The reason is test.'
        ));

        $this->assertSame('mocked', $refund->driver()->name());
        $this->assertSame(100000, $refund->amount()->getInt());
        $this->assertTrue($mockedTransaction->reference() === $refund->reference());
        $this->assertSame((string)$id, $refund->id());
        $this->assertNotTrue($mockedTransaction->reference() === $refund->id());
        $this->assertSame('This is a mocked reason', $refund->reason());
        $this->assertTrue($refund->is(Refund::FULL));
        $this->assertTrue($refund->check(Refund::FAILED));
        $this->assertSame($mockedTransaction, $refund->transaction());
    }

    public function testMockDriverRefundPartial()
    {
        $driver = new MockGatewayDriver();

        $mockedTransaction = new Transaction(
            $driver,
            'yjsyrgvjdvbsjf',
            Transaction::SUCCESS,
            new TestGatewayLabel(),
            Money::NGN(100000),
            Money::NGN(1000),
            new Customer(
                'olawale@olawale.com',
                Customer::TYPE_EMAIL,
                'Ilesanmi Olawale Adedotun',
                'olawale@olawale.com',
                '08147386372'
            ),
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
                ),
                new Product(
                    'Third product',
                    Money::NGN(50000),
                    1
                )
            ),
            Meta::data([
                'env' => 'test_mocked'
            ])
        );

        $driver->mock(null, $mockedTransaction);

        $this->assertInstanceOf(Refund::class, $refund = $driver->partialRefund(
            $mockedTransaction->reference(),
            $mockedTransaction->label(),
            Money::NGN(50000),
            'The reason is test.'
        ));
        $this->assertNotSame($mockedTransaction->amount()->getInt(), $refund->amount()->getInt());
        $this->assertSame(50000, $refund->amount()->getInt());
        $this->assertTrue($mockedTransaction->reference() === $refund->reference());
        $this->assertNotTrue($mockedTransaction->reference() === $refund->id());
        $this->assertSame('The reason is test.', $refund->reason());
        $this->assertTrue($refund->is(Refund::PARTIAL));
        $this->assertTrue($refund->check(Refund::SUCCESS));
        $this->assertSame($mockedTransaction, $refund->transaction());

        $driver = new MockGatewayDriver('mocked');

        $mockedRefund = new Refund(
            $driver,
            $id = Str::uuid(),
            $mockedTransaction->reference(),
            Refund::FAILED,
            Refund::PARTIAL,
            Money::NGN(100000),
            $mockedTransaction->label(),
            $mockedTransaction,
            'This is a mocked reason'
        );

        $driver->mock(null, null, null, $mockedRefund);

        $this->assertSame('mocked', $mockedRefund->driver()->name());
        $this->assertSame(0, $driver->charge(Money::NGN(100000))->getInt());
        $this->assertInstanceOf(Refund::class, $refund = $driver->partialRefund(
            $mockedTransaction->reference(),
            $mockedTransaction->label(),
            Money::NGN(50000),
            'The reason is test.'
        ));

        $this->assertSame('mocked', $refund->driver()->name());
        $this->assertSame(100000, $refund->amount()->getInt());
        $this->assertTrue($mockedTransaction->reference() === $refund->reference());
        $this->assertSame((string)$id, $refund->id());
        $this->assertNotTrue($mockedTransaction->reference() === $refund->id());
        $this->assertSame('This is a mocked reason', $refund->reason());
        $this->assertTrue($refund->is(Refund::PARTIAL));
        $this->assertTrue($refund->check(Refund::FAILED));
        $this->assertSame($mockedTransaction, $refund->transaction());
    }

    public function testRefundTransaction()
    {
        $driver = new MockGatewayDriver();

        $transaction = new Transaction(
            $driver,
            'yjsyrgvjdvbsjf',
            Transaction::SUCCESS,
            new TestGatewayLabel(),
            Money::NGN(100000),
            Money::NGN(1000),
            new Customer(
                'olawale@olawale.com',
                Customer::TYPE_EMAIL,
                'Ilesanmi Olawale Adedotun',
                'olawale@olawale.com',
                '08147386372'
            ),
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
                ),
                new Product(
                    'Third product',
                    Money::NGN(50000),
                    1
                )
            ),
            Meta::data([
                'env' => 'test_mocked'
            ])
        );

        $driver->mock(null, $transaction);

        $refund = $transaction->refund(
            'The reason is test.'
        );

        $this->assertSame(100000, $refund->amount()->getInt());
        $this->assertTrue($transaction->reference() === $refund->reference());
        $this->assertNotTrue($transaction->reference() === $refund->id());
        $this->assertSame('The reason is test.', $refund->reason());
        $this->assertTrue($refund->is(Refund::FULL));
        $this->assertTrue($refund->check(Refund::SUCCESS));
        $this->assertSame($transaction, $refund->transaction());
    }

    public function testRefundTransactionPartial()
    {
        $driver = new MockGatewayDriver();

        $transaction = new Transaction(
            $driver,
            'yjsyrgvjdvbsjf',
            Transaction::SUCCESS,
            new TestGatewayLabel(),
            Money::NGN(100000),
            Money::NGN(1000),
            new Customer(
                'olawale@olawale.com',
                Customer::TYPE_EMAIL,
                'Ilesanmi Olawale Adedotun',
                'olawale@olawale.com',
                '08147386372'
            ),
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
                ),
                new Product(
                    'Third product',
                    Money::NGN(50000),
                    1
                )
            ),
            Meta::data([
                'env' => 'test_mocked'
            ])
        );

        $driver->mock(null, $transaction);

        $refund = $transaction->partialRefund(
            Money::NGN(50000),
            'The reason is test.'
        );

        $this->assertSame(50000, $refund->amount()->getInt());
        $this->assertTrue($transaction->reference() === $refund->reference());
        $this->assertNotTrue($transaction->reference() === $refund->id());
        $this->assertSame('The reason is test.', $refund->reason());
        $this->assertTrue($refund->is(Refund::PARTIAL));
        $this->assertTrue($refund->check(Refund::SUCCESS));
        $this->assertSame($transaction, $refund->transaction());
    }

    public function testAddDriverClass()
    {
        $manager = new GatewayManager();

        $manager->driver('mock', MockGatewayDriver::class);

        $this->assertInstanceOf(MockGatewayDriver::class, $manager->driver('mock'));
    }

    public function testAddDriverClosure()
    {
        $manager = new GatewayManager();

        $manager->driver('mock', function () {
            return new MockGatewayDriver();
        });

        $this->assertInstanceOf(MockGatewayDriver::class, $manager->driver('mock'));
    }

    public function testAddDriverClosureReturn()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(sprintf(
            'Closure resolver must return an instance of %s',
            DriverInterface::class
        ));

        $manager = new GatewayManager();
        $manager->driver('mock', function () {
            return $this;
        });

        $manager->driver('mock');
    }

    public function testDriverNotFound()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('"mock" not found as a gateway driver');
        $manager = new GatewayManager();
        $manager->driver('mock');
    }

    public function testAddWrongDriver()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(sprintf(
            'Gateway driver must implement [%s] interface',
            DriverInterface::class
        ));
        $manager = new GatewayManager();
        $manager->driver('mock', GatewayManagerTest::class);
    }

    public function testWrongDriverResolver()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'A gateway driver can only be resolved through class name or closure'
        );
        $manager = new GatewayManager();
        $manager->driver('mock', []);
    }

    public function testAddLabelClass()
    {
        $manager = new GatewayManager();

        $manager->label('test', TestGatewayLabel::class);

        $this->assertInstanceOf(TestGatewayLabel::class, $manager->label('test'));
    }

    public function testAddLabelClosure()
    {
        $manager = new GatewayManager();

        $manager->label('test', function () {
            return new TestGatewayLabel();
        });

        $this->assertInstanceOf(TestGatewayLabel::class, $manager->label('test'));
    }

    public function testAddLabelClosureReturn()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(sprintf(
            'Closure resolver must return an instance of %s',
            LabelInterface::class
        ));

        $manager = new GatewayManager();
        $manager->label('test', function () {
            return $this;
        });

        $manager->label('test');
    }

    public function testLabelNotFound()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('"test" not found as a gateway label');
        $manager = new GatewayManager();
        $manager->label('test');
    }

    public function testAddWrongLabel()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(sprintf(
            'Gateway label must implement [%s] interface',
            LabelInterface::class
        ));
        $manager = new GatewayManager();
        $manager->label('test', GatewayManagerTest::class);
    }

    public function testWrongLabelResolver()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'A gateway label can only be resolved through class name or closure'
        );
        $manager = new GatewayManager();
        $manager->label('test', []);
    }
}
