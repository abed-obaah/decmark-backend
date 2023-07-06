<?php

namespace App\Listeners;

use Walletable\WalletManager;
use Illuminate\Support\Str;

class CreateUserWallet
{
    /**
     * Wallet manager
     *
     * @var \Walletable\WalletManager
     */
    protected $manager;
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(WalletManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        try {
            $this->manager->create($event->user, Str::orderedUuid(), 'Main Wallet', 'main', 'NGN');
        } catch (\Throwable $th) {
            $event->user->delete();
            throw $th;
        }
    }
}
