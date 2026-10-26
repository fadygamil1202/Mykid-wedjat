<?php
// One-line description of a code's discount and rules: $pc
$parts = [
    $pc['discount_type'] === 'percent' ? rtrim(rtrim(number_format((float)$pc['discount_value'], 2), '0'), '.') . '%' : money($pc['discount_value']),
    t('promo.applies.' . $pc['applies_to']),
];
if ($pc['min_amount'] !== null) $parts[] = t('admin.promo_min_short', ['amount' => money($pc['min_amount'])]);
echo e(implode(' · ', $parts));
