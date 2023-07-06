<?php

namespace Tests\Feature\User;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class WalletTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Index
     */
    public function testIndex()
    {
        $this->loginUser();

        $response = $this->get(route('api.v1.user.wallet.index'));

        $response->assertStatus(JsonResponse::HTTP_OK)->assertExactJson([
            'label' => 'Main Wallet',
            'amount' => [
                'amount' => '0',
                'display' => "₦\u{00a0}0.00",
                'symbol' => '₦',
                'currency' => 'NGN'
            ],
            'status' => 'active'
        ]);
    }

    /**
     * Index with balance
     */
    public function testIndexWithBalnce()
    {
        $this->loginUserWithBalance();

        $response = $this->get(route('api.v1.user.wallet.index'));

        $response->assertStatus(JsonResponse::HTTP_OK)->assertExactJson([
            'label' => 'Main Wallet',
            'amount' => [
                'amount' => '10000000',
                'display' => "₦\u{00a0}100,000.00",
                'symbol' => '₦',
                'currency' => 'NGN'
            ],
            'status' => 'active'
        ]);
    }

    /**
     * Test single transaction
     */
    public function testSingleTransaction()
    {
        $user1 = $this->loginUserWithBalance();
        $user2 = $this->newUser();

        $wallet1 = $user1->wallets()->first();
        $wallet2 = $user2->wallets()->first();

        $result = $wallet1->transfer($wallet2, 2000000, 'This is a test transfer');

        $trx1 = $result->getTransactions()->where('type', 'debit')->first();
        $trx2 = $result->getTransactions()->where('type', 'credit')->first();

        $response = $this->get(route('api.v1.user.wallet.transaction.single', [
            'transaction' => $trx1->id
        ]));

        $response->assertStatus(JsonResponse::HTTP_OK)
            ->assertJsonPath('type', 'debit')
            ->assertJsonPath('remarks', 'This is a test transfer')
            ->assertJsonPath('title', $user2->first_name . ' ' . $user2->last_name)
            ->assertJsonStructure([
                'id',
                'type',
                'title',
                'image',
                'remarks',
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
            ]);
        $this->assertSame($trx1->session, $trx2->session);
    }

    /**
     * Test single transaction
     */
    public function testSingleTransactionNotFound()
    {
        $user1 = $this->loginUserWithBalance();
        $user2 = $this->newUser();

        $wallet1 = $user1->wallets()->first();
        $wallet2 = $user2->wallets()->first();

        $result = $wallet1->transfer($wallet2, 2000000, 'This is a test transfer');

        $trx1 = $result->getTransactions()->where('type', 'debit')->first();
        $trx2 = $result->getTransactions()->where('type', 'credit')->first();

        $response = $this->get(route('api.v1.user.wallet.transaction.single', [
            'transaction' => $trx2->id
        ]));

        $response->assertStatus(JsonResponse::HTTP_NOT_FOUND)
            ->assertJsonPath('message', 'Not found.')
            ->assertJsonStructure([
                'message'
            ]);
        $this->assertSame($trx1->session, $trx2->session);
    }

    /**
     * Test single transaction
     */
    public function testReportTransaction()
    {
        $user1 = $this->loginUserWithBalance();
        $user2 = $this->newUser();

        $wallet1 = $user1->wallets()->first();
        $wallet2 = $user2->wallets()->first();

        $trx1 = $wallet1->transfer(
            $wallet2,
            2000000,
            'This is a test transfer'
        )->getTransactions()->where('type', 'debit')->first();

        $response = $this->postJson(route('api.v1.user.wallet.transaction.report'), [
            'transaction' => (string)$trx1->id,
            'flags' => 'mischevious,fraud,unknown',
            'title' => 'I do not know about this transaction',
            'note' => 'I saw this transaction when i woke up this morning and i wasn`t the one who initiated it.',
        ]);

        $response->assertStatus(JsonResponse::HTTP_CREATED)
            ->assertJsonPath('message', 'Dispute created for the transaction.')
            ->assertJsonPath('dispute.type', 'transaction')
            ->assertJsonStructure([
                'message',
                'dispute' => [
                    'id',
                    'flags',
                    'note',
                    'type',
                    'disputed' => [
                        'id',
                        'type',
                        'title',
                        'image',
                        'remarks',
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
                ]
            ]);
    }

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testCreatePin()
    {
        $user = $this->loginUser();
        $user->update([
            'pin' => null
        ]);

        $response = $this->postJson(route('api.v1.user.auth.pin.create'), [
            'pin' => '1234',
            'pin_confirmation' => '1234'
        ]);

        $response->assertStatus(JsonResponse::HTTP_CREATED)->assertJsonStructure([
            'message'
        ])->assertJsonPath('message', 'Pin created successfully.');
        $this->assertTrue(Hash::check('1234', $user->fresh()->pin));
    }

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testCreatePinConfict()
    {
        $user = $this->loginUser();
        $user->update([
            'pin' => Hash::make('4321')
        ]);

        $response = $this->postJson(route('api.v1.user.auth.pin.create'), [
            'pin' => '1234',
            'pin_confirmation' => '1234'
        ]);

        $response->assertStatus(JsonResponse::HTTP_CONFLICT)->assertJsonStructure([
            'message'
        ])->assertJsonPath('message', 'Pin already created.');

        $this->assertTrue(Hash::check('4321', $user->fresh()->pin));
    }

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testCreatePinError()
    {
        $user = $this->loginUser();
        $user->update([
            'pin' => null
        ]);

        $response = $this->postJson(route('api.v1.user.auth.pin.create'), [
            'pin' => '1234',
            'pin_confirmation' => '123'
        ]);

        $response->assertStatus(JsonResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'pin'
                ]
            ]);

        $this->assertTrue(is_null($user->fresh()->pin));
    }

    /**
     * test success scenario for changing password.
     *
     * @return void
     */
    public function testChangePin()
    {
        $user = $this->loginUser();
        $user->update([
            'pin' => Hash::make('4321')
        ]);

        $response = $this->postJson(route('api.v1.user.auth.pin.change'), [
            'current_pin' => '4321',
            'pin' => '1234',
            'pin_confirmation' => '1234'
        ]);

        $response->assertStatus(JsonResponse::HTTP_ACCEPTED)->assertJsonStructure([
            'message'
        ])->assertJsonPath('message', 'Pin updated successfully.');

        $this->assertNotTrue(Hash::check('4321', $user->fresh()->pin));
        $this->assertTrue(Hash::check('1234', $user->fresh()->pin));
    }

    /**
     * test success scenario for changing password.
     *
     * @return void
     */
    public function testChangePinError()
    {
        $user = $this->loginUser();
        $user->update([
            'pin' => Hash::make('4321')
        ]);

        $response = $this->postJson(route('api.v1.user.auth.pin.change'), [
            'current_pin' => '4323',
            'pin' => '1234',
            'pin_confirmation' => '1233'
        ]);

        $response->assertStatus(JsonResponse::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'current_pin',
                    'pin'
                ]
            ]);

        $this->assertTrue(Hash::check('4321', $user->fresh()->pin));
        $this->assertNotTrue(Hash::check('1234', $user->fresh()->pin));
    }
}
