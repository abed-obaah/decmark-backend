<?php

namespace Tests\Unit\Payment;

use App\Services\Payment\Methods\PaymentMethod;
use App\Services\Payment\Methods\WalletPaymentMethod;
use App\Services\Payment\Payment;
use App\Services\Payment\PaymentManager;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;
use Walletable\Money\Money;

class PaymentManagerTest extends TestCase
{
    use RefreshDatabase;

    public function testAddMethodClass()
    {
        $manager = new PaymentManager();

        $manager->method('wallet', WalletPaymentMethod::class);

        $this->assertInstanceOf(WalletPaymentMethod::class, $manager->method('wallet'));
    }

    public function testAddMethodClosure()
    {
        $manager = new PaymentManager();

        $manager->method('wallet', function () {
            return new WalletPaymentMethod($this->app['request']);
        });

        $this->assertInstanceOf(WalletPaymentMethod::class, $manager->method('wallet'));
    }

    public function testAddMethodClosureReturn()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(sprintf(
            'Closure resolver must return an instance of %s',
            PaymentMethod::class
        ));

        $manager = new PaymentManager();
        $manager->method('wallet', function () {
            return $this;
        });

        $manager->method('wallet');
    }

    public function testMethodNotFound()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('"wallet" not found as a payment method');
        $manager = new PaymentManager();
        $manager->method('wallet');
    }

    public function testAddWrongMethod()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(sprintf(
            'Payment method must implement [%s] interface',
            PaymentMethod::class
        ));
        $manager = new PaymentManager();
        $manager->method('wallet', PaymentManagerTest::class);
    }

    public function testWrongMethodResolver()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'A payment method can only be resolved through class name or closure'
        );
        $manager = new PaymentManager();
        $manager->method('wallet', []);
    }

    public function testPaymentClass()
    {
        $method = new WalletPaymentMethod(
            $resquest = $this->app['request']
        );
        $payment = new Payment(
            $method,
            'djfbhzdfjbhdbfhj',
            'djfbhzdfbfhj',
            'wallet',
            $money = Money::NGN(10000000),
            $fee = Money::NGN(10000)
        );

        $this->assertTrue($payment->check(Payment::SUCCESS));
        $this->assertSame('wallet', $payment->name());
        $this->assertSame('djfbhzdfjbhdbfhj', $payment->reference());
        $this->assertSame('djfbhzdfbfhj', $payment->secondaryReference());
        $this->assertSame($method, $payment->method());
        $this->assertSame($money, $payment->amount());
        $this->assertSame($fee, $payment->fee());

        $payment = new Payment(
            $method,
            'djfbhzdfjbhdbfhj',
            'djfbhzdfbfhj',
            'wallet',
            $money = Money::NGN(10000000),
            $fee = Money::NGN(10000),
            Payment::FAILED
        );

        $this->assertTrue($payment->check(Payment::FAILED));

        $payment = new Payment(
            $method,
            'djfbhzdfjbhdbfhj',
            'djfbhzdfbfhj',
            'wallet',
            $money = Money::NGN(10000000),
            $fee = Money::NGN(10000),
            Payment::ONGOING
        );

        $this->assertTrue($payment->check(Payment::ONGOING));

        $payment = new Payment(
            $method,
            'djfbhzdfjbhdbfhj',
            'djfbhzdfbfhj',
            'wallet',
            $money = Money::NGN(10000000),
            $fee = Money::NGN(10000),
        );

        $this->assertSame($payment, $payment->setResponse($response = response()->json(['working'])));
        $this->assertSame($response, $payment->toResponse($resquest));
    }
}
