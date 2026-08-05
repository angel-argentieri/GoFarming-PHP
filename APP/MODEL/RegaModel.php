<?php

class RegaModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function criarProximasRegas($id_planta, $frequencia_por_semana) {
        $intervalo = round(7 / $frequencia_por_semana);
        $data = new DateTime();

        for ($i = 0; $i < 30; $i += $intervalo) {
            $data_prevista = (clone $data)->modify("+{$i} days")->format('Y-m-d');

            $stmt = $this->db->prepare("
                INSERT INTO Regas (id_planta, data_prevista)
                VALUES (:id_planta, :data_prevista)
            ");
            $stmt->bindValue(':id_planta', $id_planta);
            $stmt->bindValue(':data_prevista', $data_prevista);
            $stmt->execute();
        }
    }

    public function marcarComoRegada($id_rega, $id_usuario) {
        $stmt = $this->db->prepare("
            UPDATE Regas r
            JOIN Plantas p ON p.id = r.id_planta
            SET r.status = 'concluida', r.data_regada = NOW()
            WHERE r.id = :id_rega
            AND p.id_usuario = :id_usuario
        ");
        $stmt->bindValue(':id_rega', $id_rega);
        $stmt->bindValue(':id_usuario', $id_usuario);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    public function buscarPendentesHoje() {
        $stmt = $this->db->prepare("
            SELECT r.id, r.id_planta, p.nome AS nome_planta, u.email, u.nome AS nome_usuario
            FROM Regas r
            JOIN Plantas p ON p.id = r.id_planta
            JOIN Usuarios u ON u.id = p.id_usuario
            WHERE r.data_prevista = CURDATE()
            AND r.status = 'pendente'
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
