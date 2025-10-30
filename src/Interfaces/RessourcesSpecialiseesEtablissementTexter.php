<?php

namespace App\Interfaces;

interface RessourcesSpecialiseesEtablissementTexter
{
    public function generateText(array $formData): string;
}
