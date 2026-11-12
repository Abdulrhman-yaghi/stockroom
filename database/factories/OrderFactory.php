<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OrderFactory extends Factory
{
    public function definition(): array
    {
        return ['reference' => 'SR-'.Str::ulid(), 'customer_id' => Customer::factory(), 'user_id' => User::factory(), 'status' => 'confirmed', 'total_cents' => 0];
    }
}
