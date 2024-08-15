<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Seeder;

class RolesAndPermissionSeeder extends Seeder
{

    private $admin_permissions;
    private $gestor_permissions;
    private $coordinador_permissions;

    public function __construct()
    {
        //Roles de admin
        $this->admin_permissions = [

            'Users.Index',
            'Users.Inactives',
            'Users.Store',
            'Users.Update',
            'Users.Delete',
            'Users.Restore',
            'Users.AssignRolePermissions',
            'Users.RemoveRolePermissions',
            'Cycles.Index',
            'Cycles.Store',
            'Cycles.Update',
            'Cycles.Delete',
            'Focus.Index',
            'Focus.Store',
            'Focus.Update',
            'Focus.Delete',
            'Campaigns.Index',
            'Campaigns.Store',
            'Campaigns.Update',
            'Campaigns.Delete',
            'RolesAndPermissions.Index',
            'RolesAndPermissions.Store',
            'RolesAndPermissions.Update',
            'RolesAndPermissions.Delete',
            'gestor.index',
            'gestor.store',
            'gestor.show',
            'gestor.update',
            'gestor.delete',
            'ConfigurationCampaigns.Index',
            'ConfigurationCampaigns.Store',
            'ConfigurationCampaigns.Update',
            'ConfigurationCampaigns.Delete',
            'Managements.Download',
            'Payments.Index',
            'Payments.Upload',
            'Payments.Store',
            'Payments.Update',
            'Payments.Delete',
            'Assignments.Index',

        ];
       //Roles del gestor
        $this->gestor_permissions = [
            'gestor.index',
            'gestor.store',
            'gestor.show',
            'gestor.update',
            'gestor.delete',
        ];
         //Roles del coordinador
        $this->coordinador_permissions = [

            'ConfigurationCampaigns.Index',
            'ConfigurationCampaigns.Store',
            'ConfigurationCampaigns.Update',
            'ConfigurationCampaigns.Delete',
            'Users.Index',
            'Cycles.Index',
            'Focus.Index',
            'Campaigns.Index',
            'Managements.Download',
            'Payments.Index',
            'Payments.Upload',
            'Payments.Store',
            'Payments.Update',
            'Payments.Delete',
            'Assignments.Index',

        ];
    }

    public function run()
    {
        // Restablecer roles y permisos almacenados en caché
        app()['cache']->forget('spatie.permission.cache');
        $this->createRoleAdmin();
        $this->createRoleGestor();
        $this->createRoleCoordinador();
    }


    private function createRoleAdmin()
    {
        $this->createPermissionIfNotExists($this->admin_permissions);

        $role = Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'sanctum']);
        $this->assignPermissionsToRole($role, $this->admin_permissions);
    }


    private function createRoleGestor()
    {
        $this->createPermissionIfNotExists($this->gestor_permissions);

        $role = Role::firstOrCreate(['name' => 'Gestor', 'guard_name' => 'sanctum']);
        $this->assignPermissionsToRole($role, $this->gestor_permissions);
    }

    private function createRoleCoordinador()
    {
        $this->createPermissionIfNotExists($this->coordinador_permissions);

        $role = Role::firstOrCreate(['name' => 'Coordinador', 'guard_name' => 'sanctum']);

        // Definir los permisos específicos que deseas asignar a Coordinador
        $coordinadorPermissions = [
                'ConfigurationCampaigns.Index',
                'ConfigurationCampaigns.Store',
                'ConfigurationCampaigns.Update',
                'ConfigurationCampaigns.Delete',
                'Users.Index',
                'Cycles.Index',
                'Focus.Index',
                'Campaigns.Index',
                'Managements.Download',
                'Payments.Index',
                'Payments.Upload',
                'Payments.Store',
                'Payments.Update',
                'Payments.Delete',
        ];

        $this->assignPermissionsToRole($role, $coordinadorPermissions);
    }

    private function createPermissionIfNotExists($permissions)
    {
        foreach ($permissions as $permission) {
            $existingPermission = Permission::where(['name' => $permission, 'guard_name' => 'sanctum'])->first();

            if (!$existingPermission) {
                Permission::create(['name' => $permission, 'guard_name' => 'sanctum']);
            }
        }
    }

    private function assignPermissionsToRole($role, $permissions)
    {
        $permissionModels = Permission::whereIn('name', $permissions)->get();
        $role->syncPermissions($permissionModels);
    }

}
