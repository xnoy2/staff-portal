<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Attendance
            'view_own_attendance', 'view_all_attendance',
            'create_time_entry', 'approve_time_entry', 'bulk_time_entry',
            // Jobs
            'view_jobs', 'manage_jobs', 'advance_job_phase',
            // Projects
            'view_projects', 'manage_projects',
            // Staff
            'view_staff', 'manage_staff',
            // Vans
            'view_vans', 'manage_vans',
            // Inventory
            'view_inventory', 'manage_inventory',
            // Reports
            'view_reports',
            // Audit
            'view_audit_log',
            // Settings
            'manage_settings',
            // QR
            'scan_qr', 'view_own_qr',
            // Schedule
            'view_schedule', 'manage_schedule',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $roles = [
            'staff' => [
                'view_own_attendance', 'create_time_entry',
                'view_jobs', 'view_own_qr',
            ],
            'site_head' => [
                'view_own_attendance', 'view_all_attendance',
                'create_time_entry', 'bulk_time_entry',
                'view_jobs', 'advance_job_phase',
                'scan_qr', 'view_own_qr',
                'view_schedule',
            ],
            'manager' => [
                'view_own_attendance', 'view_all_attendance',
                'create_time_entry', 'approve_time_entry', 'bulk_time_entry',
                'view_jobs', 'manage_jobs', 'advance_job_phase',
                'view_projects', 'manage_projects',
                'view_staff', 'manage_staff',
                'view_vans', 'manage_vans',
                'view_inventory', 'manage_inventory',
                'view_reports',
                'scan_qr', 'view_own_qr',
                'view_schedule', 'manage_schedule',
            ],
            'admin' => $permissions,
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            $role->syncPermissions($rolePermissions);
        }
    }
}
