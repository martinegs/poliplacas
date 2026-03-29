<?php
$sock = @stream_socket_server('tcp://127.0.0.1:8888', $errno, $errstr);
if ($sock) {
    echo "Socket en 127.0.0.1:8888 creado exitosamente\n";
    fclose($sock);
} else {
    echo "Error creando socket: $errstr (código: $errno)\n";
}
