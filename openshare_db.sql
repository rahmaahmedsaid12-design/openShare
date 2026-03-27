-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : lun. 09 mars 2026 à 02:52
-- Version du serveur : 5.7.36
-- Version de PHP : 7.4.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `openshare_db`
--

-- --------------------------------------------------------

--
-- Structure de la table `commentaires`
--

DROP TABLE IF EXISTS `commentaires`;
CREATE TABLE IF NOT EXISTS `commentaires` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `ressource_id` int(11) NOT NULL,
  `contenu` text NOT NULL,
  `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `ressource_id` (`ressource_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `commentaires`
--

INSERT INTO `commentaires` (`id`, `user_id`, `ressource_id`, `contenu`, `date_creation`) VALUES
(1, 3, 7, 'merci beaucoup moussa', '2026-03-09 02:09:13'),
(2, 4, 7, 'merci mr moussa', '2026-03-09 02:41:38');

-- --------------------------------------------------------

--
-- Structure de la table `evaluations`
--

DROP TABLE IF EXISTS `evaluations`;
CREATE TABLE IF NOT EXISTS `evaluations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `ressource_id` int(11) NOT NULL,
  `note` int(11) NOT NULL,
  `date_eval` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_res` (`user_id`,`ressource_id`),
  KEY `ressource_id` (`ressource_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `evaluations`
--

INSERT INTO `evaluations` (`id`, `user_id`, `ressource_id`, `note`, `date_eval`) VALUES
(1, 3, 7, 3, '2026-03-09 02:03:05'),
(4, 4, 7, 2, '2026-03-09 02:41:19');

-- --------------------------------------------------------

--
-- Structure de la table `ressources`
--

DROP TABLE IF EXISTS `ressources`;
CREATE TABLE IF NOT EXISTS `ressources` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titre` varchar(255) NOT NULL,
  `description` text,
  `categorie` varchar(100) NOT NULL,
  `licence` varchar(100) NOT NULL,
  `fichier_url` varchar(255) NOT NULL,
  `auteur` varchar(100) NOT NULL,
  `auteur_id` int(11) DEFAULT NULL,
  `note_moyenne` decimal(3,2) NOT NULL DEFAULT '0.00',
  `nb_telechargements` int(11) NOT NULL DEFAULT '0',
  `statut` enum('en_attente','valide','rejete') NOT NULL DEFAULT 'valide',
  `date_ajout` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `auteur_id` (`auteur_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `ressources`
--

INSERT INTO `ressources` (`id`, `titre`, `description`, `categorie`, `licence`, `fichier_url`, `auteur`, `auteur_id`, `note_moyenne`, `nb_telechargements`, `statut`, `date_ajout`) VALUES
(7, 'Code open source java', 'voici le code java to run app spring', 'Developpement', 'Domaine Public', 'uploads/f2955e0509b7bbbe77d179bb65e58deb.pdf', 'moussa', 3, '2.50', 1, 'valide', '2026-03-09 01:50:17');

-- --------------------------------------------------------

--
-- Structure de la table `signalements`
--

DROP TABLE IF EXISTS `signalements`;
CREATE TABLE IF NOT EXISTS `signalements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `ressource_id` int(11) NOT NULL,
  `motif` varchar(255) NOT NULL,
  `details` text,
  `date_signalement` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_res_report` (`user_id`,`ressource_id`),
  KEY `ressource_id` (`ressource_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `signalements`
--

INSERT INTO `signalements` (`id`, `user_id`, `ressource_id`, `motif`, `details`, `date_signalement`) VALUES
(1, 3, 7, 'Spam ou Publicite', 'spam', '2026-03-09 02:25:56');

-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs`
--

DROP TABLE IF EXISTS `utilisateurs`;
CREATE TABLE IF NOT EXISTS `utilisateurs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_expires` timestamp NULL DEFAULT NULL,
  `date_inscription` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4;

--
-- Déchargement des données de la table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id`, `nom`, `email`, `mot_de_passe`, `role`, `reset_token`, `reset_expires`, `date_inscription`) VALUES
(1, 'Admin', 'admin@openshare.com', '$2y$10$.vFQnGv0WQoeZ3FiLMA.aeOtSKlt7.0Wl8eqeA7znwDLpjhEMiwcO', 'admin', NULL, NULL, '2026-03-09 02:12:36'),
(3, 'moussa omar meraneh', 'omarmeiraneh123@gmail.com', '$2y$10$VFoOIXos3KSfT.E9mGs7dez4DSKOTQaEzrL9mRTvQpShrjbbRgGjC', 'user', NULL, NULL, '2026-03-09 01:44:42'),
(4, 'rahma ahmed said', 'rahma10@gmail.com', '$2y$10$AuojCexAESfAAMOFcQ/Xced6VCBgm/XVCXts3j4mf9BRmJTT4gnEm', 'user', 'ef6f7d53ed8cbb15224a34fdce4db9723add3c9838bf238d05fc41d88f0e679c', '2026-03-09 03:48:37', '2026-03-09 02:19:24');

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `commentaires`
--
ALTER TABLE `commentaires`
  ADD CONSTRAINT `commentaires_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `commentaires_ibfk_2` FOREIGN KEY (`ressource_id`) REFERENCES `ressources` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `evaluations`
--
ALTER TABLE `evaluations`
  ADD CONSTRAINT `evaluations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `evaluations_ibfk_2` FOREIGN KEY (`ressource_id`) REFERENCES `ressources` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `ressources`
--
ALTER TABLE `ressources`
  ADD CONSTRAINT `ressources_ibfk_1` FOREIGN KEY (`auteur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `signalements`
--
ALTER TABLE `signalements`
  ADD CONSTRAINT `signalements_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `signalements_ibfk_2` FOREIGN KEY (`ressource_id`) REFERENCES `ressources` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
