<?php
/**
 * OpenFrame — Module: Cities / Action: save
 * Create or update a city.
 */

require_framework();
require_any_level(['superadmin', 'admin']);

csrf_check();

$id         = (int) input('id');
$countryId  = (int) input('country_id');
$nameEn     = input('name_en');
$nameAr     = input('name_ar');

$errors = array();
if ($countryId <= 0) {
    $errors[] = lang('cities.country_required', 'Country is required.');
} elseif (!db_find('countries', $countryId)) {
    $errors[] = lang('cities.country_invalid', 'Selected country does not exist.');
}
if ($nameEn === '') {
    $errors[] = lang('cities.name_en_required', 'English name is required.');
}
if ($nameAr === '') {
    $errors[] = lang('cities.name_ar_required', 'Arabic name is required.');
}

if (empty($errors)) {
    $data = array(
        'country_id' => $countryId,
        'name_en'    => $nameEn,
        'name_ar'    => $nameAr,
        'updated_at' => now(),
    );

    if ($id > 0 && db_find('cities', $id)) {
        db_update('cities', $data, 'id = ?', array($id));
        flash('success', lang('cities.updated_ok', 'City updated.'));
    } else {
        $data['created_at'] = now();
        db_insert('cities', $data);
        flash('success', lang('cities.created_ok', 'City created.'));
    }
    redirect(url('index.php?module=cities'));
}

flash('danger', implode('<br>', $errors));
redirect(url('index.php?module=cities' . ($id > 0 ? '&edit=' . $id : '')));
