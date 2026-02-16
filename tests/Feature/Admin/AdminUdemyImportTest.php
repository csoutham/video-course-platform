<?php

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function udemyFakeHtml(string $title = 'Imported Udemy Course', string $description = 'Imported description'): string
{
    $jsonLd = json_encode([
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'Course',
                'name' => $title,
                'description' => $description,
                'image' => 'https://img.example.com/thumbnail.jpg',
                'syllabusSections' => [
                    [
                        '@type' => 'Syllabus',
                        'name' => 'Section One',
                        'timeRequired' => 'PT5M',
                        'hasPart' => [
                            [
                                '@type' => 'CreativeWork',
                                'name' => 'Lesson One',
                                'timeRequired' => 'PT2M',
                            ],
                        ],
                    ],
                    [
                        '@type' => 'Syllabus',
                        'name' => 'Section Two',
                        'timeRequired' => 'PT10M',
                        'hasPart' => [
                            [
                                '@type' => 'CreativeWork',
                                'name' => 'Lesson Two',
                                'timeRequired' => 'PT4M',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ], JSON_THROW_ON_ERROR);

    return <<<HTML
    <!doctype html>
    <html>
      <head>
        <title>{$title}</title>
        <meta name="description" content="{$description}">
        <meta property="og:image" content="https://img.example.com/thumbnail.jpg">
        <script type="application/ld+json">{$jsonLd}</script>
      </head>
      <body>Udemy page</body>
    </html>
    HTML;
}

function udemyCurriculumHtmlSnippet(): string
{
    return <<<HTML
    <div class="_panel_xk1nn_16 curriculum-section-module-scss-module__9JCrHq__panel _expanded_xk1nn_56">
      <div class="ud-btn ud-btn-medium ud-btn-link ud-heading-md ud-accordion-panel-toggler _panel-toggler_xk1nn_24 _outer-panel-toggler_xk1nn_37">
        <h3 class="ud-accordion-panel-heading curriculum-section-module-scss-module__9JCrHq__section-title-container">
          <button type="button" class="ud-btn ud-btn-medium ud-btn-link ud-heading-md js-panel-toggler _panel-toggler_xk1nn_24">
            <span class="ud-accordion-panel-title">
              <span class="curriculum-section-module-scss-module__9JCrHq__section-title">Introduction</span>
              <span class="ud-text-sm curriculum-section-module-scss-module__9JCrHq__section-content-stats">2 lectures • <span>1min</span></span>
            </span>
          </button>
        </h3>
      </div>
      <div class="_content-wrapper_xk1nn_63">
        <div class="ud-accordion-panel-content _content_xk1nn_63">
          <ul class="ud-unstyled-list ud-block-list">
            <li>
              <div class="ud-block-list-item-content">
                <div class="curriculum-section-module-scss-module__9JCrHq__row">
                  <span class="curriculum-section-module-scss-module__9JCrHq__course-lecture-title">Introduction</span>
                  <span class="curriculum-section-module-scss-module__9JCrHq__item-content-summary"><span>0:31</span></span>
                </div>
              </div>
            </li>
            <li>
              <div class="ud-block-list-item-content">
                <div class="curriculum-section-module-scss-module__9JCrHq__row">
                  <span class="curriculum-section-module-scss-module__9JCrHq__course-lecture-title">What is a monologue?</span>
                  <span class="curriculum-section-module-scss-module__9JCrHq__item-content-summary"><span>0:33</span></span>
                </div>
              </div>
            </li>
          </ul>
        </div>
      </div>
    </div>
    HTML;
}

function udemyFakeHtmlWithSectionOnlyJsonLdAndCurriculumHtml(
    string $title = 'Imported Udemy Course',
    string $description = 'Imported description'
): string {
    $jsonLd = json_encode([
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'Course',
                'name' => $title,
                'description' => $description,
                'image' => 'https://img.example.com/thumbnail.jpg',
                'syllabusSections' => [
                    [
                        '@type' => 'Syllabus',
                        'name' => 'Introduction',
                        'timeRequired' => 'PT1M',
                    ],
                ],
            ],
        ],
    ], JSON_THROW_ON_ERROR);

    $curriculum = udemyCurriculumHtmlSnippet();

    return <<<HTML
    <!doctype html>
    <html>
      <head>
        <title>{$title}</title>
        <meta name="description" content="{$description}">
        <meta property="og:image" content="https://img.example.com/thumbnail.jpg">
        <script type="application/ld+json">{$jsonLd}</script>
      </head>
      <body>{$curriculum}</body>
    </html>
    HTML;
}

test('admin can preview udemy url import', function (): void {
    $admin = User::factory()->admin()->create();

    Http::fake([
        'https://www.udemy.com/course/example-course/' => Http::response(udemyFakeHtml(), 200),
    ]);

    $response = $this->actingAs($admin)->post(route('admin.imports.udemy.preview'), [
        'source_url' => 'https://www.udemy.com/course/example-course/',
        'confirm_ownership' => '1',
    ]);

    $response
        ->assertRedirect(route('admin.imports.udemy.show', ['source_url' => 'https://www.udemy.com/course/example-course/']));

    $this->actingAs($admin)
        ->get(route('admin.imports.udemy.show'))
        ->assertOk()
        ->assertSee('Preview')
        ->assertSee('Imported Udemy Course')
        ->assertSee('Section One')
        ->assertSee('Lesson Two');
});

test('preview requires ownership confirmation', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('admin.imports.udemy.preview'), [
        'source_url' => 'https://www.udemy.com/course/example-course/',
    ])->assertSessionHasErrors('confirm_ownership');
});

