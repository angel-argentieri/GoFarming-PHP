<?php

require_once dirname(__DIR__) . '/MODEL/PlantaModel.php';
require_once dirname(__DIR__) . '/VIEW/JsonView.php';

class IAController {
    private $modelPlanta;
    private $view;

    public function __construct($db) {
        $this->modelPlanta = new PlantaModel($db);
        $this->view = new JsonView();
    }

    public function chat() {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['id_planta']) || !isset($data['pergunta'])) {
            $this->view->send(['error' => 'Dados incompletos.'], 400);
        }

        $planta = $this->modelPlanta->buscarPorId($data['id_planta']);

        if (!$planta) {
            $this->view->send(['error' => 'Planta não encontrada.'], 404);
        }

        if (!GEMINI_KEY) {
            $this->view->send(['error' => 'Chave Gemini não configurada.'], 500);
        }

        $contexto = "Você é um assistente de jardinagem. A planta em questão é {$planta['nome']} ({$planta['especie']}). Responda de forma curta e objetiva em português.";

        $body = json_encode([
            'contents' => [
                ['role' => 'user', 'parts' => [['text' => $contexto]]],
                ['role' => 'user', 'parts' => [['text' => $data['pergunta']]]]
            ]
        ]);

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=' . GEMINI_KEY;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        $resposta = curl_exec($ch);
        curl_close($ch);

        $json = json_decode($resposta, true);
        $texto = $json['candidates'][0]['content']['parts'][0]['text'] ?? 'Não consegui responder agora.';

        $this->view->send(['resposta' => $texto]);
    }
}
