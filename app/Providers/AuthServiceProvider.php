<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        //Esta función recibe el usuario directamente laravel se lo asigna y comprueba si ese usuario esEspecialista que es un método que esta en el modelo user, si es asi sigue la ejecución en la route que se utiliza si no retorna un error  de no tiene permiso para entrar.
        Gate::define('dashboard-especialista', function (User $user) {
            return $user->esEspecialista()
                ? Response::allow()
                : Response::deny('No tiene permiso para entrar.');
        });

        Gate::define('dashboard-paciente', function (User $user) {
            return !$user->esEspecialista()
                ? Response::allow()
                : Response::deny('No tiene permiso para entrar.');
        });
    }


}
