<?php

namespace App\Http\Controllers\Admin\Reviews;

use App\Http\Controllers\Controller;
use App\Models\CourseReview;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    public function __invoke(Request $request): View
    {
        abort_unless((bool) config('learning.reviews_enabled'), 404);

        $status = $request->string('status')->toString();
        $source = $request->string('source')->toString();
        $courseId = $request->integer('course_id');
        $search = trim($request->string('q')->toString());

        $reviews = CourseReview::query()
            ->with(['course:id,title,slug', 'user:id,name,email'])
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($source !== '', fn ($query) => $query->where('source', $source))
            ->when($courseId > 0, fn ($query) => $query->where('course_id', $courseId))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner
                        ->whereLike('reviewer_name', '%'.$search.'%')
                        ->orWhereLike('title', '%'.$search.'%')
                        ->orWhereLike('body', '%'.$search.'%')
                        ->orWhereHas('user', function ($userQuery) use ($search): void {
                            $userQuery
                                ->whereLike('name', '%'.$search.'%')
                                ->orWhereLike('email', '%'.$search.'%');
                        });
                });
            })
            ->latest('updated_at')
            ->paginate(25)
            ->withQueryString();

        $statuses = [
            CourseReview::STATUS_PENDING,
            CourseReview::STATUS_APPROVED,
            CourseReview::STATUS_REJECTED,
            CourseReview::STATUS_HIDDEN,
        ];

        $sources = [
            CourseReview::SOURCE_NATIVE,
            CourseReview::SOURCE_UDEMY_MANUAL,
        ];

        $courses = \App\Models\Course::query()
            ->orderBy('title')
            ->get(['id', 'title']);

        return view('admin.reviews.index', [
            'reviews' => $reviews,
            'statuses' => $statuses,
            'sources' => $sources,
            'courses' => $courses,
            'selectedStatus' => $status,
            'selectedSource' => $source,
            'selectedCourseId' => $courseId > 0 ? $courseId : null,
            'search' => $search,
        ]);
    }
}

