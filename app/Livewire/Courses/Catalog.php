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
        return view('livewire.courses.catalog', [
            'courses' => Course::query()
                ->published()
                ->orderBy('title')
                ->get(),
        ]);
    }
}
