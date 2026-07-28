<?php

namespace App\Utils;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;

final class FormHelper
{
    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    public function addErrorsToFlashFromForm(FormInterface $form): void
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request instanceof Request) {
            return;
        }

        $session = $request->getSession();

        if (!$session instanceof Session) {
            return;
        }

        foreach ($form->getErrors(true) as $error) {
            $session->getFlashBag()->add('danger', $error->getMessage());
        }
    }
}
