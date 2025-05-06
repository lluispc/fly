# Perpetual Filesystem Adapter

Este es un adaptador personalizado para Flysystem que implementa la interfaz `FilesystemAdapter`. 

## Requisitos

- PHP 8.0 o superior
- Extensión fileinfo (para detección de tipos MIME)
- [league/flysystem](https://flysystem.thephpleague.com/) v3.x

## Instalación

```bash
composer require league/flysystem
```

## Uso

```php
use App\Adapter\Perpetual\PerpetualAdapter;
use League\Flysystem\Filesystem;

// Ruta donde se almacenarán los archivos
$storagePath = '/ruta/al/directorio/de/almacenamiento';

// Crear una instancia del adaptador
$adapter = new PerpetualAdapter($storagePath);

// Crear el sistema de archivos con nuestro adaptador
$filesystem = new Filesystem($adapter);

// Ahora puedes usar cualquier operación disponible en Flysystem
$filesystem->write('archivo.txt', 'Contenido del archivo');
$contenido = $filesystem->read('archivo.txt');
```

## Características

El adaptador Perpetual soporta todas las operaciones básicas de archivos:

- Lectura y escritura de archivos
- Manejo de streams para archivos grandes
- Creación, eliminación y listado de directorios
- Copiar y mover archivos
- Gestión de metadatos (tamaño, fecha de modificación, tipo MIME)
- Control de visibilidad (público/privado)

## Ejemplo de uso

Puedes encontrar un ejemplo completo de uso en el archivo `src/example.php`.

Para ejecutar el ejemplo:

```bash
php src/example.php
```

## Licencia

MIT 