<?php

namespace App\Provider\Id;

/**
 * Provides properly formated IDs
 **/
interface IdProvider {
    public function generate() : string;
}
