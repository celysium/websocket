<?php

namespace Celysium\Permission;

use Celysium\Permission\Middleware\CheckPermission;
use Celysium\Permission\Middleware\CheckRole;
use Celysium\Permission\Models\Permission;
use Celysium\Permission\Models\Role;
use Celysium\Permission\Observers\PermissionObserver;
use Celysium\Permission\Observers\RoleObserver;
use Celysium\Permission\Repositories\Permission\PermissionRepository;
use Celysium\Permission\Repositories\Permission\PermissionRepositoryInterface;
use Celysium\Permission\Repositories\Role\RoleRepository;
use Celysium\Permission\Repositories\Role\RoleRepositoryInterface;
use Celysium\Permission\Traits\Permissions;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class WebSocketServiceProvider extends ServiceProvider
{
    public function boot()
    {


        $this->publishes([
            __DIR__ . '/../config/websocket.php' => config_path('websocket.php'),
        ], 'websocket-config');

    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/websocket.php', 'websocket'
        );
    }
}
