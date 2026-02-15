<?php

namespace App\Livewire\Courses;

use App\Models\Course;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.public')]
class Catalog extends Component
{
    public function render(): View
    {
        $user = auth()->user();
        $ownedCourseIds = collect();

        if ($user) {
            $ownedCourseIds = $user->entitlements()
                ->active()
                ->pluck('course_id');
        }

        return view('livewire.courses.catalog', [
            'courses' => Course::query()
                ->published()
                ->orderBy('title')
                ->get(),
            'ownedCourseIds' => $ownedCourseIds,
        ]);
    }
}
