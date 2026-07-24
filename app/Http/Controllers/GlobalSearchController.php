<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Crm\Customer;
use App\Models\Crm\Lead;
use App\Models\Crm\Prospect;
use App\Models\Faq;
use App\Models\MediaItem;
use App\Models\User;
use App\Models\WebsiteFormSubmission;
use App\Support\Crm\CrmRoutes;
use App\Support\Crm\CrmScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class GlobalSearchController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        abort_unless($user, 403);

        $q = trim($request->string('q')->toString());
        $crmPrefix = CrmRoutes::prefixForUser($user);
        $results = [
            'leads' => collect(),
            'prospects' => collect(),
            'customers' => collect(),
            'faqs' => collect(),
            'blog' => collect(),
            'media' => collect(),
            'users' => collect(),
            'forms' => collect(),
        ];

        if ($q !== '' && strlen($q) >= 2) {
            if ($user->hasPermission('leads.view') && Schema::hasTable('leads')) {
                $results['leads'] = CrmScope::contacts(Lead::query())
                    ->lifecycle('lead')
                    ->where(function ($query) use ($q) {
                        $query->where('first_name', 'like', "%{$q}%")
                            ->orWhere('last_name', 'like', "%{$q}%")
                            ->orWhere('email', 'like', "%{$q}%")
                            ->orWhere('phone', 'like', "%{$q}%");
                    })
                    ->limit(8)
                    ->get();
            }

            if ($user->hasPermission('prospects.view') && Schema::hasTable('prospects')) {
                $results['prospects'] = CrmScope::contacts(Prospect::query())
                    ->where(function ($query) use ($q) {
                        $query->where('first_name', 'like', "%{$q}%")
                            ->orWhere('last_name', 'like', "%{$q}%")
                            ->orWhere('email', 'like', "%{$q}%")
                            ->orWhere('phone', 'like', "%{$q}%");
                    })
                    ->limit(8)
                    ->get();
            }

            if ($user->hasPermission('clients.view') && Schema::hasTable('customers')) {
                $results['customers'] = CrmScope::contacts(Customer::query())
                    ->where(function ($query) use ($q) {
                        $query->where('first_name', 'like', "%{$q}%")
                            ->orWhere('last_name', 'like', "%{$q}%")
                            ->orWhere('email', 'like', "%{$q}%")
                            ->orWhere('phone', 'like', "%{$q}%");
                    })
                    ->limit(8)
                    ->get();
            }

            if ($user->hasPermission('faqs.manage') && Schema::hasTable('faqs')) {
                $results['faqs'] = Faq::query()
                    ->where(function ($query) use ($q) {
                        $query->where('question', 'like', "%{$q}%")
                            ->orWhere('answer', 'like', "%{$q}%");
                    })
                    ->limit(8)
                    ->get();
            }

            if ($user->hasPermission('blog.manage') && Schema::hasTable('blog_posts')) {
                $results['blog'] = BlogPost::query()
                    ->where(function ($query) use ($q) {
                        $query->where('title', 'like', "%{$q}%")
                            ->orWhere('excerpt', 'like', "%{$q}%")
                            ->orWhere('body', 'like', "%{$q}%");
                    })
                    ->limit(8)
                    ->get();
            }

            if ($user->hasPermission('media.view') && Schema::hasTable('media_items')) {
                $results['media'] = MediaItem::query()
                    ->where(function ($query) use ($q) {
                        $query->where('title', 'like', "%{$q}%")
                            ->orWhere('description', 'like', "%{$q}%");
                    })
                    ->limit(8)
                    ->get();
            }

            if ($user->hasPermission('users.view') && Schema::hasTable('users')) {
                $results['users'] = User::query()
                    ->where(function ($query) use ($q) {
                        $query->where('name', 'like', "%{$q}%")
                            ->orWhere('email', 'like', "%{$q}%");
                    })
                    ->limit(8)
                    ->get();
            }

            if ($user->hasPermission('website-forms.view') && Schema::hasTable('website_form_submissions')) {
                $results['forms'] = WebsiteFormSubmission::query()
                    ->where(function ($query) use ($q) {
                        $query->where('name', 'like', "%{$q}%")
                            ->orWhere('email', 'like', "%{$q}%")
                            ->orWhere('phone', 'like', "%{$q}%")
                            ->orWhere('message', 'like', "%{$q}%");
                    })
                    ->limit(8)
                    ->get();
            }
        }

        return view('search.results', [
            'q' => $q,
            'results' => $results,
            'crmPrefix' => $crmPrefix,
            'total' => collect($results)->sum(fn ($items) => $items->count()),
        ]);
    }
}
