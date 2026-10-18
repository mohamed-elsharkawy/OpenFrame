<?php
/**
 * Module: Record Guest Leave — search an open visit, pick the leave
 * time with a date & time picker and save it.
 */

return array(
    'name'        => 'Record Guest Leave',
    'name_ar'     => 'تسجيل مغادرة ضيف',
    'description' => 'Close an open visit by recording its leave time.',
    'icon'        => 'bi-person-dash-fill',
    'order'       => 30,
    'menu'        => true,
    'layout'      => 'app',
    'permission'  => 'guest_leave',
);
