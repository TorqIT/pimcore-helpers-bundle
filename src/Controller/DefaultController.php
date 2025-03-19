<?php

namespace Torq\PimcoreHelpersBundle\Controller;

use Pimcore\Controller\FrontendController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends FrontendController
{
    /**
     * @Route("/torq_pimcore_helpers")
     */
    public function indexAction(Request $request): Response
    {
        return new Response('Hello world from torq_pimcore_helpers');
    }
}
