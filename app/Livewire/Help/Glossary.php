<?php

namespace App\Livewire\Help;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Glossary — PANDA')]
class Glossary extends Component
{
    public function render()
    {
        return view('livewire.help.glossary');
    }
}
