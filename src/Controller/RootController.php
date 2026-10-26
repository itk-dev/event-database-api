<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;

class RootController extends AbstractController
{
    #[\Symfony\Component\Routing\Attribute\Route('/', name: 'app_redirect')]
    public function index(): RedirectResponse
    {
        return $this->redirectToRoute('api_doc');
    }
}
