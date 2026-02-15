<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseLesson;
use App\Models\User;
use Illuminate\Contracts\View\View;

class UsersController extends Controller
{
    public function index(): View
    {
        $users = User::query()
            ->withCount([
                'entitlements as active_entitlements_count' => fn ($query) => $query->where('status', 'active'),
                'orders',
            ])
            ->latest('id')
            ->paginate(25);

        return view('admin.users.index', [
            'users' => $users,
        ]);
    }

    public function show(User $user): View
    {
        $entitlements = $user->entitlements()
            ->with(['course.modules.lessons', 'order'])
            ->latest('granted_at')
            ->get();

        $courseIds = $entitlements
            ->pluck('course_id')
            ->filter()
            ->unique()
            ->values();

        $progressByCourse = collect();

        if ($courseIds->isNotEmpty()) {
            $lessonIdsByCourse = CourseLesson::query()
                ->whereIn('course_id', $courseIds)
                ->get(['id', 'course_id'])
                ->groupBy('course_id')
                ->map(fn ($rows) => $rows->pluck('id')->values());

            $progressRows = $user->lessonProgress()
                ->whereIn('lesson_id', $lessonIdsByCourse->flatten()->values())
                ->get()
                ->keyBy('lesson_id');

            $progressByCourse = $lessonIdsByCourse->map(function ($lessonIds) use ($progressRows): array {
                $rows = $lessonIds
                    ->map(fn ($lessonId) => $progressRows->get($lessonId))
                    ->filter();

                $completed = $rows->where('status', 'completed')->count();
                $inProgress = $rows->where('status', 'in_progress')->count();
                $avgPercent = $rows->count() > 0
                    ? (int) round($rows->avg('percent_complete') ?? 0)
                    : 0;
                $lastViewedAt = $rows
                    ->pluck('last_viewed_at')
                    ->filter()
                    ->sortDesc()
                    ->first();

                return [
                    'total_lessons' => $lessonIds->count(),
                    'tracked_lessons' => $rows->count(),
                    'completed_lessons' => $completed,
                    'in_progress_lessons' => $inProgress,
                    'avg_percent' => $avgPercent,
                    'last_viewed_at' => $lastViewedAt,
                ];
            });
        }

        return view('admin.users.show', [
            'targetUser' => $user,
            'entitlements' => $entitlements,
            'progressByCourse' => $progressByCourse,
        ]);
    }
}
