<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user for Filament
        User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
            ]
        );

        // Seed sample items with RFID UIDs
        $items = [
            ['rfid_uid' => 'RFID001', 'name' => 'Laptop Dell Latitude 5520', 'category' => 'Electronics'],
            ['rfid_uid' => 'RFID002', 'name' => 'Projector Epson EB-X51', 'category' => 'Electronics'],
            ['rfid_uid' => 'RFID003', 'name' => 'Office Chair Ergonomic', 'category' => 'Furniture'],
            ['rfid_uid' => 'RFID004', 'name' => 'Whiteboard 120x240cm', 'category' => 'Equipment'],
            ['rfid_uid' => 'RFID005', 'name' => 'Printer HP LaserJet Pro', 'category' => 'Electronics'],
            ['rfid_uid' => 'RFID006', 'name' => 'Standing Desk Adjustable', 'category' => 'Furniture'],
            ['rfid_uid' => 'RFID007', 'name' => 'Paper Ream A4 80gsm', 'category' => 'Stationery'],
            ['rfid_uid' => 'RFID008', 'name' => 'Toyota Avanza 2023', 'category' => 'Vehicle'],
        ];

        foreach ($items as $item) {
            Item::firstOrCreate(
                ['rfid_uid' => $item['rfid_uid']],
                $item
            );
        }
    }
}
