<?php

namespace App\Enums;

enum EnrollmentSource: string
{
    case Purchase = 'purchase';
    case Free = 'free';
    case Coupon100 = 'coupon_100';
    case AdminGrant = 'admin_grant';
}
