<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Actions\Stripe\SetupStripeAccount;

class SetupStripeAccountCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'setup-stripe';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create product and recurring prices on Stripe automatically';

    /**
     * Execute the console command.
     */
    public function handle(SetupStripeAccount $setupStripeAccount): void
    {
        $productName = 'MVP Skeleton';

        $plans = [
            ['name' => 'Basic',    'amount' => 1000],
            ['name' => 'Standard', 'amount' => 2000],
            ['name' => 'Premium',  'amount' => 3000],
        ];

        $setupStripeAccount->execute($productName, $plans);
        $this->output->success('Stripe setup completed successfully!');
    }
}
