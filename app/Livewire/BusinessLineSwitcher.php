<?php

namespace App\Livewire;

use App\Enums\BusinessLine;
use App\Support\BusinessLineContext;
use Livewire\Component;

class BusinessLineSwitcher extends Component
{
    public string $active = '';

    public function mount(): void
    {
        $this->active = BusinessLineContext::current();
    }

    public function select(string $line): void
    {
        BusinessLineContext::setCurrent($line);
        $this->active = BusinessLineContext::current();
        $this->dispatch('business-line-changed');
    }

    public function render()
    {
        $lines = BusinessLineContext::linesForUser();

        return view('livewire.business-line-switcher', [
            'lines' => $lines,
            'showSwitcher' => count($lines) > 1,
            'lineConfig' => config('business.lines', []),
        ]);
    }
}
