<?php
/**
 * OpenFrame — Module: Countries / Action: save
 * Create or update a country.
 */

require_framework();
require_any_level(['superadmin', 'admin']);

csrf_check();

$id      = (int) input('id');
$code    = strtoupper(input('code'));
$nameEn  = input('name_en');
$nameAr  = input('name_ar');

$errors = array();
if ($code === '') {
    $errors[] = lang('countries.code_required', 'Country code is required.');
} elseif (strlen($code) !== 2) {
    $errors[] = lang('countries.code_length', 'Country code must be 2 letters.');
}
if ($nameEn === '') {
    $errors[] = lang('countries.name_en_required', 'English name is required.');
}
if ($nameAr === '') {
    $errors[] = lang('countries.name_ar_required', 'Arabic name is required.');
}

if (empty($errors) && $code !== '') {
    $existing = db_row('SELECT id FROM ' . db_table('countries') . ' WHERE code = ? AND id <> ?', array($code, $id));
    if ($existing) {
        $errors[] = lang('countries.code_exists', 'Country code already exists.');
    }
}

if (empty($errors)) {
    $data = array(
        'code'       => $code,
        'name_en'    => $nameEn,
        'name_ar'    => $nameAr,
        'updated_at' => now(),
    );

    if ($id > 0 && db_find('countries', $id)) {
        db_update('countries', $data, 'id = ?', array($id));
        flash('success', lang('countries.updated_ok', 'Country updated.'));
    } else {
        $data['created_at'] = now();
        db_insert('countries', $data);
        flash('success', lang('countries.created_ok', 'Country created.'));
    }
    redirect(url('index.php?module=countries'));
}

flash('danger', implode('<br>', $errors));
redirect(url('index.php?module=countries' . ($id > 0 ? '&edit=' . $id : '')));
