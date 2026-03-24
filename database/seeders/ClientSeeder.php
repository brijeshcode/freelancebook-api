<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        $freelancer = User::where('role', 'freelancer')->first();

        $clients = [
            ['name' => 'David',           'type' => 'individual', 'contact_person' => 'David',      'status' => 'active', 'country' => 'Morroco'],
            ['name' => 'Mark',            'type' => 'individual', 'contact_person' => 'Mark Zouin', 'status' => 'active', 'country' => 'Lebanon'],
            ['name' => 'Nick',            'type' => 'individual', 'contact_person' => 'Nick', 'status' => 'active', 'country' => 'Lebanon'],
            ['name' => 'Pixel & Ink Studio', 'type' => 'company', 'contact_person' => 'Emma Dubois', 'status' => 'active', 'country' => 'Lebanon'],
        ];

        foreach ($clients as $index => $data) {
            $data['user_id']     = $freelancer->id;
            $data['client_code'] = 'CLI' . str_pad($index + 1, 3, '0', STR_PAD_LEFT);
            Client::create($data);
        }
    }
}
