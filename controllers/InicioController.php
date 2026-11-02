<?php
class InicioController {
    private UserRepository $userRepository;
    public function __construct(UserRepository $userRepository) {
        $this->userRepository = $userRepository;
    }
    // Aquí podrías cargar una vista: require 'vistas/inicio.php';
    public function index() {        
        $username = $_SESSION["user"];
        $user = $this->userRepository->getByUsername($username);
        if ($user) {
            require '../views/indexView.php';
        } else {
            echo 'User not found';
        }
    }
}