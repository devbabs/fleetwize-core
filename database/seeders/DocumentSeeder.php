<?php

namespace Database\Seeders;

use App\Models\Document;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DocumentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $documents = [
            'Insurance',
            'Roadworthiness',
            'Vehicle License',
            'Hackney Permit',
            'Driver License',
            'Proof of Ownership',
            'Vehicle Registration(Plate Number)',
            "Central Motor Registry Certificate",
        ];

        foreach ($documents as $title) {
            Document::firstOrCreate([
                'title' => $title,
            ]);
        }
    }
}
