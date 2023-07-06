<?php

namespace App\Services\Payment\Gateway\Drivers;

use App\Services\Payment\Gateway\Card;
use App\Services\Payment\Gateway\Checkout;
use App\Services\Payment\Gateway\Customer;
use App\Services\Payment\Gateway\Customization;
use App\Services\Payment\Gateway\Gateway;
use App\Services\Payment\Gateway\LabelInterface;
use App\Services\Payment\Gateway\Meta;
use App\Services\Payment\Gateway\Product;
use App\Services\Payment\Gateway\Products;
use App\Services\Payment\Gateway\Refund;
use App\Services\Payment\Gateway\Transaction;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Walletable\Money\Money;

class PaystackGatewayDriver implements DriverInterface
{
    /**
     * @inheritDoc
     */
    public function name(): string
    {
        return 'paystack';
    }

    /**
     * @inheritDoc
     */
    public function transaction(string $reference): ?Transaction
    {
        $transaction = $this->http()->get('transaction/verify/' . $reference);

        if ($transaction->successful()) {
            return $this->makeTransaction(
                $transaction->json('data.reference'),
                $transaction->json('data.amount'),
                (int)$transaction->json('data.metadata.fee'),
                $transaction->json('data.status'),
                $transaction->json('data.metadata.label'),
                $transaction->json('data.customer'),
                $transaction->json('data.metadata.products') ?? [],
                $transaction->json('data.metadata.custom_fields'),
                $transaction->json('data.channel') === 'card' ?
                    $transaction->json('data.authorization') : []
            );
        }

        return null;
    }

    /**
     * Make new transaction from data
     *
     * @param string $reference
     * @param integer $amount
     * @param integer $fee
     * @param string $status
     * @param string $label
     * @param array $customer
     * @param array $products
     * @param array $meta
     * @param ?array $card
     * @return Transaction
     */
    protected function makeTransaction(
        string $reference,
        int $amount,
        int $fee,
        string $status,
        string $label,
        array $customer,
        array $products,
        array $meta,
        ?array $card
    ) {
        $charge = Money::NGN($fee);
        $amount = Money::NGN($amount)->subtract($charge);

        return new Transaction(
            $this,
            $reference,
            match ($status) {
                'success' => Transaction::SUCCESS,
                'failed' => Transaction::FAILED,
                'reversed' => Transaction::REVERSED,
                default => Transaction::PROCESSING
            },
            Gateway::label($label),
            $amount,
            $charge,
            $this->createCustomerFromPayload($customer),
            $this->createProductsFromPayload($products),
            $this->createMetaFromPayload($meta),
            !empty($card) ? $this->createCardFromPayload($card) : null
        );
    }

