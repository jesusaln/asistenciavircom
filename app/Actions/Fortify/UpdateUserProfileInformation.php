<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    /**
     * Validate and update the given user's profile information.
     *
     * @param  array<string, mixed>  $input
     */
    public function update(User $user, array $input): void
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'photo' => ['nullable', 'mimes:jpg,jpeg,png', 'max:1024'],
        ])->validateWithBag('updateProfileInformation');

        if (isset($input['photo'])) {
            // Convertir el archivo de Inertia.js a UploadedFile si es necesario
            $photo = $this->convertToUploadedFile($input['photo']);
            if ($photo) {
                $user->updateProfilePhoto($photo);
            }
        }

        if ($input['email'] !== $user->email &&
            $user instanceof MustVerifyEmail) {
            $this->updateVerifiedUser($user, $input);
        } else {
            $user->forceFill([
                'name' => $input['name'],
                'email' => $input['email'],
            ])->save();
        }
    }

    /**
     * Update the given verified user's profile information.
     *
     * @param  array<string, string>  $input
     */
    protected function updateVerifiedUser(User $user, array $input): void
    {
        $user->forceFill([
            'name' => $input['name'],
            'email' => $input['email'],
            'email_verified_at' => null,
        ])->save();

        $user->sendEmailVerificationNotification();
    }

    /**
     * Convertir archivo de Inertia.js a UploadedFile
     *
     * @param mixed $file
     * @return UploadedFile|null
     */
    private function convertToUploadedFile($file)
    {
        // Si ya es un UploadedFile, devolverlo directamente
        if ($file instanceof UploadedFile) {
            return $file;
        }

        // Si es un array (viene desde Inertia.js)
        if (is_array($file) && isset($file['name'], $file['size'], $file['tmp_name'])) {
            return new UploadedFile(
                $file['tmp_name'],
                $file['name'],
                $file['type'] ?? null,
                $file['error'] ?? UPLOAD_ERR_OK,
                true // test
            );
        }

        // Si no se puede convertir, devolver null
        return null;
    }
}
