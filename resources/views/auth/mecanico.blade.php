<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Mecánico - SGA</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-lg p-8 text-center">
            <div class="w-20 h-20 bg-emerald-500 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fa-solid fa-wrench text-3xl text-white"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Bienvenido Mecánico</h1>
            <p class="text-gray-600 mb-6">{{ Auth::user()->username }}</p>
            <p class="text-gray-500 text-sm mb-6">Has iniciado sesión correctamente en el sistema SGA.</p>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-6 py-2 rounded-lg transition">
                    Cerrar Sesión
                </button>
            </form>
        </div>
    </div>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</body>

</html>
