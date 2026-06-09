<?php

namespace App\Contracts;

use App\Models\User;

interface FaceVerificationService
{
    /**
     * Enrolls a new face reference for the given user.
     *
     * @param string $selfieAbsolutePath Absolute path on disk for provider processing.
     * @return array{status:string,provider:string,message:string,match_score:float|null,liveness_score:float|null}
     */
    public function enroll(User $user, string $selfieAbsolutePath): array;

    /**
     * Verifies a selfie against an existing user face reference.
     *
     * @param string $referenceAbsolutePath Absolute path to user reference image.
     * @param string $selfieAbsolutePath Absolute path to current selfie.
     * @return array{status:string,provider:string,message:string,match_score:float|null,liveness_score:float|null}
     */
    public function verify(User $user, string $referenceAbsolutePath, string $selfieAbsolutePath): array;
}

