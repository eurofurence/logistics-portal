<?php

namespace App\Models;

use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Database\Eloquent\Builder;
use Spatie\MediaLibrary\HasMedia;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int $id
 * @property string|null $data1
 * @property string|null $data2
 * @property string|null $data3
 * @property string|null $data4
 * @property string|null $data5
 * @property string|null $data6
 * @property string|null $data7
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read MediaCollection<int, Media> $media
 * @property-read int|null $media_count
 * @method static Builder<static>|TestModel newModelQuery()
 * @method static Builder<static>|TestModel newQuery()
 * @method static Builder<static>|TestModel query()
 * @method static Builder<static>|TestModel whereCreatedAt($value)
 * @method static Builder<static>|TestModel whereData1($value)
 * @method static Builder<static>|TestModel whereData2($value)
 * @method static Builder<static>|TestModel whereData3($value)
 * @method static Builder<static>|TestModel whereData4($value)
 * @method static Builder<static>|TestModel whereData5($value)
 * @method static Builder<static>|TestModel whereData6($value)
 * @method static Builder<static>|TestModel whereData7($value)
 * @method static Builder<static>|TestModel whereId($value)
 * @method static Builder<static>|TestModel whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class TestModel extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'data1',
        'data2',
        'data3',
        'data4',
        'data5',
        'data6',
        'data7'
    ];

    protected static function boot()
    {
        parent::boot();
    }
}
