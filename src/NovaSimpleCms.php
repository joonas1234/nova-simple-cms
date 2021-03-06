<?php

namespace Joonas1234\NovaSimpleCms;

use Laravel\Nova\Nova;
use Laravel\Nova\Tool;

class NovaSimpleCms extends Tool
{
    /**
     * Perform any tasks that need to happen when the tool is booted.
     *
     * @return void
     */
    public function boot()
    {
        Nova::resources([
            \Joonas1234\NovaSimpleCms\Page::class,
        ]);
        Nova::script('nova-simple-cms', __DIR__.'/../dist/js/tool.js');
        Nova::style('nova-simple-cms', __DIR__.'/../dist/css/tool.css');
    }

    /**
     * Build the view that renders the navigation links for the tool.
     *
     * @return \Illuminate\View\View
     */
    public function renderNavigation()
    {
        return view('nova-simple-cms::navigation');
    }
}
