<?php
// core/Container.php

class Container {
    private array $services = [];
    private array $instances = [];

    // Registra cómo construir un servicio
    public function set(string $name, callable $resolver): void {
        $this->services[$name] = $resolver;
    }

    // Obtiene un servicio (y lo instancia solo la primera vez que se pide)
    public function get(string $name) {
        if (!isset($this->instances[$name])) {
            if (!isset($this->services[$name])) {
                throw new Exception("Servicio no encontrado: {$name}");
            }
            // Ejecuta el callable pasándole el propio contenedor
            $this->instances[$name] = $this->services[$name]($this);
        }
        return $this->instances[$name];
    }
}