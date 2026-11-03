CREATE DATABASE IF NOT EXISTS `sgo` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `sgo`;

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `anexos_ocorrencia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `anexos_ocorrencia` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ocorrencia_id` int(11) NOT NULL,
  `nome_original` varchar(255) DEFAULT NULL,
  `nome_arquivo` varchar(255) NOT NULL,
  `tipo_mime` varchar(100) DEFAULT NULL,
  `tamanho` int(11) DEFAULT NULL,
  `uploader_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ocorrencia_id` (`ocorrencia_id`),
  KEY `fk_anx_uploader` (`uploader_id`),
  CONSTRAINT `fk_anx_ocorrencia` FOREIGN KEY (`ocorrencia_id`) REFERENCES `ocorrencias` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_anx_uploader` FOREIGN KEY (`uploader_id`) REFERENCES `utilizadores` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `bairros`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bairros` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `distrito` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_bairro_distrito` (`nome`,`distrito`)
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `esquadras`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `esquadras` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(150) NOT NULL,
  `codigo` varchar(20) DEFAULT NULL,
  `tipo` enum('esquadra','posto') NOT NULL DEFAULT 'esquadra',
  `distrito` varchar(100) DEFAULT NULL,
  `bairro` varchar(100) DEFAULT NULL,
  `endereco` text DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `comandante_nome` varchar(100) DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `esquadras_bairros`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `esquadras_bairros` (
  `esquadra_id` int(11) NOT NULL,
  `bairro_id` int(11) NOT NULL,
  PRIMARY KEY (`esquadra_id`,`bairro_id`),
  KEY `fk_eb_bairro` (`bairro_id`),
  CONSTRAINT `fk_eb_bairro` FOREIGN KEY (`bairro_id`) REFERENCES `bairros` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_eb_esquadra` FOREIGN KEY (`esquadra_id`) REFERENCES `esquadras` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `historico_ocorrencia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `historico_ocorrencia` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ocorrencia_id` int(11) NOT NULL,
  `utilizador_id` int(11) DEFAULT NULL,
  `acao` varchar(150) NOT NULL,
  `descricao` text DEFAULT NULL,
  `estado_anterior` varchar(50) DEFAULT NULL,
  `estado_novo` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ocorrencia_id` (`ocorrencia_id`),
  KEY `utilizador_id` (`utilizador_id`),
  CONSTRAINT `fk_hist_ocorrencia` FOREIGN KEY (`ocorrencia_id`) REFERENCES `ocorrencias` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_hist_util` FOREIGN KEY (`utilizador_id`) REFERENCES `utilizadores` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=304 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `log_sistema`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `log_sistema` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `utilizador_id` int(11) DEFAULT NULL,
  `identificador_tentativa` varchar(150) DEFAULT NULL,
  `username_tentativa` varchar(100) DEFAULT NULL,
  `tipo` enum('acesso','gestao','ocorrencia') NOT NULL DEFAULT 'acesso',
  `acao` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_utilizador` (`utilizador_id`),
  KEY `idx_tipo_acao` (`tipo`,`acao`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_log_util` FOREIGN KEY (`utilizador_id`) REFERENCES `utilizadores` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=244 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notificacoes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notificacoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `utilizador_id` int(11) NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `mensagem` text DEFAULT NULL,
  `tipo` enum('info','aviso','alerta','sucesso') NOT NULL DEFAULT 'info',
  `lida` tinyint(1) NOT NULL DEFAULT 0,
  `lida_em` datetime DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `utilizador_id` (`utilizador_id`),
  KEY `idx_utilizador_lida` (`utilizador_id`,`lida`),
  CONSTRAINT `fk_not_util` FOREIGN KEY (`utilizador_id`) REFERENCES `utilizadores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ocorrencias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ocorrencias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numero` varchar(30) NOT NULL,
  `numero_auto` varchar(30) DEFAULT NULL,
  `tipo_id` int(11) NOT NULL,
  `tipo_peca` enum('auto_noticia','auto_denuncia','participacao','informacao') NOT NULL DEFAULT 'auto_noticia',
  `esquadra_id` int(11) NOT NULL,
  `agente_id` int(11) NOT NULL,
  `data_ocorrencia` date NOT NULL,
  `hora_ocorrencia` time NOT NULL,
  `bairro` varchar(100) DEFAULT NULL,
  `rua` varchar(150) DEFAULT NULL,
  `referencia_local` text DEFAULT NULL,
  `descricao_factos` text NOT NULL,
  `objetos_recuperados` text DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `prioridade` enum('baixa','media','alta','critica') NOT NULL DEFAULT 'media',
  `estado` enum('aberta','em_investigacao','resolvida','arquivada') NOT NULL DEFAULT 'aberta',
  `validacao_estado` enum('pendente','aprovada','rejeitada') NOT NULL DEFAULT 'pendente',
  `supervisor_id` int(11) DEFAULT NULL,
  `data_validacao` datetime DEFAULT NULL,
  `motivo_rejeicao` text DEFAULT NULL,
  `investigador_id` int(11) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero` (`numero`),
  UNIQUE KEY `numero_auto` (`esquadra_id`,`tipo_peca`,`numero_auto`),
  KEY `tipo_id` (`tipo_id`),
  KEY `esquadra_id` (`esquadra_id`),
  KEY `agente_id` (`agente_id`),
  KEY `fk_oc_supervisor` (`investigador_id`),
  KEY `idx_estado` (`estado`),
  KEY `idx_validacao_esquadra` (`validacao_estado`,`esquadra_id`),
  KEY `idx_validacao_supervisor` (`supervisor_id`),
  KEY `idx_data_ocorrencia` (`data_ocorrencia`),
  KEY `idx_prioridade` (`prioridade`),
  KEY `idx_esquadra_investigador` (`esquadra_id`,`investigador_id`),
  CONSTRAINT `fk_oc_agente` FOREIGN KEY (`agente_id`) REFERENCES `utilizadores` (`id`),
  CONSTRAINT `fk_oc_esquadra` FOREIGN KEY (`esquadra_id`) REFERENCES `esquadras` (`id`),
  CONSTRAINT `fk_oc_investigador` FOREIGN KEY (`investigador_id`) REFERENCES `utilizadores` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_oc_validacao_supervisor` FOREIGN KEY (`supervisor_id`) REFERENCES `utilizadores` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_oc_tipo` FOREIGN KEY (`tipo_id`) REFERENCES `tipos_ocorrencia` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=313 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `participantes_ocorrencia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `participantes_ocorrencia` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ocorrencia_id` int(11) NOT NULL,
  `tipo` enum('vitima','suspeito','testemunha','detido') NOT NULL,
  `nome` varchar(100) DEFAULT NULL,
  `idade` int(3) DEFAULT NULL,
  `sexo` enum('M','F','outro') DEFAULT NULL,
  `estado_civil` enum('solteiro','casado','divorciado','viuvo','uniao_de_facto') DEFAULT NULL,
  `data_nascimento` date DEFAULT NULL,
  `nome_pai` varchar(100) DEFAULT NULL,
  `nome_mae` varchar(100) DEFAULT NULL,
  `naturalidade` varchar(100) DEFAULT NULL,
  `nacionalidade` varchar(100) DEFAULT 'Mo├ºambicana',
  `profissao` varchar(100) DEFAULT NULL,
  `documento` varchar(50) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `endereco` text DEFAULT NULL,
  `quarteirao` varchar(20) DEFAULT NULL,
  `numero_casa` varchar(20) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `ocorrencia_id` (`ocorrencia_id`),
  CONSTRAINT `fk_part_ocorrencia` FOREIGN KEY (`ocorrencia_id`) REFERENCES `ocorrencias` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=287 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipos_ocorrencia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tipos_ocorrencia` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `codigo` varchar(20) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `prioridade_default` enum('baixa','media','alta','critica') NOT NULL DEFAULT 'media',
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nome` (`nome`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `utilizadores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `utilizadores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `patente` varchar(100) DEFAULT NULL,
  `numero_mecanografico` varchar(50) DEFAULT NULL,
  `perfil` enum('admin','supervisor','agente','investigador') NOT NULL DEFAULT 'agente',
  `esquadra_id` int(11) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `ultimo_acesso` datetime DEFAULT NULL,
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_token_expires` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_utilizadores_email` (`email`),
  KEY `esquadra_id` (`esquadra_id`),
  CONSTRAINT `fk_util_esquadra` FOREIGN KEY (`esquadra_id`) REFERENCES `esquadras` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=77 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;


INSERT INTO `bairros` VALUES (23,'25 de Junho A','KaMaxaquene'),(24,'25 de Junho B','KaMaxaquene'),(15,'Aeroporto A','KaMaxaquene'),(28,'Albazine','KaMavota'),(25,'Bagamoyo','KaMaxaquene'),(1,'Central A','KaMpfumo'),(2,'Central B','KaMpfumo'),(3,'Central C','KaMpfumo'),(9,'Chamanculo A','KaMaxaquene'),(10,'Chamanculo B','KaMaxaquene'),(11,'Chamanculo C','KaMaxaquene'),(7,'Coop','KaMpfumo'),(27,'Ferrovi├írio','KaMavota'),(36,'For├ºa A├®rea','KaMavota'),(33,'FPLM','KaMavota'),(38,'George Dimitrov','KaMubukwana'),(31,'Hulene A','KaMavota'),(32,'Hulene B','KaMavota'),(43,'Inhagoia A','KaMubukwana'),(44,'Inhagoia B','KaMubukwana'),(42,'Jardim','KaMubukwana'),(29,'Laulane','KaMavota'),(41,'Lu├¡s Cabral','KaMubukwana'),(16,'Mafalala','KaMaxaquene'),(39,'Magoanine','KaMubukwana'),(30,'Mahotas','KaMavota'),(26,'Malanga','KaMaxaquene'),(8,'Malhangalene','KaMpfumo'),(40,'Malhazine','KaMubukwana'),(34,'Mavalane A','KaMavota'),(35,'Mavalane B','KaMavota'),(17,'Maxaquene A','KaMaxaquene'),(18,'Maxaquene B','KaMaxaquene'),(19,'Maxaquene C','KaMaxaquene'),(20,'Maxaquene D','KaMaxaquene'),(13,'Minkadjuine','KaMaxaquene'),(14,'Munhuana','KaMaxaquene'),(21,'Polana Cani├ºo A','KaMaxaquene'),(22,'Polana Cani├ºo B','KaMaxaquene'),(4,'Polana Cimento A','KaMpfumo'),(5,'Polana Cimento B','KaMpfumo'),(6,'Sommerschield','KaMpfumo'),(12,'Xipamanine','KaMaxaquene'),(37,'Zimpeto','KaMavota');
INSERT INTO `esquadras` VALUES (1,'1.ª Esquadra','E-01','esquadra','KaMpfumo','Central B e Central C',NULL,NULL,NULL,NULL,1,'2026-07-29 12:19:11','2026-07-29 12:19:11'),(2,'2.ª Esquadra','E-02','esquadra','KaMpfumo','Polana Cimento A e Polana Cimento B',NULL,NULL,NULL,NULL,1,'2026-07-29 12:19:11','2026-07-29 12:19:11'),(3,'3.ª Esquadra','E-03','esquadra','KaMpfumo','Sommerschield e Coop',NULL,NULL,NULL,NULL,1,'2026-07-29 12:19:11','2026-07-29 12:19:11'),(4,'6.ª Esquadra','E-06','esquadra','KaMpfumo','Central A e Malhangalene',NULL,NULL,NULL,NULL,1,'2026-07-29 12:19:11','2026-07-29 12:19:11'),(5,'9.ª Esquadra','E-09','esquadra','KaMaxaquene','Chamanculo A e B, Xipamanine, Minkadjuine, Munhuana e Aeroporto A',NULL,NULL,NULL,NULL,1,'2026-07-29 12:19:11','2026-07-29 12:19:11'),(6,'12.ª Esquadra','E-12','esquadra','KaMaxaquene','Mafalala, Maxaquene e Polana Caniço',NULL,NULL,NULL,NULL,1,'2026-07-29 12:19:11','2026-07-29 12:19:11'),(7,'14.ª Esquadra','E-14','esquadra','KaMavota','Ferroviário, Albazine, Laulane e Mahotas',NULL,NULL,NULL,NULL,1,'2026-07-29 12:19:11','2026-07-29 12:19:11'),(8,'15.ª Esquadra','E-15','esquadra','KaMubukwana','George Dimitrov, Magoanine e Malhazine',NULL,NULL,NULL,NULL,1,'2026-07-29 12:19:11','2026-07-29 12:19:11'),(9,'16.ª Esquadra','E-16','esquadra','KaMaxaquene','25 de Junho A e B e Bagamoyo',NULL,NULL,NULL,NULL,1,'2026-07-29 12:19:11','2026-07-29 12:19:11'),(10,'17.ª Esquadra','E-17','esquadra','KaMubukwana','Luís Cabral, Jardim e Inhagoia',NULL,NULL,NULL,NULL,1,'2026-07-29 12:19:11','2026-07-29 12:19:11'),(11,'18.ª Esquadra','E-18','esquadra','KaMaxaquene','Malanga e Chamanculo C',NULL,NULL,NULL,NULL,1,'2026-07-29 12:19:11','2026-07-29 12:19:11'),(12,'19.ª Esquadra','E-19','esquadra','KaTembe','KaTembe',NULL,NULL,NULL,NULL,1,'2026-07-29 12:19:11','2026-07-29 12:19:11'),(13,'20.ª Esquadra','E-20','esquadra','KaInhaca','Inhaca',NULL,NULL,NULL,NULL,1,'2026-07-29 12:19:11','2026-07-29 12:19:11'),(14,'21.ª Esquadra','E-21','esquadra','KaMavota','Zimpeto',NULL,NULL,NULL,NULL,1,'2026-07-29 12:19:11','2026-07-29 12:19:11'),(15,'22.ª Esquadra','E-22','esquadra','KaMavota','Hulene A e B, FPLM, Mavalane A e B e Força Aérea',NULL,NULL,NULL,NULL,1,'2026-07-29 12:19:11','2026-07-29 12:19:11');
INSERT INTO `tipos_ocorrencia` VALUES (1,'Furto','FUR',NULL,'media',1,'2026-07-29 12:19:11'),(2,'Roubo','ROB',NULL,'alta',1,'2026-07-29 12:19:11'),(3,'Assalto à Mão Armada','AMA',NULL,'critica',1,'2026-07-29 12:19:11'),(4,'Violência Doméstica','VD',NULL,'alta',1,'2026-07-29 12:19:11'),(5,'Acidente de Viação','AV',NULL,'media',1,'2026-07-29 12:19:11'),(6,'Homicídio','HOM',NULL,'critica',1,'2026-07-29 12:19:11'),(7,'Ofensas Corporais','OC',NULL,'media',1,'2026-07-29 12:19:11'),(8,'Vandalismo','VAN',NULL,'baixa',1,'2026-07-29 12:19:11'),(9,'Tráfico de Droga','TD',NULL,'alta',1,'2026-07-29 12:19:11'),(10,'Violação','VIO',NULL,'critica',1,'2026-07-29 12:19:11'),(11,'Incêndio','INC',NULL,'alta',1,'2026-07-29 12:19:11'),(12,'Perturbação da Ordem Pública','POP',NULL,'media',1,'2026-07-29 12:19:11'),(13,'Burla','BUR',NULL,'media',1,'2026-07-29 12:19:11'),(14,'Ameaça','AME',NULL,'media',1,'2026-07-29 12:19:11'),(15,'Sequestro','SEQ',NULL,'critica',1,'2026-07-29 12:19:11'),(16,'Detenção / Prisão','DET',NULL,'baixa',1,'2026-07-29 12:19:11'),(17,'Descoberta de Cadáver','DC',NULL,'alta',1,'2026-07-29 12:19:11'),(18,'Outros','OUT',NULL,'baixa',1,'2026-07-29 12:19:11');


-- Segurança, versões e rascunhos.
-- Melhoria aditiva: conserva as contas, senhas, ocorrências e históricos existentes.
ALTER TABLE utilizadores ADD COLUMN IF NOT EXISTS exigir_alteracao_senha TINYINT(1) NOT NULL DEFAULT 1;
ALTER TABLE utilizadores ADD COLUMN IF NOT EXISTS versao_sessao INT UNSIGNED NOT NULL DEFAULT 1;
ALTER TABLE ocorrencias ADD COLUMN IF NOT EXISTS versao INT UNSIGNED NOT NULL DEFAULT 1;
ALTER TABLE ocorrencias ADD COLUMN IF NOT EXISTS natureza_dados ENUM('por_validar','confirmados','simulados') NOT NULL DEFAULT 'por_validar';
ALTER TABLE ocorrencias ADD COLUMN IF NOT EXISTS fonte_informacao VARCHAR(500) NULL;

CREATE TABLE IF NOT EXISTS rascunhos_ocorrencia (
    utilizador_id INT NOT NULL,
    contexto VARCHAR(40) NOT NULL,
    dados MEDIUMTEXT NOT NULL,
    versao INT UNSIGNED NOT NULL DEFAULT 1,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expira_em DATETIME NOT NULL,
    PRIMARY KEY (utilizador_id, contexto),
    CONSTRAINT fk_rascunho_utilizador FOREIGN KEY (utilizador_id) REFERENCES utilizadores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Só classifica automaticamente os marcadores explícitos já presentes no texto.
INSERT INTO historico_ocorrencia (ocorrencia_id, utilizador_id, acao, descricao)
SELECT id, NULL, 'Natureza dos dados identificada',
       'Classificação como dados simulados: o próprio registo já continha um marcador explícito de simulação ou de teste de fluxo. O texto original foi conservado.'
FROM ocorrencias WHERE natureza_dados='por_validar' AND
    (descricao_factos LIKE '%DADOS COMPLEMENTARES SIMULADOS%'
     OR observacoes LIKE '%DADOS COMPLEMENTARES SIMULADOS%'
     OR descricao_factos LIKE 'Teste de fluxo completo:%');
UPDATE ocorrencias SET natureza_dados='simulados', versao=versao+1 WHERE natureza_dados='por_validar' AND
    (descricao_factos LIKE '%DADOS COMPLEMENTARES SIMULADOS%'
     OR observacoes LIKE '%DADOS COMPLEMENTARES SIMULADOS%'
     OR descricao_factos LIKE 'Teste de fluxo completo:%');



-- Evolução aditiva do SGO. Executar apenas na base sgo ou numa cópia de teste.
-- Conserva as contas, senhas, ocorrências e históricos existentes.
ALTER TABLE utilizadores
    ADD COLUMN IF NOT EXISTS username VARCHAR(50) NULL,
    ADD COLUMN IF NOT EXISTS exigir_alteracao_senha TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS versao_sessao INT UNSIGNED NOT NULL DEFAULT 1;
ALTER TABLE utilizadores ADD UNIQUE INDEX IF NOT EXISTS uq_sgo_username (username);
ALTER TABLE log_sistema ADD COLUMN IF NOT EXISTS identificador_tentativa VARCHAR(150) NULL;
UPDATE log_sistema SET identificador_tentativa=username_tentativa WHERE identificador_tentativa IS NULL AND username_tentativa IS NOT NULL;
ALTER TABLE ocorrencias
    ADD COLUMN IF NOT EXISTS versao INT UNSIGNED NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS natureza_dados ENUM('por_validar','confirmados','simulados') NOT NULL DEFAULT 'por_validar',
    ADD COLUMN IF NOT EXISTS fonte_informacao VARCHAR(500) NULL,
    ADD COLUMN IF NOT EXISTS conclusao_estado ENUM('sem_proposta','pendente','aprovada','devolvida') NOT NULL DEFAULT 'sem_proposta',
    ADD COLUMN IF NOT EXISTS conclusao_proposta TEXT NULL,
    ADD COLUMN IF NOT EXISTS conclusao_destino ENUM('resolvida','arquivada') NULL,
    ADD COLUMN IF NOT EXISTS conclusao_proposta_por INT NULL,
    ADD COLUMN IF NOT EXISTS conclusao_proposta_em DATETIME NULL,
    ADD COLUMN IF NOT EXISTS conclusao_decidida_por INT NULL,
    ADD COLUMN IF NOT EXISTS conclusao_decidida_em DATETIME NULL,
    ADD COLUMN IF NOT EXISTS conclusao_parecer TEXT NULL,
    ADD INDEX IF NOT EXISTS idx_sgo_encerramento (esquadra_id,conclusao_estado),
    ADD INDEX IF NOT EXISTS idx_sgo_investigador (investigador_id,validacao_estado,estado);
CREATE TABLE IF NOT EXISTS rascunhos_ocorrencia (
    utilizador_id INT NOT NULL,
    contexto VARCHAR(40) NOT NULL,
    dados MEDIUMTEXT NOT NULL,
    versao INT UNSIGNED NOT NULL DEFAULT 1,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expira_em DATETIME NOT NULL,
    PRIMARY KEY (utilizador_id,contexto),
    CONSTRAINT fk_sgo_rascunho_utilizador FOREIGN KEY (utilizador_id) REFERENCES utilizadores(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
-- Corrige apenas os links locais copiados da instalação registo.
UPDATE notificacoes SET url=REPLACE(url,'http://localhost/registo/','http://localhost/SGO/') WHERE url LIKE 'http://localhost/registo/%';
