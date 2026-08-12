<?php

declare(strict_types=1);

use App\Models\LetterRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Redirect root ke panel admin Filament.
Route::get('/', function () {
    return redirect('/admin');
});

// Halaman publik verifikasi surat via UUID (diakses dari scan QR Code).
// Signed URL + rate limiting (60 req/minute)
Route::get('/letter/verify/{uuid}', function (Request $request, string $uuid) {
    $letterRequest = LetterRequest::where('uuid', $uuid)->firstOrFail();

    $sig = $request->query('sig');

    // Verify HMAC signature
    $expectedSig = hash_hmac('sha256', $uuid, config('app.key'));
    if (! $sig || ! hash_equals($expectedSig, (string) $sig)) {
        abort(403, 'Tanda tangan QR Code tidak valid');
    }

    return view('letter-verify', [
        'letterRequest' => $letterRequest,
    ]);
})->name('letter.verify')->middleware('throttle:60,1');
