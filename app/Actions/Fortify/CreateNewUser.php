<?php

namespace App\Actions\Fortify;

use App\Models\Empresa;
use App\Models\EmpresaConfiguracion;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Jetstream\Jetstream;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => $this->passwordRules(),
            'empresa_name' => ['required', 'string', 'max:255'],
            'empresa_sector' => ['nullable', 'string', 'max:255'],
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
        ])->validate();

        return DB::transaction(function () use ($input) {
            $isFirstUser = !User::exists();

            // Create Empresa first
            $empresa = Empresa::create([
                'nombre_razon_social' => $input['empresa_name'],
                'email' => $input['email'],
                'sector' => $input['empresa_sector'] ?? 'general',
                'activo' => true,
            ]);

            // Create EmpresaConfiguracion
            EmpresaConfiguracion::create([
                'empresa_id' => $empresa->id,
                'nombre_empresa' => $input['empresa_name'],
                'color_principal' => '#FF6B35', // Default color
                'color_secundario' => '#D97706',
                'color_terciario' => '#B45309',
            ]);

            $user = User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => Hash::make($input['password']),
                'empresa_id' => $empresa->id,
                'activo' => $isFirstUser ? true : false, // El primero se activa automáticamente
            ]);

            if ($isFirstUser) {
                // Marcar como verificado de inmediato para evitar bloqueos del middleware 'verified'
                $user->email_verified_at = now();
                $user->save();

                // Asignar rol super-admin al primer usuario
                $user->assignRole('super-admin');
            } else {
                // Assign default role for new tenants
                $user->assignRole('admin'); // Or 'owner' if you have that role
            }

            $this->createTeam($user, $empresa);

            return $user;
        });
    }

    /**
     * Create a personal team for the user within the empresa.
     */
    protected function createTeam(User $user, Empresa $empresa): void
    {
        $user->ownedTeams()->save(Team::forceCreate([
            'user_id' => $user->id,
            'empresa_id' => $empresa->id,
            'name' => explode(' ', $user->name, 2)[0] . "'s Team",
            'personal_team' => true,
        ]));
    }
}
