<?php

namespace App\Services\Biometrics;

use App\Contracts\FaceVerificationService;
use App\Models\User;

class MockFaceVerificationService implements FaceVerificationService
{
    public function enroll(User $user, string $selfieAbsolutePath): array
    {
        return [
            'status' => 'enrolled',
            'provider' => 'mock',
            'message' => 'Rostro enrolado en modo de prueba.',
            'match_score' => null,
            'liveness_score' => null,
        ];
    }

    public function verify(User $user, string $referenceAbsolutePath, string $selfieAbsolutePath): array
    {
        return [
            'status' => 'pending',
            'provider' => 'mock',
            'message' => 'Proveedor biométrico no configurado. Validación pendiente de revisión.',
            'match_score' => null,
            'liveness_score' => null,
        ];
    }
}

