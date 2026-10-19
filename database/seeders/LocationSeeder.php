<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Create the confirmed SignatureCare facilities.
     *
     * More facilities (Midland, etc.) are added by an admin on the Facilities page.
     */
    public function run(): void
    {
        $facilities = [
            ['name' => 'College Station', 'city' => 'College Station'],
            ['name' => 'Killeen', 'city' => 'Killeen'],
            ['name' => 'Preston', 'city' => null],
            ['name' => 'Frisco', 'city' => 'Frisco'],
        ];

        foreach ($facilities as $facility) {
            // firstOrCreate keeps the seeder safe to run more than once,
            // and never overwrites details an admin has edited since.
            Location::firstOrCreate(
                ['name' => $facility['name']],
                [
                    'city' => $facility['city'],
                    'state' => 'TX',
                    'is_active' => true,
                ]
            );
        }
    }
}
