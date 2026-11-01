<?php

namespace App\Models;

use App\SliderAnimation;
use Database\Factories\SliderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A slideshow of library images, placed in a page with [slider:slug].
 */
#[Fillable([
    'name',
    'slug',
    'animation',
    'seconds_per_slide',
])]
class Slider extends Model
{
    /** @use HasFactory<SliderFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'animation' => 'slide',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'animation' => SliderAnimation::class,
            'seconds_per_slide' => 'integer',
        ];
    }

    /**
     * The slides, in the order they're shown.
     *
     * @return HasMany<Slide, $this>
     */
    public function slides(): HasMany
    {
        return $this->hasMany(Slide::class)->orderBy('sort');
    }

    public function shortcode(): string
    {
        return '[slider:'.$this->slug.']';
    }

    /**
     * The slider's HTML, or nothing if it has no slides yet.
     */
    public function html(): ?string
    {
        $slides = $this->slides()->with('image')->get();

        if ($slides->isEmpty()) {
            return null;
        }

        return view('components.site.slider', ['slider' => $this, 'slides' => $slides])->render();
    }
}
