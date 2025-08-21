<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CommissionSetting;
use App\Models\EventCategory;

class CommissionSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Commission par défaut (toutes catégories)
        CommissionSetting::create([
            'category_id' => null,
            'promoter_id' => null,
            'commission_rate' => 10.00, // 10%
            'platform_fee_fixed' => 500, // 500 FCFA fixe
            'min_commission' => 1000, // Minimum 1000 FCFA
            'is_active' => true,
            'valid_from' => now()->subYear(),
            'valid_until' => null,
        ]);

        // Commission réduite pour les concerts (volume plus élevé)
        $concertCategory = EventCategory::where('slug', 'concert')->first();
        if ($concertCategory) {
            CommissionSetting::create([
                'category_id' => $concertCategory->id,
                'promoter_id' => null,
                'commission_rate' => 8.00, // 8%
                'platform_fee_fixed' => 300, // 300 FCFA fixe
                'min_commission' => 800, // Minimum 800 FCFA
                'is_active' => true,
                'valid_from' => now()->subYear(),
                'valid_until' => null,
            ]);
        }

        // Commission majorée pour théâtre (événements plus petits)
        $theatreCategory = EventCategory::where('slug', 'theatre')->first();
        if ($theatreCategory) {
            CommissionSetting::create([
                'category_id' => $theatreCategory->id,
                'promoter_id' => null,
                'commission_rate' => 12.00, // 12%
                'platform_fee_fixed' => 700, // 700 FCFA fixe
                'min_commission' => 1200, // Minimum 1200 FCFA
                'is_active' => true,
                'valid_from' => now()->subYear(),
                'valid_until' => null,
            ]);
        }

        // Commission sport (événements populaires)
        $sportCategory = EventCategory::where('slug', 'sport')->first();
        if ($sportCategory) {
            CommissionSetting::create([
                'category_id' => $sportCategory->id,
                'promoter_id' => null,
                'commission_rate' => 7.00, // 7%
                'platform_fee_fixed' => 200, // 200 FCFA fixe
                'min_commission' => 500, // Minimum 500 FCFA
                'is_active' => true,
                'valid_from' => now()->subYear(),
                'valid_until' => null,
            ]);
        }

        // Commission conférence (événements business)
        $conferenceCategory = EventCategory::where('slug', 'conference')->first();
        if ($conferenceCategory) {
            CommissionSetting::create([
                'category_id' => $conferenceCategory->id,
                'promoter_id' => null,
                'commission_rate' => 15.00, // 15%
                'platform_fee_fixed' => 1000, // 1000 FCFA fixe
                'min_commission' => 2000, // Minimum 2000 FCFA
                'is_active' => true,
                'valid_from' => now()->subYear(),
                'valid_until' => null,
            ]);
        }

        // Commission festival (événements grands publics)
        $festivalCategory = EventCategory::where('slug', 'festival')->first();
        if ($festivalCategory) {
            CommissionSetting::create([
                'category_id' => $festivalCategory->id,
                'promoter_id' => null,
                'commission_rate' => 9.00, // 9%
                'platform_fee_fixed' => 400, // 400 FCFA fixe
                'min_commission' => 1000, // Minimum 1000 FCFA
                'is_active' => true,
                'valid_from' => now()->subYear(),
                'valid_until' => null,
            ]);
        }
    }
}