    /**
     * @inheritDoc
     */
    public function checkout(
        string $reference,
        LabelInterface $label,
        Customer $customer,
        Money $amount,
        Products $products,
        Meta $meta,
        Customization $customization = null
    ): Checkout {
        return $this->createCheckout(
            $reference,
            $label,
            $customer,
            $amount,
            $products,
            $meta,
            $customization,
            [
                'card',
                'bank',
                'ussd',
                'qr',
                'mobile_money',
                'bank_transfer'
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function cardOnly(
        string $reference,
        LabelInterface $label,
        Customer $customer,
        Money $amount,
        Products $products,
        Meta $meta,
        Customization $customization = null
    ): Checkout {
        return $this->createCheckout(
            $reference,
            $label,
            $customer,
            $amount,
            $products,
            $meta,
            $customization,
            ['card'],
            true
        );
    }

    /**
     * Create checkout
     *
     * @param string $reference
     * @param LabelInterface $label
     * @param Customer $customer
     * @param Money $amount
     * @param Products $products
     * @param Meta $meta
     * @param Customization $customization
     * @param array $channels
     * @param bool $recurring = false
     * @return Checkout
     */
    protected function createCheckout(
        string $reference,
        LabelInterface $label,
        Customer $customer,
        Money $amount,
        Products $products,
        Meta $meta,
        Customization $customization = null,
        array $channels,
        bool $recurring = false
    ): Checkout {
        $customization = $customization ?? new Customization();

        $this->createCustomerIfNotExist($customer);
        $charge = $this->charge($amount);

        $meta->add('label', $label->name());

        if (count($products) > 0) {
            $meta->add('products', $products->count());
            $meta->add('total', $products->amount()->display());
        }

        $meta->add('fee', $charge->display());

        $request = [
            'amount' => $amount->add($charge)->getInt(),
            'email' => $customer->email(),
            'currency' => 'NGN',
            'reference' => $reference,
            'channels' => $channels,
            'metadata' => [
                'products' => $products->toArray(),
                'fee' => $charge->getInt(),
                'label' => $label->name(),
                'custom_fields' => $this->processMeta($meta)
            ]
        ];

        if ($recurring) {
            $request['metadata']['custom_filters']['recurring'] = true;
        }

        if ($customization->has('success')) {
            $request['callback_url'] = $customization->success();
        }

        $checkout = $this->http()->post('transaction/initialize', $request);
        if ($checkout->successful()) {
            return new Checkout(
                $this,
                $checkout->json('data.reference'),
                $checkout->json('data.authorization_url'),
                Checkout::SUCCESS,
                $amount,
                $this->charge($amount),
                $customer,
                $products,
                $meta
            );
        } else {
            return new Checkout(
                $this,
                $reference,
                '',
                Checkout::FAILED,
                $amount,
                $this->charge($amount),
                $customer,
                $products,
                $meta
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function token(
        string $token,
        string $reference,
        LabelInterface $label,
        Customer $customer,
        Money $amount,
        Products $products,
        Meta $meta
    ): Transaction {
        $this->createCustomerIfNotExist($customer);
        $charge = $this->charge($amount);

        $meta->add('label', $label->name());

        if (count($products) > 0) {
            $meta->add('products', $products->count());
            $meta->add('total', $products->amount()->display());
        }

        $meta->add('fee', $charge->display());

        $request = [
            'authorization_code' => $token,
            'amount' => $amount->add($charge)->getInt(),
            'email' => $customer->email(),
            'currency' => 'NGN',
            'reference' => $reference,
            'queue' => true,
            'metadata' => [
                'products' => $products->toArray(),
                'fee' => $charge->getInt(),
                'label' => $label->name(),
                'custom_fields' => $this->processMeta($meta)
            ]
        ];


        $transaction = $this->http()->post('transaction/charge_authorization', $request);

        if ($transaction->successful()) {
            return $this->makeTransaction(
                $transaction->json('data.reference'),
                $transaction->json('data.amount'),
                (int)$transaction->json('data.metadata.fee'),
                $transaction->json('data.status'),
                $transaction->json('data.metadata.label'),
                $transaction->json('data.customer'),
                $transaction->json('data.metadata.products') ?? [],
                $transaction->json('data.metadata.custom_fields'),
                $transaction->json('data.channel') === 'card' ?
                    $transaction->json('data.authorization') : []
            );
        } else {
            return new Transaction(
                $this,
                '',
                Transaction::FAILED,
                $label,
                $amount,
                $charge,
                $customer,
                $products,
                $meta
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function charge(Money $amount): Money
    {
        $original = Money::NGN($amount->getInt());

        if ($amount->greaterThan(Money::NGN(250000))) {
            $amount = $amount->add(Money::NGN(10000));
        }

        return $amount->divide(
            (1 - 0.015),
            Money::ROUND_HALF_UP
        )->subtract($original);
    }

    /**
     * @inheritDoc
     */
    public function refund(string $reference, LabelInterface $label, string $reason = null): Refund
    {
        $refund = $this->http()->post('refund', [
            'transaction' => $reference,
            'merchant_note' => $reason
        ]);

        if ($refund->successful()) {
            $transaction = $this->transaction($reference);

            return new Refund(
                $this,
                $refund->json('data.id'),
                $reference,
                Refund::SUCCESS,
                Refund::FULL,
                Money::NGN($refund->json('data.amount')),
                $label,
                $transaction,
                $reason
            );
        } else {
            return new Refund(
                $this,
                '',
                $reference,
                Refund::FAILED,
                Refund::FULL,
                Money::NGN(0),
                $label,
                null,
                $reason
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function partialRefund(
        string $reference,
        LabelInterface $label,
        Money $amount,
        string $reason = null
    ): Refund {
        $refund = $this->http()->post('refund', [
            'transaction' => $reference,
            'amount' => $amount->getInt(),
            'merchant_note' => $reason
        ]);

        if ($refund->successful()) {
            $transaction = $this->transaction($reference);

            return new Refund(
                $this,
                $refund->json('data.id'),
                $reference,
                Refund::SUCCESS,
                Refund::PARTIAL,
                Money::NGN($refund->json('data.amount')),
                $label,
                $transaction,
                $reason
            );
        } else {
            return new Refund(
                $this,
                '',
                $reference,
                Refund::FAILED,
                Refund::PARTIAL,
                Money::NGN(0),
                $label,
                null,
                $reason
            );
        }
    }

    /**
     * Get prepared laravel http client
     *
     * @return PendingRequest
     */
    protected function http(): PendingRequest
    {
        return Http::baseUrl(config('services.paystack.url'))->withToken(config('services.paystack.secret'));
    }

    /**
     * Create Meta instance from payload
     *
     * @param array $payload
     * @return Meta
     */
    public function createMetaFromPayload(array $payload): Meta
    {
        $meta = Meta::data();

        \collect($payload)->each(function ($data) use ($meta) {
            $value = $data['value'];

            if (is_numeric($value)) {
                $value = is_float($value) ? (float)$value : (int)$value;
            }

            $meta->add($data['variable_name'], $value);
        });

        return $meta;
    }

    /**
     * Create Products instance from payload
     *
     * @param array $payload
     * @return Products
     */
    public function createProductsFromPayload(array $payload): Products
    {
        $products = \collect($payload)->reduce(function ($result, $data) {
            $result[] = $product = new Product(
                $data['name'],
                Money::NGN((int)$data['price']),
                (int)$data['quantity']
            );

            if (isset($data['model_id']) && isset($data['model_type'])) {
                $product->setModelMorph($data['model_id'], $data['model_type']);
            }

            return $result;
        });

        return !empty($products) ? (new Products(...$products))->withModel() : new Products();
    }

    /**
     * Create Customer instance from payload
     *
     * @param array $payload
     * @return Customer
     */
    public function createCustomerFromPayload(array $payload): Customer
    {
        return new Customer(
            $payload['email'],
            Customer::TYPE_EMAIL,
            $payload['first_name'] . ' ' . $payload['last_name'],
            $payload['email'],
            $payload['phone'] ?? ''
        );
    }

    /**
     * Create Card instance from payload
     *
     * @param array $payload
     * @return Card
     */
    public function createCardFromPayload(array $payload): Card
    {
        return new Card(
            $this,
            $payload['bin'] . str_repeat('*', 6) . $payload['last4'],
            $payload['brand'],
            $payload['exp_month'],
            $payload['exp_year'],
            $payload['account_name'] ?? '',
            $payload['bank'] ?? '',
            $payload['country_code'] ?? '',
            $payload['reusable'] ? $payload['authorization_code'] : '',
        );
    }

    /**
     * Create Customer if they do not exist on paystack
     * Note: Paystack auto create Customers but this helps fill in more
     * details about them before use.
     *
     * @param Customer $customer
     * @return bool
     */
    protected function createCustomerIfNotExist(Customer $customer): bool
    {
        if ($this->http()->get('customer/' . $customer->email())->status() === 404) {
            $names = explode(' ', $customer->name());

            $this->http()->post('customer', [
                'email' => $customer->email(),
                'first_name' => $names[0] ?? null,
                'last_name' => (count($names) > 1) ? ($names[count($names) - 1] ?? null) : null,
                'phone' => '+' . $customer->phone(),
            ]);

            return true;
        }

        return false;
    }

    /**
     * Mutate Meta instance to array
     *
     * @param Meta $meta
     * @return array
     */
    public function processMeta(Meta $meta): array
    {
        return $meta->collect()->reduce(function ($result, $value, $key) {
            $result[] = [
                'display_name' => ucwords(str_replace('_', ' ', $key)),
                'variable_name' => $key,
                'value' => $value
            ];

            return $result;
        });
    }
}
