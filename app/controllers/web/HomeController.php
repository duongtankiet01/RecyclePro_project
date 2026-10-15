<?php
declare(strict_types=1);

class HomeController extends BaseController
{
    public function index(): void
    {
        $this->render('home/index');
    }
}
