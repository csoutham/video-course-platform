<?php

namespace App\Http\Controllers\Certificates;

use App\Http\Controllers\Controller;
use App\Models\CourseCertificate;
use Illuminate\Contracts\View\View;

class VerifyController extends Controller
{
    public function __invoke(string $code): View
    {
        abort_unless((bool) config('learning.certificates_enabled'), 404);

        $certificate = CourseCertificate::query()
            ->with('course:id,title,slug')
            ->where('verification_code', strtoupper($code))
            ->first();

        abort_if(! $certificate, 404);

        return view('certificates.verify', [
            'certificate' => $certificate,
        ]);
    }
}
