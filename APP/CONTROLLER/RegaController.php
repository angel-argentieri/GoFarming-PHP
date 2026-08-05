<?php

require_once dirname(__DIR__) . '/MODEL/RegaModel.php';
require_once dirname(__DIR__) . '/VIEW/JsonView.php';

class RegaController {
    private $model;
    private $view;

    public function __construct($db) {
        $this->model = new RegaModel($db);
        $this->view = new JsonView();
    }

    public function regar() {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['id_rega'])) {
            $this->view->send(['error' => 'ID da rega não informado.'], 400);
            return;
        }

        if (session_status() === PHP_SESSION_NONE) session_start();
        $id_usuario = $_SESSION['id_usuario'] ?? null;

        $ok = $this->model->marcarComoRegada($data['id_rega'], $id_usuario);
        if (!$ok) {
            $this->view->send(['error' => 'Rega não encontrada ou não pertence a você.'], 404);
            return;
        }
        $this->view->send(['message' => 'Rega registrada!']);
    }
}
