<?php
/**
 * Module: Users management — admin only.
 * Add / edit / delete users and assign level (admin or entry) via dropdown.
 * Admin can set/change password on create/edit.
 */

return array(
    'name'        => 'Users',
    'name_ar'     => 'إدارة المستخدمين',
    'description' => 'Add, edit, delete users and define their level (admin / entry).',
    'icon'        => 'bi-people',
    'order'       => 65,
    'menu'        => false, // reachable from the Dashboard buttons
    'layout'      => 'app',
    'permission'  => 'users_manage',
);
