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
