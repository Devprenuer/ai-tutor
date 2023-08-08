<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Industry;
use App\Models\Topic;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $industry = $this->createIndustry();
        $this->createTopics($industry->id);
    }

    public function createIndustry()
    {
        return Industry::create([
            'industry' => 'Coding',
        ]);
    }

    public function createTopics($industry_id)
    {
        Topic::create([
            'topic' => 'PHP',
            'industry_id' => $industry_id,
        ]);

        Topic::create([
            'topic' => 'Javscript',
            'industry_id' => $industry_id,
        ]);

        Topic::create([
            'topic' => 'Python',
            'industry_id' => $industry_id,
        ]);

        Topic::create([
            'topic' => 'Rust',
            'industry_id' => $industry_id,
        ]);
    }
}
