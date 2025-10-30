<?php

namespace App\Interfaces;

interface RessourcesSpecialiseesEtablissementNamer
{
    public function generateName(array $formData): string;
}
