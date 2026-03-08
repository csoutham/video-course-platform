<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BrandingSetting extends Model
{
    protected $fillable = [
        'platform_name',
        'logo_url',
        'logo_height_px',
        'font_provider',
        'font_family',
        'font_weights',
        'publisher_name',
        'publisher_website',
        'footer_tagline',
        'homepage_eyebrow',
        'homepage_title',
        'homepage_subtitle',
        'homepage_seo_title',
        'homepage_seo_description',
        'analytics_provider',
        'analytics_site_id',
        'analytics_script_url',
        'analytics_custom_head_snippet',
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
