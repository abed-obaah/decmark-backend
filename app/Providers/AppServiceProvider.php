<?php

namespace App\Providers;

use App\Models\User;
use App\Models\Admin;
use App\Models\Wallet;
use App\Models\Transaction;
use Walletable\Money\Money;
use Walletable\WalletManager;
use Walletable\Money\Currency;
use App\Services\Payment\Payer;
use App\Services\Webhook\Webhook;
use Walletable\WalletableManager;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use App\Services\Payment\PaymentManager;
use App\Services\Wallet\CardTopUpAction;
use App\Services\Webhook\WebhookManager;
use App\Services\Payment\Gateway\Gateway;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Notification;
use App\Services\Payment\Gateway\GatewayManager;
use App\Services\Payment\Labels\CardGatewayLabel;
use App\Services\Payment\Labels\WalletGatewayLabel;
use App\Services\Payment\Methods\CardPaymentMethod;
use Illuminate\Database\Eloquent\Relations\Relation;
use App\Services\Payment\Methods\WalletPaymentMethod;
use App\Services\Webhook\Drivers\QoreIDWebhookDriver;
use App\Services\Webhook\Drivers\PaystackWebhookDriver;
use App\Services\Webhook\Drivers\VerifyMeWebHookDriver;
use App\Services\Payment\Gateway\Drivers\PaystackGatewayDriver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(PaymentManager::class);
        $this->app->singleton(GatewayManager::class);
        $this->app->singleton(WebhookManager::class);
    }

    public function boot()
    {
        Schema::defaultStringLength(191);

        Relation::morphMap([
            'user' => User::class,
            'admin' => Admin::class,
            'wallet' => Wallet::class,
            'transaction' => Transaction::class,
        ]);

        // Wallet services
        /**
         * @var WalletManager
         */
        $walletable = app(WalletManager::class);
        $walletable->action('card_topup', CardTopUpAction::class);

        Payer::method('wallet', WalletPaymentMethod::class);
        Payer::method('card', CardPaymentMethod::class);

        /**
         * Register gateway drivers
         */
        Gateway::driver('paystack', PaystackGatewayDriver::class);

        /**
         * Register labels
         */
        Gateway::label('bank_card', CardGatewayLabel::class);
        Gateway::label('wallet', WalletGatewayLabel::class);

        /**
         * Register webhooks
         */
        Webhook::driver('paystack', PaystackWebhookDriver::class);
        Webhook::driver('qoreid', QoreIDWebhookDriver::class);
        Webhook::driver('verify.me', VerifyMeWebHookDriver::class);


        Money::currencies(
            Currency::new('NGN', '₦', 'Naira', 'Kobo', 100, 566),
        );

        $this->registerSchemaMacros();
    }

    private function registerSchemaMacros()
    {
        Blueprint::macro('primaryUuid', function (string $name = 'id') {
            /**
             * @var Blueprint $this
             */
            $this->uuid($name)->primary();
            return $this;
        });
    }
}
