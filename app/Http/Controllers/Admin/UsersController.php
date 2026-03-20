<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseLesson;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Contracts\View\View;

class UsersController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class, 'email')],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'is_admin' => ['nullable', 'boolean'],
        ]);

        User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'is_admin' => (bool) ($validated['is_admin'] ?? false),
        ]);

        return to_route('admin.users.index')->with('status', 'User created.');
    }

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
                ->latest('last_viewed_at')
                ->latest('updated_at')
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
