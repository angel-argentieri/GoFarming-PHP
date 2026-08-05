<?php

require_once __DIR__ . '/../MODEL/PlantaModel.php';
require_once __DIR__ . '/../VIEW/JsonView.php';

class IAController {
    private $modelPlanta;
    private $view;

    public function __construct($db) {
        $this->modelPlanta = new PlantaModel($db);
        $this->view = new JsonView();
    }

    public function chat() {
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['id_planta']) || empty($data['pergunta'])) {
            $this->view->send(['error' => 'Dados incompletos.'], 400);
            return;
        }

        if (session_status() === PHP_SESSION_NONE) session_start();
        $id_usuario = $_SESSION['id_usuario'] ?? null;

        $planta = $this->modelPlanta->buscarPorIdEUsuario($data['id_planta'], $id_usuario);

        if (!$planta) {
            $this->view->send(['error' => 'Planta não encontrada.'], 404);
            return;
        }

        $contexto = <<<EOD
Você é o assistente virtual especialista em botânica e jardinagem do aplicativo GoFarming.
Seu objetivo é guiar o usuário com orientações práticas, seguras e fáceis de entender sobre o cultivo de plantas.

--- DADOS DA PLANTA ATUAL ---
• Nome popular: {$planta['nome']}
• Nome científico/Espécie: {$planta['especie']}

--- DIRETRIZES DE RESPOSTA ---
1. PERSONA E TOM:
   - Responda como um botânico amigável, encorajador e altamente capacitado.
   - Use português do Brasil claro, acessível para iniciantes e direto ao ponto.

2. CONTEÚDO E CONHECIMENTO:
   - Mantenha o foco estritamente em jardinagem, cultivo e saúde vegetal.
   - Sempre que o usuário perguntar sobre cuidados, aborde (conforme necessário): iluminação ideal (sol direto, meia-sombra, luz indireta), frequência de rega, tipo de solo/substrato, adubação e sinais de pragas ou doenças.

3. FORMATAÇÃO (UI/UX):
   - Formate a resposta usando Markdown limpo (use negritos para termos importantes e marcadores • para listas).
   - Mantenha parágrafos curtos para facilitar a leitura na tela do celular.
   - Não use emojis.
EOD;

        $body = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $contexto . "\n\n--- PERGUNTA DO USUÁRIO ---\n" . $data['pergunta']]
                    ]
                ]
            ]
        ];

        $resultado = $this->executarCurlGemini($body);

        if (isset($resultado['error'])) {
            $this->view->send(['error' => $resultado['error']], 500);
            return;
        }

        $this->view->send(['resposta' => $resultado['texto']]);
    }

    public function obterCuidados() {
        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data['nome_planta'])) {
            $this->view->send(['error' => 'Nome da planta não informado.'], 400);
            return;
        }

        $prompt = "Forneça as informações de cultivo para a planta '{$data['nome_planta']}'. "
            . "Retorne estritamente um JSON com este formato: "
            . "{\"frequencia_rega\": \"A cada X dias\"}. "
            . "Sem formatação markdown e sem texto adicional.";

        $body = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ]
        ];

        $resultado = $this->executarCurlGemini($body);

        if (isset($resultado['texto'])) {
            $jsonLimpo = trim(str_replace(['```json', '```'], '', $resultado['texto']));
            $dadosCuidados = json_decode($jsonLimpo, true);

            if (!empty($dadosCuidados['frequencia_rega'])) {
                $this->view->send($dadosCuidados);
                return;
            }
        }

        $this->view->send(['frequencia_rega' => 'A cada 2 a 3 dias']);
    }

    private function executarCurlGemini($body) {
        $apiKey = defined('GEMINI_KEY') ? GEMINI_KEY : '';
        if (empty($apiKey)) {
            return ['error' => 'Chave Gemini não configurada.'];
        }

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . $apiKey;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS     => json_encode($body),
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);

        $resposta = curl_exec($ch);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return ['error' => 'Erro de conexão cURL: ' . $curlError];
        }

        $json = json_decode($resposta, true);

        if (isset($json['error']['message'])) {
            return ['error' => 'Erro Google API: ' . $json['error']['message']];
        }

        $texto = $json['candidates'][0]['content']['parts'][0]['text'] ?? null;

        if (!$texto) {
            return ['error' => 'Sem resposta de texto do modelo.'];
        }

        return ['texto' => $texto];
    }
}