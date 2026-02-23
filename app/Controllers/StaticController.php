<?php

namespace App\Controllers;

use App\Core\Controller;

class StaticController extends Controller
{
    public function faq(): void
    {
        $this->render('static/faq', ['title' => 'FAQ - ' . $this->getSiteName(), 'head_extra_css' => []]);
    }

    public function about(): void
    {
        $this->render('static/about', ['title' => '關於我們 - ' . $this->getSiteName(), 'head_extra_css' => []]);
    }

    public function privacy(): void
    {
        $this->render('static/privacy', ['title' => '隱私條款 - ' . $this->getSiteName(), 'head_extra_css' => []]);
    }

    public function terms(): void
    {
        $this->render('static/terms', ['title' => '服務條款 - ' . $this->getSiteName(), 'head_extra_css' => []]);
    }
}

