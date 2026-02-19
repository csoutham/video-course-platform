<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BrandingSetting extends Model
{
    protected $fillable = [
        'platform_name',
        'logo_url',
        'color_bg',
        'color_panel',
        'color_panel_soft',
        'color_border',
        'color_text',
        'color_muted',
        'color_brand',
        'color_brand_strong',
        'color_accent',
        'color_warning',
    ];
}
