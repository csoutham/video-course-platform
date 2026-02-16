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
                ->with('lesson:id,course_id,title')
                ->whereIn('lesson_id', $lessonIdsByCourse->flatten()->values())
                ->orderByDesc('last_viewed_at')
                ->orderByDesc('updated_at')
                ->get()
                ->filter(fn ($row) => $row->lesson !== null);

            $progressRowsByCourse = $progressRows
                ->groupBy(fn ($row) => (int) $row->lesson->course_id);

            $progressByCourse = $lessonIdsByCourse->map(function ($lessonIds, $courseId) use ($progressRowsByCourse): array {
                $rows = $progressRowsByCourse->get((int) $courseId, collect());

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

            $progressLogByCourse = $progressRowsByCourse->map(fn ($rows) => $rows
                ->map(fn ($row) => [
                    'lesson_title' => $row->lesson->title,
                    'status' => $row->status,
                    'watched_seconds' => $row->playback_position_seconds,
                    'duration_seconds' => $row->video_duration_seconds,
                    'percent_complete' => $row->percent_complete,
                    'last_viewed_at' => $row->last_viewed_at,
                    'completed_at' => $row->completed_at,
                ])
                ->values());
        }

        return view('admin.users.show', [
            'targetUser' => $user,
            'entitlements' => $entitlements,
            'progressByCourse' => $progressByCourse,
            'progressLogByCourse' => $progressLogByCourse ?? collect(),
        ]);
    }
}
