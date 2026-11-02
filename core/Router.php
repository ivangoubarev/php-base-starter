<?php
class Router {
    protected $rutas = [];
    protected $container;
    public function __construct(Container $container) {
        $this->container = $container;
    }

    // Añadimos $protegida por defecto en false
    public function get($ruta, $funcion, $protegida) {
        $this->rutas['GET'][$ruta] = [
            'funcion' => $funcion,
            'protegida' => $protegida
        ];
    }

    public function post($ruta, $funcion, $protegida) {
        $this->rutas['POST'][$ruta] = [
            'funcion' => $funcion,
            'protegida' => $protegida
        ];
    }

    // Resolver la ruta actual
    public function resolver() {
        $rutaActual = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $metodo = $_SERVER['REQUEST_METHOD'];

        $ruta = $this->rutas[$metodo][$rutaActual] ?? false;

        if ($ruta === false) {
            http_response_code(404);
            echo "Error 404";
            return;
        }

        if ($ruta['protegida']) {
            //session_start();
            // Si requiere protección y no hay sesión, redirigir al login
            if (!isset($_SESSION['user'])) {
                header('Location: /login');
                exit; // Detener la ejecución inmediatamente
            }
        }

        // Si la función es un arreglo [Controlador::class, 'metodo']
        $funcion = $ruta['funcion'];

        if (is_array($funcion)) {
            $claseControlador = $funcion[0];
            $funcion[0] = $this->container->get($claseControlador);
        }

        // Ejecutar el controlador y su método
        return call_user_func($funcion);
    }
}