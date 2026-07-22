<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class ShortLink extends Model
{
    use CentralConnection;

    protected $fillable = [
        'code',
        'target_url',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
        ];
    }

    public static function shorten(string $url): string
    {
        $link = static::create([
            'code' => static::uniqueCode(),
            'target_url' => $url,
        ]);

        return $link->url();
    }

    public static function forCalendar(array $payload): string
    {
        $link = static::create([
            'code' => static::uniqueCode(),
            'payload' => $payload,
        ]);

        return $link->url();
    }

    public function url(): string
    {
        return url('/l/'.$this->code);
    }

    public function isCalendar(): bool
    {
        return is_array($this->payload) && filled($this->payload);
    }

    private static function uniqueCode(): string
    {
        do {
            $code = Str::lower(Str::random(7));
        } while (static::query()->where('code', $code)->exists());

        return $code;
    }
}
