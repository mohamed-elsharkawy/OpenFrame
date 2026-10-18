<?php
/**
 * Module: Guest entry report based on users — admin only.
 * How many guests each user registered inside the selected period.
 */

return array(
    'name'        => 'Entries by user',
    'name_ar'     => 'تقرير حسب المستخدمين',
    'description' => 'Guest entries per user inside a period (admin).',
    'icon'        => 'bi-people-fill',
    'order'       => 55,
    'menu'        => false, // reachable from the Dashboard buttons
    'layout'      => 'app',
    'permission'  => 'report_users',
);
