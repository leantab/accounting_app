<?php

namespace App\Livewire\Auth;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.auth')]
class InvitedUserRegister extends Component
{
    public string $name = '';

    public string $lastname = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $customer_id = '';

    public function mount()
    {
        $this->email = request()->query('email', '');
        $this->customer_id = request()->query('customer_id', '');

        if (! $this->email || ! $this->customer_id) {
            abort(403, 'Invalid invitation link.');
        }

        Customer::findOrFail($this->customer_id);
    }

    public function register()
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'customer_id' => ['required', 'exists:customers,id'],
        ]);

        $user = new User;
        $user->name = $validated['name'];
        $user->lastname = $validated['lastname'];
        $user->email = $validated['email'];
        $user->password = Hash::make($validated['password']);
        $user->customer_id = $validated['customer_id'];
        $user->save();

        Auth::login($user);

        return redirect()->route('dashboard');
    }

    public function render()
    {
        return view('livewire.auth.invited-user-register');
    }
}
