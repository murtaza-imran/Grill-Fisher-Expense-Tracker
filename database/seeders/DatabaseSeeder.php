<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use App\Models\MenuItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@grillfisher.com')],
            ['name' => 'Grill Fisher Admin', 'password' => Hash::make(env('ADMIN_PASSWORD', 'grillfisher'))]
        );

        // Menu from the Grill Fisher & BBQ menu card
        $items = [
            ['Rohu Grill Fish (1 Kg)', 1730], ['Boneless Fish (1 Kg)', 2400],
            ['Chicken Tikka (1 Pao)', 300], ['Chicken Tikka (1 Kg)', 1100],
            ['Chicken Malai Boti (1 Pao)', 350], ['Chicken Malai Boti (1 Kg)', 1300],
            ['Chicken Wings (4 Piece)', 250], ['Chicken Kebab (1 Seekh)', 150],
            ['Chicken Leg Piece', 450], ['Chicken Chest Piece', 480],
            ['Deal 1 – BBQ Platter', 1200], ['Deal 2 – Fish & BBQ', 2000], ['Deal 3 – BBQ Platter', 2200],
            ['Deal 4 – Fish & BBQ', 2800], ['Deal 5 – Grilled Fish Special', 3460],
            ['Cold Drink (Regular)', 70], ['Water Bottle', 50], ['Raita', 50], ['Fish Sauce', 50],
        ];
        if (MenuItem::count() === 0) {
            foreach ($items as $i => [$name, $price]) {
                MenuItem::create(['name' => $name, 'price' => $price, 'sort' => $i]);
            }
        }

        foreach (['Fish purchase', 'Chicken / meat', 'Charcoal & gas', 'Vegetables & spices', 'Naan / bread',
            'Cold drinks stock', 'Packaging', 'Staff salary', 'Rent', 'Electricity & bills', 'Delivery / fuel',
            'Marketing', 'Maintenance', 'Other'] as $c) {
            ExpenseCategory::firstOrCreate(['name' => $c]);
        }
    }
}
