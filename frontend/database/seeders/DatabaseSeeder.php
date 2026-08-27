<?php

namespace Database\Seeders;

use App\Models\Bike;
use App\Models\Offer;
use App\Models\Ride;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // --- Demo rider account ---
        $user = User::factory()->create([
            'name' => 'Alex Rider',
            'email' => 'rider@velex.test',
            'password' => bcrypt('password'),
            'account_mode' => 'RIDER',
            'rider_status' => 'ACTIVE',
            'current_balance' => 42.50,
            'lifetime_spend' => 318.75,
        ]);

        // --- Fleet ---
        $bikes = collect([
            ['name' => 'Yamaha Bolt', 'model' => 'MT-07', 'license' => 'LHR-2291', 'hourly_rate' => 6.00, 'battery' => 88, 'last_known_address' => 'Gulberg III, Lahore', 'status' => 'RENTED'],
            ['name' => 'Vespa Electra', 'model' => 'Elettrica', 'license' => 'LHR-4410', 'hourly_rate' => 5.00, 'battery' => 100, 'last_known_address' => 'DHA Phase 5, Lahore', 'status' => 'AVAILABLE'],
            ['name' => 'Honda Volt-X', 'model' => 'CB125F-E', 'license' => 'LHR-7783', 'hourly_rate' => 4.50, 'battery' => 76, 'last_known_address' => 'Model Town, Lahore', 'status' => 'AVAILABLE'],
        ])->map(fn ($attrs) => Bike::create($attrs));

        // --- Active ride (first bike, currently RENTED) ---
        $activeRide = Ride::create([
            'user_id' => $user->id,
            'bike_id' => $bikes[0]->id,
            'started_at' => now()->subMinutes(45),
            'due_at' => now()->addMinutes(15),
            'cost' => 6.00,
            'status' => 'ACTIVE',
        ]);

        // --- Completed rides (history) ---
        $history = [
            ['bike' => $bikes[1], 'daysAgo' => 2, 'hours' => 1, 'cost' => 5.00],
            ['bike' => $bikes[2], 'daysAgo' => 5, 'hours' => 2, 'cost' => 9.00],
            ['bike' => $bikes[1], 'daysAgo' => 9, 'hours' => 1, 'cost' => 5.00],
        ];

        foreach ($history as $entry) {
            $start = now()->subDays($entry['daysAgo'])->subHours($entry['hours']);
            $end = now()->subDays($entry['daysAgo']);

            $ride = Ride::create([
                'user_id' => $user->id,
                'bike_id' => $entry['bike']->id,
                'started_at' => $start,
                'due_at' => $end,
                'ended_at' => $end,
                'cost' => $entry['cost'],
                'status' => 'COMPLETED',
            ]);

            WalletTransaction::create([
                'user_id' => $user->id,
                'ride_id' => $ride->id,
                'type' => 'RIDE_CHARGE',
                'amount' => -1 * $entry['cost'],
                'description' => "Ride charge for {$entry['bike']->name}",
            ]);
        }

        WalletTransaction::create([
            'user_id' => $user->id,
            'type' => 'TOPUP',
            'amount' => 50.00,
            'description' => 'Wallet top-up',
        ]);

        // --- Promo offer ---
        Offer::create([
            'code' => 'WEEKEND20',
            'title' => 'Unlock Unlimited Weekends',
            'description' => 'Get 20% off all long-distance rentals starting Friday evening.',
            'discount_type' => 'PERCENT',
            'discount_value' => 20,
            'expires_at' => now()->addMonths(2),
        ]);
    }
}
