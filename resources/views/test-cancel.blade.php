<!DOCTYPE html>
<html>
<head>
    <title>Test Cancelar Compra</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    <h1>Test Cancelar Compra C0010</h1>
    <button onclick="cancelarCompra()">Cancelar Compra ID 10</button>
    <div id="result"></div>

    <script>
        async function cancelarCompra() {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            const result = document.getElementById('result');
            
            try {
                result.innerHTML = 'Enviando solicitud...';
                
                const response = await fetch('/compras/10/cancel', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin'
                });
                
                const data = await response.json();
                
                if (response.ok) {
                    result.innerHTML = `<p style="color: green;">✓ Éxito: ${JSON.stringify(data)}</p>`;
                } else {
                    result.innerHTML = `<p style="color: red;">✗ Error: ${JSON.stringify(data)}</p>`;
                }
            } catch (error) {
                result.innerHTML = `<p style="color: red;">✗ Error: ${error.message}</p>`;
            }
        }
    </script>
</body>
</html>
