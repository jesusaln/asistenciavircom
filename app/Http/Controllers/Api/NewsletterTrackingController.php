<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NewsletterTrack;
use Illuminate\Http\Request;

class NewsletterTrackingController extends Controller
{
    /**
     * Track email open (1x1 pixel)
     */
    public function open($token)
    {
        $track = NewsletterTrack::where('token', $token)->first();

        if ($track && !$track->abierto_at) {
            $track->update(['abierto_at' => now()]);
        }

        // Return a 1x1 transparent GIF
        $img = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
        return response($img)->header('Content-Type', 'image/gif');
    }

    /**
     * Track link click and redirect
     */
    public function click($token)
    {
        $track = NewsletterTrack::with('blogPost')->where('token', $token)->first();

        if ($track) {
            if (!$track->clic_at) {
                $track->update(['clic_at' => now()]);
            }
            // Redirect to the blog post with token for further tracking (button clicks)
            return redirect()->route('public.blog.show', [
                'slug' => $track->blogPost->slug,
                'nt_token' => $token
            ]);
        }

        return redirect('/');
    }

    /**
     * Report interest (button click in post)
     */
    public function reportInterest(Request $request)
    {
        $token = $request->input('token');
        if (!$token)
            return response()->json(['ok' => false]);

        $track = NewsletterTrack::where('token', $token)->first();
        if ($track && !$track->interes_at) {
            $track->update(['interes_at' => now()]);
        }

        return response()->json(['ok' => true]);
    }
}
