<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buscar Vehículos</title>

    <style>
        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
            font-family:Arial, Helvetica, sans-serif;
        }

        body{
            background:#081426;
            min-height:100vh;
            padding:40px;
            color:white;
        }

        .container{
            max-width:1100px;
            margin:auto;
        }

        .card{
            background:#13233d;
            border-radius:15px;
            padding:30px;
            margin-bottom:25px;
            box-shadow:0 0 20px rgba(0,0,0,0.3);
        }

        h1{
            margin-bottom:25px;
        }

        .errores{
            background:#8b1e1e;
            padding:12px;
            border-radius:8px;
            margin-bottom:20px;
        }

        .errores ul{
            margin-left:20px;
        }

        .formulario{
            display:flex;
            gap:15px;
            align-items:end;
        }

        .campo{
            flex:1;
        }

        label{
            display:block;
            margin-bottom:8px;
        }

        input{
            width:100%;
            padding:12px;
            border:none;
            border-radius:8px;
            background:#1b3154;
            color:white;
        }

        input:focus{
            outline:none;
            border:2px solid #1ea7ff;
        }

        button{
            padding:12px 25px;
            border:none;
            border-radius:8px;
            background:#18a8ff;
            color:white;
            font-weight:bold;
            cursor:pointer;
        }

        button:hover{
            background:#0f93e2;
        }

        table{
            width:100%;
            border-collapse:collapse;
            margin-top:15px;
        }

        th{
            background:#1b3154;
            padding:14px;
        }

        td{
            padding:12px;
            border-top:1px solid #2c446d;
            text-align:center;
        }

        tr:hover{
            background:#162948;
        }

        .sin-resultados{
            background:#7a1d1d;
            padding:15px;
            border-radius:8px;
            margin-top:15px;
        }

        h2{
            margin-bottom:15px;
        }
    </style>

</head>
<body>

<div class="container">

    <div class="card">

        <h1>Buscar Vehículo</h1>

        @if($errors->any())
            <div class="errores">
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="/vehiculos/buscar" method="GET" class="formulario">

            <div class="campo">
                <label>Placa o Propietario</label>
                <input type="text" name="termino">
            </div>

            <button type="submit">
                Buscar
            </button>

        </form>

    </div>

    @isset($vehiculos)

        <div class="card">

            @if($vehiculos->count() > 0)

                <h2>Resultados</h2>

                <table>

                    <tr>
                        <th>Placa</th>
                        <th>Marca</th>
                        <th>Modelo</th>
                        <th>Propietario</th>
                    </tr>

                    @foreach($vehiculos as $vehiculo)

                        <tr>
                            <td>{{ $vehiculo->placa }}</td>
                            <td>{{ $vehiculo->marca }}</td>
                            <td>{{ $vehiculo->modelo }}</td>
                            <td>
                                {{ \App\Models\Propietario::find($vehiculo->id_propietario)?->nombre }}
                            </td>
                        </tr>

                    @endforeach

                </table>

            @else

                <div class="sin-resultados">
                    No se encontraron resultados.
                </div>

            @endif

        </div>

    @endisset

</div>

</body>
</html>