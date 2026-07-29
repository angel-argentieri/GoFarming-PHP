<?php

require_once dirname(__DIR__) . '/MODEL/PlantaModel.php';
require_once dirname(__DIR__) . '/MODEL/RegaModel.php';
require_once dirname(__DIR__) . '/VIEW/JsonView.php';

class PlantaController {
    private $modelPlanta;
    private $modelRega;
    private $view;

    public function __construct($db) {
        $this->modelPlanta = new PlantaModel($db);
        $this->modelRega = new RegaModel($db);
        $this->view = new JsonView();
    }

    public function listar() {
        session_start();
        $plantas = $this->modelPlanta->buscarPorUsuario($_SESSION['id_usuario']);
        $this->view->send($plantas);
    }

    public function identificar() {
        // Recebe a foto em base64 e chama a Plant.id
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['imagem'])) {
            $this->view->send(['error' => 'Imagem não recebida.'], 400);
        }

        $resposta = $this->chamarPlantId($data['imagem']);

        if (!$resposta) {
            $this->view->send(['error' => 'Erro ao identificar a planta.'], 500);
        }

        $sugestoes = $resposta['result']['classification']['suggestions'] ?? [];

        if (empty($sugestoes)) {
            $this->view->send(['error' => 'Nenhuma planta identificada na imagem.'], 400);
        }

        $melhor = $sugestoes[0];
        $nome = $melhor['details']['common_names'][0] ?? $melhor['name'];
        $especie = $melhor['name'];
        $confianca = round($melhor['probability'] * 100);
        $access_token = $resposta['access_token'];

        // Pergunta pro Gemini a frequência de rega
        $frequencia = $this->perguntarGemini($especie);

        $this->view->send([
            'nome' => $nome,
            'especie' => $especie,
            'confianca' => $confianca,
            'frequencia_rega' => $frequencia,
            'access_token' => $access_token
        ]);
    }

    public function salvar() {
        session_start();
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['nome']) || !isset($data['especie'])) {
            $this->view->send(['error' => 'Dados incompletos.'], 400);
        }

        $id_planta = $this->modelPlanta->criar(
            $_SESSION['id_usuario'],
            $data['nome'],
            $data['especie'],
            $data['foto_url'] ?? null,
            $data['frequencia_rega'] ?? null,
            $data['access_token'] ?? null
        );

        // Extrai o número de regas por semana da string do Gemini
        // Ex: "2 vezes por semana" -> 2
        $frequencia_numero = $this->extrairNumeroFrequencia($data['frequencia_rega'] ?? '');
        $this->modelRega->criarProximasRegas($id_planta, $frequencia_numero);

        $this->view->send(['message' => 'Planta salva no jardim!', 'id' => $id_planta], 201);
    }

    public function deletar() {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['id'])) {
            $this->view->send(['error' => 'ID não informado.'], 400);
        }

        $this->modelPlanta->deletar($data['id']);
        $this->view->send(['message' => 'Planta removida.']);
    }

    private function chamarPlantId($base64) {
        // Remove prefixo data URI se vier com ele
        if (strpos($base64, ',') !== false) {
            $base64 = explode(',', $base64)[1];
        }

        $body = json_encode([
            'images' => ['data:image/jpeg;base64,' . $base64],
            'details' => ['common_names'],
            'language' => 'pt'
        ]);

        $ch = curl_init('https://api.plant.id/v3/identification');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Api-Key: ' . (defined('PLANT_ID_KEY') ? PLANT_ID_KEY : ''),
            'Content-Type: application/json'
        ]);

        // FIX PARA XAMPP/WINDOWS: Ignora verificação estrita de SSL
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $resposta = curl_exec($ch);

        if (curl_errno($ch)) {
            // Se o cURL falhar, retorna o erro do cURL para debugar
            return ['error' => curl_error($ch)];
        }

        curl_close($ch);

        return json_decode($resposta, true);
    }

    private function perguntarGemini($especie) {
        if (!defined('GEMINI_KEY') || !GEMINI_KEY) {
            return 'Frequência não disponível (chave Gemini não configurada)';
        }

        $pergunta = "A planta é {$especie}. Quantas vezes por semana ela deve ser regada? Responda em uma frase curta e objetiva em português.";

        $body = json_encode([
            'contents' => [['parts' => [['text' => $pergunta]]]]
        ]);

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=' . GEMINI_KEY;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

       
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $resposta = curl_exec($ch);
        curl_close($ch);

        $json = json_decode($resposta, true);
        return $json['candidates'][0]['content']['parts'][0]['text'] ?? 'Regar 1 a 2 vezes por semana.';
    }

    private function extrairNumeroFrequencia($texto) {
        
        preg_match('/\d+/', $texto, $matches);
        $numero = isset($matches[0]) ? (int)$matches[0] : 2;
        
        if ($numero < 1) $numero = 1;
        if ($numero > 7) $numero = 7;
        return $numero;
    }
}
