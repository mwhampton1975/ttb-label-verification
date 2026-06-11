<?php

interface LlmAdjudicatorInterface
{
    public function adjudicate(array $input): array;
}