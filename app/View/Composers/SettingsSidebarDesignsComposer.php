<?php

namespace App\View\Composers;

use Illuminate\View\View;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class SettingsSidebarDesignsComposer
{
    public function compose(View $view)
    {
        $designs = [];
        $files = File::glob(resource_path('views/admin/previews/sidebar/design*.blade.php'));
        foreach ($files as $file) {
            $basename = basename($file, '.blade.php');
            if (preg_match('/design(\d+)/', $basename, $matches)) {
                $num = $matches[1];
                $designs[] = [
                    'value' => 'design' . $num,
                    'label' => 'Sidebar Design ' . $num,
                ];
            }
        }
        $view->with('sidebarDesigns', $designs);
    }
}
