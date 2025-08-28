<?php

namespace Database\Factories;

use App\Models\Photo;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Photo>
 */
class PhotoFactory extends Factory
{
    protected $model = Photo::class;

    public function definition(): array
    {
        $uuid = (string) Str::uuid();
        return [
            'user_id' => User::factory(),
            'original_path' => "photos/originals/{$uuid}.jpg",
            'thumbnail_path' => "photos/thumbnails/{$uuid}.jpg",
            'width' => 1200,
            'height' => 900,
            'caption' => $this->faker->optional()->sentence(),
            'taken_at' => now(),
            'is_completed' => true,
        ];
    }
}


