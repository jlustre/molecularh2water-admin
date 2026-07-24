<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FaqController extends Controller
{
    private const STATUSES = [
        'draft' => 'Draft',
        'review' => 'Review',
        'published' => 'Published',
        'archived' => 'Archived',
    ];

    public function index(Request $request): View
    {
        $query = Faq::query()->ordered();

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();

            $query->where(function ($query) use ($search) {
                $query->where('question', 'like', "%{$search}%")
                    ->orWhere('answer', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status') && array_key_exists($request->status, self::STATUSES)) {
            $query->where('status', $request->status);
        }

        $faqs = $query->paginate(10)->withQueryString();

        return view('admin.faqs.index', [
            'faqs' => $faqs,
            'statuses' => self::STATUSES,
            'statusCounts' => Faq::query()
                ->selectRaw('status, count(*) as aggregate')
                ->groupBy('status')
                ->pluck('aggregate', 'status'),
            'totalCount' => Faq::query()->count(),
        ]);
    }

    public function create(): View
    {
        $nextSortOrder = ((int) Faq::query()->max('sort_order')) + 1;

        return view('admin.faqs.create', [
            'faq' => new Faq([
                'status' => 'draft',
                'sort_order' => $nextSortOrder,
            ]),
            'statuses' => self::STATUSES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Faq::create($this->faqAttributes($request));

        return redirect()
            ->route('admin.faqs.index')
            ->with('status', 'FAQ created.');
    }

    public function edit(Faq $faq): View
    {
        return view('admin.faqs.edit', [
            'faq' => $faq,
            'statuses' => self::STATUSES,
        ]);
    }

    public function update(Request $request, Faq $faq): RedirectResponse
    {
        $faq->update($this->faqAttributes($request));

        return redirect()
            ->route('admin.faqs.index')
            ->with('status', 'FAQ updated.');
    }

    public function destroy(Faq $faq): RedirectResponse
    {
        $faq->delete();

        return redirect()
            ->route('admin.faqs.index')
            ->with('status', 'FAQ deleted.');
    }

    public function moveUp(Faq $faq): RedirectResponse
    {
        $previous = Faq::query()
            ->where(function ($query) use ($faq) {
                $query->where('sort_order', '<', $faq->sort_order)
                    ->orWhere(function ($sameOrder) use ($faq) {
                        $sameOrder->where('sort_order', $faq->sort_order)
                            ->where('id', '<', $faq->id);
                    });
            })
            ->ordered()
            ->get()
            ->last();

        if ($previous) {
            $this->swapSortOrder($faq, $previous);
        }

        return redirect()
            ->route('admin.faqs.index')
            ->with('status', 'FAQ order updated.');
    }

    public function moveDown(Faq $faq): RedirectResponse
    {
        $next = Faq::query()
            ->where(function ($query) use ($faq) {
                $query->where('sort_order', '>', $faq->sort_order)
                    ->orWhere(function ($sameOrder) use ($faq) {
                        $sameOrder->where('sort_order', $faq->sort_order)
                            ->where('id', '>', $faq->id);
                    });
            })
            ->ordered()
            ->first();

        if ($next) {
            $this->swapSortOrder($faq, $next);
        }

        return redirect()
            ->route('admin.faqs.index')
            ->with('status', 'FAQ order updated.');
    }

    private function swapSortOrder(Faq $a, Faq $b): void
    {
        $orderA = $a->sort_order;
        $a->update(['sort_order' => $b->sort_order]);
        $b->update(['sort_order' => $orderA]);
    }

    /**
     * @return array{question: string, answer: string, status: string, sort_order: int}
     */
    private function faqAttributes(Request $request): array
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'max:500'],
            'answer' => ['required', 'string'],
            'status' => ['required', Rule::in(array_keys(self::STATUSES))],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
        ]);

        return [
            'question' => $validated['question'],
            'answer' => $validated['answer'],
            'status' => $validated['status'],
            'sort_order' => (int) $validated['sort_order'],
        ];
    }
}
