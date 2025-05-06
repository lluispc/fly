# Perpetual Adapter con integración Autonomi

Este componente proporciona un adaptador de sistema de archivos para Flysystem en PHP con capacidad de interactuar con la API de Autonomi desarrollada en Python.

## Características

- Adaptador de sistema de archivos compatible con Flysystem (League\Flysystem)
- Soporte para operaciones estándar de archivos y directorios locales
- Integración con la API Autonomi para operaciones avanzadas con directorios:
  - Subir directorios a la red Autonomi (públicos o privados)
  - Descargar directorios desde la red Autonomi
  - Consultar historial de transacciones
  - Obtener estadísticas de uso

## Requisitos

- PHP 7.4 o superior
- Extensión PHP cURL habilitada
- Biblioteca Guzzle HTTP
- Acceso a una instancia de la API de Autonomi (Python)

## Instalación

Asegúrate de tener las dependencias necesarias en tu `composer.json`:

```json
{
    "require": {
        "league/flysystem": "^2.0",
        "guzzlehttp/guzzle": "^7.0"
    }
}
```

Ejecuta:

```bash
composer install
```

## Uso básico

### Inicialización estándar (sin Autonomi)

```php
use App\Adapter\Perpetual\PerpetualAdapter;
use League\Flysystem\Filesystem;

// Para usar solo almacenamiento local
$adapter = new PerpetualAdapter('/ruta/al/directorio/base');
$filesystem = new Filesystem($adapter);

// Operaciones estándar de Flysystem
$filesystem->write('archivo.txt', 'Contenido del archivo');
$contenido = $filesystem->read('archivo.txt');
```

### Inicialización con integración Autonomi

```php
use App\Adapter\Perpetual\PerpetualAdapter;
use League\Flysystem\Filesystem;

// Habilitar integración con Autonomi
$adapter = new PerpetualAdapter(
    '/ruta/al/directorio/base',
    true, // Habilitar integración Autonomi
    'http://localhost:8000' // URL de la API Autonomi (opcional)
);
$filesystem = new Filesystem($adapter);
```

### Uso de funcionalidades Autonomi

```php
// Subir un directorio a Autonomi
$resultado = $adapter->uploadDirectoryToAutonomi('carpeta/a/subir', true); // true para hacerlo público
// $resultado contendrá detalles como el coste y la dirección pública o el data_map

// Descargar un directorio desde Autonomi
// Para directorios privados (usando data_map)
$resultado = $adapter->downloadDirectoryFromAutonomi('destino/local', 'data_map_string', null);

// Para directorios públicos (usando dirección pública)
$resultado = $adapter->downloadDirectoryFromAutonomi('destino/local', null, 'direccion_publica');

// Obtener historial de transacciones
$transacciones = $adapter->getDirectoryTransactions('2023-01-01', 'upload');

// Obtener estadísticas
$estadisticas = $adapter->getDirectoryStats(30); // estadísticas de los últimos 30 días
```

## Configuración de la API de Autonomi (Python)

La API Python de Autonomi debe estar ejecutándose para que la integración funcione. Por defecto, el adaptador intenta conectarse a `http://localhost:8000`.

Para desplegar la API de Autonomi:

1. Asegúrate de tener Python 3.8+ instalado
2. Instala las dependencias del proyecto Autonomi
3. Ejecuta el servidor FastAPI:

```bash
cd /ruta/a/api/autonomi
python run_server.py
```

## Solución de problemas

### La API de Autonomi no responde

- Verifica que el servidor de Python esté en ejecución
- Comprueba que la URL proporcionada en la inicialización sea correcta
- Verifica los logs del servidor Python para posibles errores

### Error en las operaciones de directorio

- Asegúrate de que la ruta a los directorios sea correcta y accesible
- Verifica que tengas permisos suficientes para leer/escribir en los directorios
- Comprueba que la integración con Autonomi esté habilitada (segundo parámetro en true)

## Limitaciones

- Las operaciones con Autonomi son solo para directorios, no para archivos individuales
- La comunicación entre PHP y Python se realiza mediante HTTP, por lo que ambos servicios deben estar accesibles entre sí
- Las operaciones pueden ser lentas para directorios grandes debido a la transferencia de datos

## Licencia

Este software se distribuye bajo licencia propietaria y es para uso exclusivo autorizado. 