<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
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


        foreach (['Fish purchase', 'Chicken / meat', 'Charcoal & gas', 'Vegetables & spices', 'Naan / bread',
            'Cold drinks stock', 'Packaging', 'Staff salary', 'Rent', 'Electricity & bills', 'Delivery / fuel',
            'Marketing', 'Maintenance', 'Other'] as $c) {
            ExpenseCategory::firstOrCreate(['name' => $c]);
        }
    }
}
