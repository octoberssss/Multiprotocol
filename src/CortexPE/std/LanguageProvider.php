<?php


namespace CortexPE\std;


interface LanguageProvider
{
    public function getMessage(string $key, array $params = []): string;
}