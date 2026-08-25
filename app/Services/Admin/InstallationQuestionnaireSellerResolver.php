<?php

namespace App\Services\Admin;

use App\Models\Crm\Customer;
use App\Models\Crm\MemberSale;

class InstallationQuestionnaireSellerResolver
{
    public function resolveId(?string $email): ?int
    {
        $email = strtolower(trim((string) $email));

        if ($email === '') {
            return null;
        }

        $fromCustomer = Customer::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->whereNotNull('assigned_user_id')
            ->latest('id')
            ->value('assigned_user_id');

        if ($fromCustomer) {
            return (int) $fromCustomer;
        }

        $fromSale = MemberSale::query()
            ->whereRaw('LOWER(customer_email) = ?', [$email])
            ->whereNotNull('user_id')
            ->latest('id')
            ->value('user_id');

        return $fromSale ? (int) $fromSale : null;
    }
}