test('admin can commit udemy import and create course shells', function (): void {
    $admin = User::factory()->admin()->create();

    Http::fake([
        'https://www.udemy.com/course/example-course/' => Http::response(udemyFakeHtml(), 200),
    ]);

    $response = $this->actingAs($admin)->post(route('admin.imports.udemy.commit'), [
        'source_url' => 'https://www.udemy.com/course/example-course/',
        'confirm_ownership' => '1',
        'overwrite_mode' => 'safe_merge',
    ]);

    $course = Course::query()->firstWhere('source_url', 'https://www.udemy.com/course/example-course/');

    expect($course)->not->toBeNull();
    expect($course?->source_platform)->toBe('udemy');
    expect($course?->is_published)->toBeFalse();
    expect($course?->modules()->count())->toBe(2);
    expect($course?->lessons()->count())->toBe(2);

    $response->assertRedirect(route('admin.courses.edit', $course));
});

test('reimport by same source url does not duplicate lesson shells', function (): void {
    $admin = User::factory()->admin()->create();

    Http::fake([
        'https://www.udemy.com/course/example-course/' => Http::response(udemyFakeHtml(), 200),
    ]);

    $this->actingAs($admin)->post(route('admin.imports.udemy.commit'), [
        'source_url' => 'https://www.udemy.com/course/example-course/',
        'confirm_ownership' => '1',
        'overwrite_mode' => 'safe_merge',
    ])->assertRedirect();

    $this->actingAs($admin)->post(route('admin.imports.udemy.commit'), [
        'source_url' => 'https://www.udemy.com/course/example-course/',
        'confirm_ownership' => '1',
        'overwrite_mode' => 'safe_merge',
    ])->assertRedirect();

    $course = Course::query()->firstWhere('source_url', 'https://www.udemy.com/course/example-course/');

    expect($course)->not->toBeNull();
    expect($course?->lessons()->count())->toBe(2);
    expect($course?->lessons()->where('is_imported_shell', true)->count())->toBe(2);
});

test('preview supports manual html fallback when udemy blocks remote fetch', function (): void {
    $admin = User::factory()->admin()->create();

    Http::fake([
        'https://www.udemy.com/course/example-course/' => Http::response('<html>challenge</html>', 403, [
            'cf-mitigated' => 'challenge',
        ]),
    ]);

    $response = $this->actingAs($admin)->post(route('admin.imports.udemy.preview'), [
        'source_url' => 'https://www.udemy.com/course/example-course/',
        'source_html' => udemyFakeHtml('Manual HTML Import'),
        'confirm_ownership' => '1',
    ]);

    $response->assertRedirect(route('admin.imports.udemy.show', ['source_url' => 'https://www.udemy.com/course/example-course/']));

    $this->actingAs($admin)
        ->get(route('admin.imports.udemy.show'))
        ->assertOk()
        ->assertSee('Manual HTML Import');
});

test('preview returns actionable error when udemy challenge page is served', function (): void {
    $admin = User::factory()->admin()->create();

    Http::fake([
        'https://www.udemy.com/course/example-course/' => Http::response('<html>challenge</html>', 403, [
            'cf-mitigated' => 'challenge',
        ]),
    ]);

    $this->actingAs($admin)->post(route('admin.imports.udemy.preview'), [
        'source_url' => 'https://www.udemy.com/course/example-course/',
        'confirm_ownership' => '1',
    ])->assertSessionHasErrors('source_url');
});

test('html curriculum panels are used when jsonld has only section-level data', function (): void {
    $admin = User::factory()->admin()->create();

    Http::fake([
        'https://www.udemy.com/course/example-course/' => Http::response(
            udemyFakeHtmlWithSectionOnlyJsonLdAndCurriculumHtml(),
            200
        ),
    ]);

    $this->actingAs($admin)->post(route('admin.imports.udemy.commit'), [
        'source_url' => 'https://www.udemy.com/course/example-course/',
        'confirm_ownership' => '1',
        'overwrite_mode' => 'safe_merge',
    ])->assertRedirect();

    $course = Course::query()->firstWhere('source_url', 'https://www.udemy.com/course/example-course/');

    expect($course)->not->toBeNull();
    expect($course?->modules()->count())->toBe(1);
    expect($course?->lessons()->count())->toBe(2);
    expect($course?->lessons()->pluck('title')->all())->toBe([
        'Introduction',
        'What is a monologue?',
    ]);
});
