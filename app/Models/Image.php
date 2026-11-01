<?php

namespace App\Models;

use Database\Factories\ImageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * An image in the library, stored in the "images" folder of the public disk.
 *
 * Uploads keep a tidied version of their own name ("Team Photo.JPG" becomes
 * "team-photo.jpg"). A name that's taken is numbered ("team-photo-2.jpg"), so an
 * upload never replaces an image that's already there.
 */
#[Fillable([
    'filename',
    'alt_text',
    'width',
    'height',
    'size',
    'mime_type',
])]
class Image extends Model
{
    /** @use HasFactory<ImageFactory> */
    use HasFactory;

    public const DISK = 'public';

    public const DIRECTORY = 'images';

    /**
     * The types of image that may be uploaded, with the extension each is saved under.
     *
     * @var array<string, string>
     */
    public const TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Image $image): bool {
            return $image->usages()->isEmpty();
        });

        static::deleted(function (Image $image): void {
            Storage::disk(self::DISK)->delete($image->path());
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'width' => 'integer',
            'height' => 'integer',
            'size' => 'integer',
        ];
    }

    /**
     * Add an uploaded file to the library, exactly as it is, under a name no other image has.
     */
    public static function storeUpload(UploadedFile $file): self
    {
        $mimeType = (string) $file->getMimeType();
        $extension = self::TYPES[$mimeType] ?? strtolower($file->getClientOriginalExtension());
        $filename = self::uniqueFilename(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), $extension);
        $path = $file->storeAs(self::DIRECTORY, $filename, self::DISK);

        $absolutePath = Storage::disk(self::DISK)->path($path);
        [$width, $height] = getimagesize($absolutePath) ?: [0, 0];

        return self::query()->create([
            'filename' => $filename,
            'width' => $width,
            'height' => $height,
            'size' => (int) filesize($absolutePath),
            'mime_type' => $mimeType,
        ]);
    }

    /**
     * A tidy file name that isn't used yet, numbered ("photo-2.jpg") if it would be.
     */
    public static function uniqueFilename(string $name, string $extension): string
    {
        $baseName = Str::slug($name) ?: 'image';
        $filename = "{$baseName}.{$extension}";

        for ($number = 2; self::isFilenameTaken($filename); $number++) {
            $filename = "{$baseName}-{$number}.{$extension}";
        }

        return $filename;
    }

    private static function isFilenameTaken(string $filename): bool
    {
        return self::query()->where('filename', $filename)->exists()
            || Storage::disk(self::DISK)->exists(self::DIRECTORY.'/'.$filename);
    }

    /**
     * Where the image is on the disk.
     */
    public function path(): string
    {
        return self::DIRECTORY.'/'.$this->filename;
    }

    /**
     * The address of the image on the site, without the domain, so pages keep
     * working if the site moves.
     */
    public function url(): string
    {
        return '/storage/'.$this->path();
    }

    /**
     * The shortcode that places the image in a page.
     */
    public function shortcode(): string
    {
        return '[image:'.$this->filename.']';
    }

    /**
     * The pages, posts, modules and cards whose content uses the image, by name,
     * e.g. "Page: About us".
     *
     * @return Collection<int, string>
     */
    public function usages(): Collection
    {
        $pattern = '/(?<![a-z0-9._-])'.preg_quote($this->filename, '/').'(?!\.?[a-z0-9_-])/i';
        $uses = fn (Model $model): bool => (bool) preg_match($pattern, (string) $model->getAttribute('body'));
        $containing = fn (string $model) => $model::query()->where('body', 'like', '%'.$this->filename.'%');

        return collect()
            ->merge($containing(Entry::class)->get(['id', 'type', 'title', 'body'])->filter($uses)
                ->map(fn (Entry $entry): string => $entry->type->getLabel().': '.$entry->title))
            ->merge($containing(Module::class)->get(['id', 'name', 'body'])->filter($uses)
                ->map(fn (Module $module): string => 'Module: '.$module->name))
            ->merge($containing(Card::class)->get(['id', 'name', 'body'])->filter($uses)
                ->map(fn (Card $card): string => 'Card: '.$card->name))
            ->values();
    }

    /**
     * The library file name that a reference in content points to: "team.jpg",
     * "images/team.jpg" or "/storage/images/team.jpg" all mean "team.jpg".
     */
    public static function nameFromReference(string $reference): ?string
    {
        $name = strtolower(urldecode(ltrim(parse_url($reference, PHP_URL_PATH) ?: $reference, '/')));
        $name = Str::after(Str::after($name, 'storage/'), self::DIRECTORY.'/');

        return preg_match('/^[a-z0-9][a-z0-9._-]*\.[a-z0-9]+$/', $name) ? $name : null;
    }
}
