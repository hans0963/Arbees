<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Arbee's Bakery</title>
    @vite('resources/css/app.css')
</head>
<body class="bg-blue-700 min-h-screen flex items-center justify-center">

<div class="text-center w-full max-w-md">

    <!-- Header -->
    <div class="mb-8">
        <div class="inline-block px-4 py-1 border border-white text-white rounded-full text-sm mb-4">
            Borbolla
        </div>

        <h1 class="text-white text-4xl font-bold">ARBEE'S BAKERY</h1>
        <p class="text-blue-100 mt-2">Fresh baked goods made with love</p>
    </div>

    <!-- Card -->
    <div class="bg-gray-100 rounded-2xl shadow-lg p-6">

        <h2 class="text-lg font-semibold mb-1">Create Account</h2>
        <p class="text-gray-500 text-sm mb-4">Register as a customer</p>

        <form method="POST" action="{{ route('register') }}">
            @csrf

            <!-- Name -->
            <div class="mb-3 text-left">
                <label>Full Name</label>
                <input type="text" name="name" required
                    class="w-full mt-1 px-3 py-2 rounded-lg bg-gray-200">
            </div>

            <!-- Email -->
            <div class="mb-3 text-left">
                <label>Email</label>
                <input type="email" name="email" required
                    class="w-full mt-1 px-3 py-2 rounded-lg bg-gray-200">
            </div>

            <!-- Password -->
            <div class="mb-3 text-left">
                <label>Password</label>
                <input type="password" name="password" required
                    class="w-full mt-1 px-3 py-2 rounded-lg bg-gray-200">
            </div>

            <!-- Confirm -->
            <div class="mb-4 text-left">
                <label>Confirm Password</label>
                <input type="password" name="password_confirmation" required
                    class="w-full mt-1 px-3 py-2 rounded-lg bg-gray-200">
            </div>

            <button class="w-full bg-blue-700 text-white py-2 rounded-lg">
                Create Account
            </button>
        </form>

        <!-- Switch -->
        <p class="text-sm text-gray-500 mt-4">
            Already have an account?
            <a href="{{ route('login') }}" class="text-blue-700 font-medium">
                Login
            </a>
        </p>

    </div>
</div>

</body>
</html>