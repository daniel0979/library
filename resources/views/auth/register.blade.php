@extends('layouts.app', ['title' => 'Register'])

@section('content')
<div class="max-w-md mx-auto bg-white p-6 rounded-lg shadow">
    <h2 class="text-xl font-semibold mb-4">Register Member</h2>

    @if ($errors->any())
        <div class="mb-4 rounded bg-red-100 p-3 text-red-700 text-sm">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('register.post') }}" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm mb-1">Name</label>
            <input type="text" name="name" value="{{ old('name') }}" class="w-full rounded border px-3 py-2" required>
        </div>
        <div>
            <label class="block text-sm mb-1">Email</label>
            <input type="email" name="email" value="{{ old('email') }}" class="w-full rounded border px-3 py-2" required>
        </div>
        <div>
            <label class="block text-sm mb-1">Phone</label>
            <input type="text" name="phone" value="{{ old('phone') }}" class="w-full rounded border px-3 py-2">
        </div>
        <div>
            <label class="block text-sm mb-1">Password</label>
            <input type="password" name="password" class="w-full rounded border px-3 py-2" required>
        </div>
        <div>
            <label class="block text-sm mb-1">Confirm Password</label>
            <input type="password" name="password_confirmation" class="w-full rounded border px-3 py-2" required>
        </div>
        <button type="submit" class="w-full rounded bg-slate-800 px-3 py-2 text-white">Create Account</button>
    </form>

    <p class="text-sm mt-4">Already registered? <a class="text-blue-600" href="{{ route('login') }}">Login</a></p>
</div>
@endsection
