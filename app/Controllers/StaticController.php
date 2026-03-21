<?php

namespace App\Controllers;

use App\Core\Controller;

class StaticController extends Controller
{
    public function faq(): void
    {
        $this->render('static/faq', ['title' => $this->titleWithSite('static_faq'), 'head_extra_css' => []]);
    }

    public function about(): void
    {
        $this->render('static/about', ['title' => $this->titleWithSite('static_about'), 'head_extra_css' => []]);
    }

    public function privacy(): void
    {
        $this->render('static/privacy', ['title' => $this->titleWithSite('static_privacy'), 'head_extra_css' => []]);
    }

    public function terms(): void
    {
        $this->render('static/terms', ['title' => $this->titleWithSite('static_terms'), 'head_extra_css' => []]);
    }
}

