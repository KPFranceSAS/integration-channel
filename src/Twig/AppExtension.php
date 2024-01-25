<?php

namespace App\Twig;

use ReflectionClass;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

class AppExtension extends AbstractExtension
{

    public function getTests(): array
    {
        return [
            new TwigTest('instanceof', $this->isInstanceof(...)),
        ];
    }

    public function isInstanceof($object, $class): bool 
    {   
        if(!is_object($object)){
            return false;
        }
        
        $reflectionClass = new ReflectionClass($class);
        return $reflectionClass->isInstance($object);
    }
}
