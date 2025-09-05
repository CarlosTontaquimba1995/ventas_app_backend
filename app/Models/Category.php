<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'image',
        'is_active',
        'parent_id',
        'order'
    ];

    /**
     * The "booting" method of the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            $category->slug = $category->generateUniqueSlug($category->name);
        });

        static::updating(function ($category) {
            if ($category->isDirty('name')) {
                $category->slug = $category->generateUniqueSlug($category->name, $category->id);
            }
        });
    }

    /**
     * Generate a unique slug for the category.
     */
    protected function generateUniqueSlug(string $name, int $id = null): string
    {
        $slug = Str::slug($name);
        $query = static::where('slug', $slug);
        
        if ($id) {
            $query->where('id', '!=', $id);
        }
        
        $count = $query->count();
        
        if ($count > 0) {
            $slug = $slug . '-' . ($count + 1);
        }
        
        return $slug;
    }

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Parent category
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    // Child categories
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('order');
    }

    // All nested children
    public function allChildren()
    {
        return $this->children()->with('allChildren');
    }

    // Check if category has children
    public function hasChildren()
    {
        return $this->children()->count() > 0;
    }

    // Products relationship
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Scope a query to only include active categories.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
