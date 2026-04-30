<?php

namespace Database\Seeders;

use App\Models\Business;
use Illuminate\Database\Seeder;

class BusinessSeeder extends Seeder
{
    public function run(): void
    {
        Business::firstOrCreate(['code' => 'bcf'], [
            'name'      => 'BCF Climbing Frames',
            'color'     => '#EF233C',
            'is_active' => true,
        ]);

        Business::firstOrCreate(['code' => 'bgr'], [
            'name'      => 'BGR',
            'color'     => '#2563EB',
            'is_active' => true,
        ]);
    }
}
