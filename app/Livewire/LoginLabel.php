<?php

namespace App\Livewire;

use Livewire\Component;

class LoginLabel extends Component
{
    public $text = '';
    public function render()
    {
        return view('livewire.login-label');
    }
}
