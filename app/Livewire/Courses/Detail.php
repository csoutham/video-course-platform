<?php

namespace App\Livewire\Courses;

use App\Models\Course;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.public-layout')]
class Detail extends Component
{
    public string $slug;

    public function mount(string $slug): void
    {
        $this->slug = $slug;
    }

    public function render(): View
    {
        $course = Course::query()
            ->published()
            ->with([
                'modules.lessons' => fn ($query) => $query->published()->orderBy('sort_order'),
            ])
            ->firstWhere('slug', $this->slug);

        abort_if(! $course, 404);

        return view('livewire.courses.detail', [
            'course' => $course,
            'giftsEnabled' => (bool) config('learning.gifts_enabled'),
        ]);
    }
}
