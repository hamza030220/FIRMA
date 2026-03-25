-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : mer. 04 mars 2026 à 04:41
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `firma`
--

-- --------------------------------------------------------

--
-- Structure de la table `accompagnants`
--

CREATE TABLE `accompagnants` (
  `id_accompagnant` int(11) NOT NULL,
  `id_participation` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `accompagnants`
--

INSERT INTO `accompagnants` (`id_accompagnant`, `id_participation`, `nom`, `prenom`) VALUES
(31, 35, 'le s', 'naama');

-- --------------------------------------------------------

--
-- Structure de la table `achats_fournisseurs`
--

CREATE TABLE `achats_fournisseurs` (
  `id` int(11) NOT NULL,
  `fournisseur_id` int(11) NOT NULL,
  `equipement_id` int(11) NOT NULL,
  `quantite` int(11) NOT NULL,
  `prix_unitaire` decimal(10,2) NOT NULL,
  `montant_total` decimal(10,2) NOT NULL,
  `date_achat` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `achats_fournisseurs`
--

INSERT INTO `achats_fournisseurs` (`id`, `fournisseur_id`, `equipement_id`, `quantite`, `prix_unitaire`, `montant_total`, `date_achat`, `notes`) VALUES
(1, 1, 1, 50, 30.00, 1500.00, '2026-02-05 18:23:37', 'Commande initiale pelles'),
(2, 4, 4, 30, 180.00, 5400.00, '2026-02-05 18:23:37', 'Stock irrigation'),
(3, 4, 6, 10, 800.00, 8000.00, '2026-02-10 18:23:37', 'Charrues pour la saison'),
(4, 1, 2, 40, 20.00, 800.00, '2026-02-15 18:23:37', 'Réapprovisionnement râteaux');

--
-- Déclencheurs `achats_fournisseurs`
--
DELIMITER $$
CREATE TRIGGER `after_achat_delete` AFTER DELETE ON `achats_fournisseurs` FOR EACH ROW BEGIN
    UPDATE equipements SET quantite_stock = quantite_stock - OLD.quantite WHERE id = OLD.equipement_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_achat_insert` AFTER INSERT ON `achats_fournisseurs` FOR EACH ROW BEGIN
    UPDATE equipements SET quantite_stock = quantite_stock + NEW.quantite WHERE id = NEW.equipement_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `avis`
--

CREATE TABLE `avis` (
  `id_avis` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `note` int(11) DEFAULT NULL CHECK (`note` between 1 and 10),
  `commentaire` text DEFAULT NULL,
  `date_avis` date DEFAULT NULL,
  `id_tech` int(11) DEFAULT NULL,
  `id_demande` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `type_produit` enum('equipement','vehicule','terrain') NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `categories`
--

INSERT INTO `categories` (`id`, `nom`, `type_produit`, `description`) VALUES
(1, 'Tracteurs', 'vehicule', 'Tracteurs agricoles de différentes puissances'),
(2, 'Moissonneuses', 'vehicule', 'Moissonneuses-batteuses et récolteuses'),
(3, 'Camions et Remorques', 'vehicule', 'Véhicules de transport agricole'),
(4, 'Terres Arables', 'terrain', 'Terrains destinés aux cultures'),
(5, 'Pâturages', 'terrain', 'Terrains pour élevage'),
(6, 'Vergers et Plantations', 'terrain', 'Terrains avec arbres fruitiers'),
(7, 'Outils Manuels', 'equipement', 'Outils agricoles à main'),
(8, 'Systèmes d\'Irrigation', 'equipement', 'Équipements d\'arrosage et irrigation'),
(9, 'Machines Agricoles', 'equipement', 'Machines et équipements motorisés'),
(10, 'Tracteurs Compacts', 'vehicule', 'Petits tracteurs polyvalents'),
(11, 'Engins de Récolte', 'vehicule', 'Machines spécialisées pour la récolte'),
(12, 'Véhicules Utilitaires', 'vehicule', 'Fourgons, pick-ups et utilitaires'),
(13, 'Terres Céréalières', 'terrain', 'Grandes parcelles pour céréales'),
(14, 'Serres', 'terrain', 'Structures de culture protégée'),
(15, 'Vignobles', 'terrain', 'Terrains viticoles'),
(16, 'Semoirs', 'equipement', 'Machines de semis'),
(17, 'Pulvérisateurs', 'equipement', 'Équipements de traitement'),
(18, 'Stockage', 'equipement', 'Solutions de stockage agricole'),
(19, 'Prairies', 'terrain', 'Terrains naturels d\'élevage'),
(20, 'Palmeraies', 'terrain', 'Plantations de palmiers'),
(21, 'Agrumes', 'terrain', 'Vergers d\'agrumes'),
(22, 'Amandiers/Pistachiers', 'terrain', 'Plantations de fruits secs'),
(23, 'test', 'equipement', 'test de catégorie'),
(24, 'Matériel Viticole', 'equipement', 'Équipements pour la viticulture');

-- --------------------------------------------------------

--
-- Structure de la table `categorie_forum`
--

CREATE TABLE `categorie_forum` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `categorie_forum`
--

INSERT INTO `categorie_forum` (`id`, `nom`) VALUES
(2, 'Cultures & Techniques'),
(3, 'Santé'),
(1, 'Transactions');

-- --------------------------------------------------------

--
-- Structure de la table `commandes`
--

CREATE TABLE `commandes` (
  `id` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `numero_commande` varchar(50) NOT NULL,
  `montant_total` decimal(10,2) NOT NULL,
  `statut_paiement` enum('en_attente','paye','echoue') DEFAULT 'en_attente',
  `statut_livraison` enum('en_attente','en_preparation','expedie','livre','annule') DEFAULT 'en_attente',
  `adresse_livraison` text NOT NULL,
  `ville_livraison` varchar(100) DEFAULT NULL,
  `date_commande` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_livraison` date DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `commandes`
--

INSERT INTO `commandes` (`id`, `id_utilisateur`, `numero_commande`, `montant_total`, `statut_paiement`, `statut_livraison`, `adresse_livraison`, `ville_livraison`, `date_commande`, `date_livraison`, `notes`) VALUES
(20, 14, 'CMD-20260304-6565', 1000.00, 'paye', 'en_attente', 'À confirmer', NULL, '2026-03-04 03:15:26', NULL, NULL);

--
-- Déclencheurs `commandes`
--
DELIMITER $$
CREATE TRIGGER `before_commande_insert` BEFORE INSERT ON `commandes` FOR EACH ROW BEGIN
    IF NEW.numero_commande IS NULL OR NEW.numero_commande = '' THEN
        SET NEW.numero_commande = CONCAT('CMD-', DATE_FORMAT(NOW(), '%Y%m%d'), '-', LPAD(FLOOR(RAND() * 9999), 4, '0'));
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `commentaire`
--

CREATE TABLE `commentaire` (
  `id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `utilisateur_id` int(11) NOT NULL,
  `contenu` text NOT NULL,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `commentaire`
--

INSERT INTO `commentaire` (`id`, `post_id`, `utilisateur_id`, `contenu`, `date_creation`) VALUES
(1, 1, 11, 'oui c\'est vrai', '2026-03-03 15:51:54'),
(2, 1, 4, 'hello  i am ramez', '2026-03-03 15:55:26');

-- --------------------------------------------------------

--
-- Structure de la table `demande`
--

CREATE TABLE `demande` (
  `id_demande` int(11) NOT NULL,
  `id_utilisateur` int(11) DEFAULT NULL,
  `type_probleme` varchar(150) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `date_demande` date DEFAULT NULL,
  `statut` varchar(50) DEFAULT NULL,
  `id_tech` int(11) DEFAULT NULL,
  `adresse_client` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `demande`
--

INSERT INTO `demande` (`id_demande`, `id_utilisateur`, `type_probleme`, `description`, `date_demande`, `statut`, `id_tech`, `adresse_client`) VALUES
(58, 4, 'Installation', 'edcjnejbcejfbvcefjvbe', '2026-03-02', 'Terminée', 1, NULL),
(61, 11, 'Installation', 'hbfzhjegfbdhjezbfjhezbfjhezf', '2026-03-03', 'En attente', 1, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `details_commandes`
--

CREATE TABLE `details_commandes` (
  `id` int(11) NOT NULL,
  `commande_id` int(11) NOT NULL,
  `equipement_id` int(11) NOT NULL,
  `quantite` int(11) NOT NULL,
  `prix_unitaire` decimal(10,2) NOT NULL,
  `sous_total` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `details_commandes`
--

INSERT INTO `details_commandes` (`id`, `commande_id`, `equipement_id`, `quantite`, `prix_unitaire`, `sous_total`) VALUES
(14, 20, 5, 1, 1000.00, 1000.00);

--
-- Déclencheurs `details_commandes`
--
DELIMITER $$
CREATE TRIGGER `before_detail_commande_insert` BEFORE INSERT ON `details_commandes` FOR EACH ROW BEGIN
    SET NEW.sous_total = NEW.quantite * NEW.prix_unitaire;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `before_detail_commande_update` BEFORE UPDATE ON `details_commandes` FOR EACH ROW BEGIN
    SET NEW.sous_total = NEW.quantite * NEW.prix_unitaire;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `equipements`
--

CREATE TABLE `equipements` (
  `id` int(11) NOT NULL,
  `categorie_id` int(11) NOT NULL,
  `fournisseur_id` int(11) NOT NULL,
  `nom` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `prix_achat` decimal(10,2) NOT NULL,
  `prix_vente` decimal(10,2) NOT NULL,
  `quantite_stock` int(11) DEFAULT 0,
  `seuil_alerte` int(11) DEFAULT 5,
  `image_url` varchar(255) DEFAULT NULL,
  `disponible` tinyint(1) DEFAULT 1,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `equipements`
--

INSERT INTO `equipements` (`id`, `categorie_id`, `fournisseur_id`, `nom`, `description`, `prix_achat`, `prix_vente`, `quantite_stock`, `seuil_alerte`, `image_url`, `disponible`, `date_creation`) VALUES
(1, 7, 1, 'Pelle agricole premium', 'Pelle robuste en acier forgé pour travaux lourds', 30.00, 45.50, 25, 5, 'file:/C:/Users/zussl/Desktop/finale/firma/src/main/resources/image/i4.png', 1, '2026-02-05 18:23:37'),
(2, 7, 1, 'Râteau professionnel 16 dents', 'Râteau en acier inoxydable avec manche en bois', 20.00, 32.00, 26, 8, 'file:/C:/Users/zussl/Desktop/finale/firma/src/main/resources/image/i4.png', 1, '2026-02-05 18:23:37'),
(3, 7, 2, 'Sécateur professionnel', 'Sécateur ergonomique lame acier trempé', 15.00, 28.00, 40, 10, 'file:/C:/Users/zussl/Desktop/finale/firma/src/main/resources/image/i4.png', 1, '2026-02-05 18:23:37'),
(4, 8, 4, 'Kit irrigation goutte-à-goutte 100m', 'Système complet avec programmateur', 180.00, 250.00, 100, 3, 'file:/C:/Users/zussl/Desktop/finale/firma/src/main/resources/image/i4.png', 1, '2026-02-05 18:23:37'),
(5, 8, 4, 'Arroseur oscillant grande surface', 'Couvre jusqu\'à 300m²', 45.00, 1000.00, 18, 5, 'file:/C:/Users/zussl/Desktop/finale/firma/src/main/resources/image/i4.png', 1, '2026-02-05 18:23:37'),
(6, 9, 4, 'Charrue réversible 2 socs', 'Pour tracteur 60-80CV', 800.00, 1200.00, 55, 2, 'file:/C:/Users/zussl/Desktop/finale/firma/src/main/resources/image/i4.png', 1, '2026-02-05 18:23:37'),
(7, 9, 3, 'Herse rotative 1.5m', 'Herse pour préparation du sol', 650.00, 950.00, 8, 2, 'file:/C:/Users/zussl/Desktop/finale/firma/src/main/resources/image/i4.png', 0, '2026-02-05 18:23:37'),
(11, 7, 1, 'Semoir mécanique 12 rangs', 'Semoir précis pour céréales et légumineuses', 2500.00, 3500.00, 8, 2, 'file:/C:/Users/zussl/Desktop/finale/firma/src/main/resources/image/i4.png', 1, '2026-02-14 21:08:14'),
(12, 8, 2, 'Pulvérisateur porté 600L', 'Pulvérisateur avec rampe 12m', 1800.00, 2600.00, 6, 2, 'file:/C:/Users/zussl/Desktop/finale/firma/src/main/resources/image/i4.png', 1, '2026-02-14 21:08:14'),
(13, 7, 1, 'Brouette galvanisée 100L', 'Brouette robuste pour travaux lourds', 85.00, 145.00, 25, 5, 'file:/C:/Users/zussl/Desktop/finale/firma/src/main/resources/image/i4.png', 1, '2026-02-14 21:08:14'),
(14, 8, 4, 'Tuyau irrigation 50m', 'Tuyau renforcé diamètre 25mm', 35.00, 55.00, 100, 20, 'file:/C:/Users/zussl/Desktop/finale/firma/src/main/resources/image/i4.png', 1, '2026-02-14 21:08:14'),
(15, 9, 14, 'Cultivateur à dents 2.5m', 'Cultivateur pour travail superficiel', 950.00, 1400.00, 30, 2, 'file:/C:/Users/zussl/Desktop/finale/firma/src/main/resources/image/i4.png', 1, '2026-02-14 21:08:14'),
(16, 7, 2, 'Filet anti-grêle 4x100m', 'Protection cultures sensibles', 180.00, 280.00, 30, 10, 'file:/C:/Users/zussl/Desktop/finale/firma/src/main/resources/image/i4.png', 1, '2026-02-14 21:08:14'),
(17, 8, 1, 'Bac de stockage 500L', 'Cuve plastique alimentaire', 120.00, 195.00, 14, 5, 'file:/C:/Users/zussl/Desktop/finale/firma/src/main/resources/image/i4.png', 1, '2026-02-14 21:08:14'),
(18, 9, 3, 'Épandeur centrifuge 300L', 'Distribution uniforme engrais', 450.00, 680.00, 9, 3, 'file:/C:/Users/zussl/Desktop/finale/firma/src/main/resources/image/i4.png', 1, '2026-02-14 21:08:14'),
(19, 7, 4, 'Motobineuse 5CV', 'Motobineuse thermique professionnelle', 380.00, 550.00, 8, 3, 'file:/C:/Users/zussl/Desktop/finale/firma/src/main/resources/image/i4.png', 1, '2026-02-14 21:08:14'),
(20, 8, 4, 'Pompe immergée 1.5CV', 'Pompe pour puits jusqu\'à 30m', 220.00, 350.00, 100, 4, 'file:/C:/Users/zussl/Desktop/finale/firma/src/main/resources/image/i4.png', 1, '2026-02-14 21:08:14'),
(21, 7, 2, 'Cueille-olives électrique', 'Peigne vibreur rechargeable', 290.00, 420.00, 20, 5, 'file:/C:/Users/zussl/Desktop/finale/firma/src/main/resources/image/i4.png', 1, '2026-02-14 21:08:14'),
(22, 8, 1, 'Atomiseur dos 20L', 'Pulvérisateur motorisé portable', 350.00, 520.00, 12, 5, 'file:/C:/Users/zussl/Desktop/finale/firma/src/main/resources/image/i4.png', 1, '2026-02-14 21:08:14'),
(23, 7, 13, 'Planteuse manuelle', 'Planteuse pour plants en mottes', 65.00, 95.00, 40, 10, 'file:/C:/Users/zussl/Desktop/finale/firma/src/main/resources/image/i4.png', 1, '2026-02-14 21:08:14'),
(24, 9, 2, 'Disques de labour 24 pouces', 'Jeu de 4 disques pour charrue', 280.00, 420.00, 18, 5, 'file:/C:/Users/zussl/Desktop/finale/firma/src/main/resources/image/i4.png', 1, '2026-02-14 21:08:14'),
(25, 8, 4, 'Silo souple 5000L', 'Stockage grains et aliments', 450.00, 680.00, 6, 2, 'file:/C:/Users/zussl/Desktop/finale/firma/src/main/resources/image/i4.png', 1, '2026-02-14 21:08:14'),
(30, 23, 1, 'test 2', 'test gl 1', 11.00, 100.00, 196, 8, 'file:/C:/Users/zussl/Desktop/finale/firma/src/main/resources/image/i4.png', 1, '2026-02-24 00:12:46'),
(31, 24, 13, 'test', 'testt', 10.00, 20.00, 10, 5, 'file:/C:/Users/zussl/Desktop/finale/firma/src/main/resources/image/i4.png', 1, '2026-02-28 23:13:54');

-- --------------------------------------------------------

--
-- Structure de la table `evenements`
--

CREATE TABLE `evenements` (
  `id_evenement` int(11) NOT NULL,
  `titre` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `type_evenement` enum('exposition','atelier','conference','salon','formation','autre') DEFAULT NULL,
  `date_debut` datetime NOT NULL,
  `date_fin` datetime DEFAULT NULL,
  `horaire_debut` time DEFAULT NULL,
  `horaire_fin` time DEFAULT NULL,
  `lieu` varchar(200) DEFAULT NULL,
  `adresse` text DEFAULT NULL,
  `capacite_max` int(11) DEFAULT NULL,
  `places_disponibles` int(11) DEFAULT NULL,
  `organisateur` varchar(100) DEFAULT NULL,
  `contact_email` varchar(100) DEFAULT NULL,
  `contact_tel` varchar(20) DEFAULT NULL,
  `statut` enum('actif','annule','termine','complet') DEFAULT 'actif',
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_modification` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `evenements`
--

INSERT INTO `evenements` (`id_evenement`, `titre`, `description`, `image_url`, `type_evenement`, `date_debut`, `date_fin`, `horaire_debut`, `horaire_fin`, `lieu`, `adresse`, `capacite_max`, `places_disponibles`, `organisateur`, `contact_email`, `contact_tel`, `statut`, `date_creation`, `date_modification`) VALUES
(1, 'Conférence Innovation Digitale 2026', 'Grande conférence sur les dernières innovations en matière de transformation digitale et intelligence artificielle', 'image/10.jpg', 'conference', '2026-03-15 00:00:00', '2026-03-15 00:00:00', '09:00:00', '17:00:00', 'Palais des Congrès', '1 Place de la Porte Maillot, 75017 Paris', 500, 500, 'TechEvents SA', NULL, NULL, 'actif', '2026-01-31 23:00:00', '2026-02-28 00:17:45'),
(2, 'Salon de l\'Agriculture Bio 2026', 'Salon dédié à l\'agriculture biologique et aux pratiques agricoles durables', 'image/ia_1772595026180.png', 'salon', '2026-04-20 00:00:00', '2026-04-25 00:00:00', '10:00:00', '19:00:00', 'Parc des Expositions', 'Place de la Concorde, 75008 Paris', 8000, 8000, 'Fédération Agriculture Bio', NULL, NULL, 'actif', '2026-01-19 23:00:00', '2026-03-04 03:30:28'),
(3, 'Exposition d\'Art Contemporain \"Visions 2026\"', 'Exposition majeure d\'art contemporain avec 50 artistes internationaux', 'image/15.jpg', 'exposition', '2026-05-01 00:00:00', '2026-06-30 00:00:00', '11:00:00', '19:00:00', 'Musée d\'Art Moderne', '11 Avenue du Président Wilson, 75116 Paris', 200, 200, 'Musée d\'Art Moderne de Paris', NULL, NULL, 'actif', '2026-01-31 23:00:00', '2026-03-04 03:29:22'),
(4, 'Atelier Cuisine Méditerranéenne', 'Atelier pratique de cuisine méditerranéenne animé par un chef étoilé', 'image/ia_1772595461600.png', 'atelier', '2026-03-22 00:00:00', '2026-03-22 00:00:00', '14:00:00', '18:00:00', 'École de Cuisine Le Cordon Bleu', '8 Rue Léon Delhomme, 75015 Paris', 25, 25, 'Le Cordon Bleu Paris', NULL, NULL, 'actif', '2026-02-04 23:00:00', '2026-03-04 03:37:43'),
(5, 'Formation DevOps & Cloud Computing', 'Formation intensive de 3 jours sur DevOps, Docker et Kubernetes', 'image/ia_1772595324323.png', 'formation', '2026-04-14 00:00:00', '2026-04-16 00:00:00', '09:00:00', '17:00:00', 'Centre de Formation IT', '45 Rue de la Victoire, 75009 Paris', 30, 30, 'IT Training Academy', NULL, NULL, 'actif', '2026-02-04 23:00:00', '2026-03-04 03:35:27'),
(6, 'Conférence Leadership & Management', 'Conférence internationale sur les nouvelles approches du leadership', 'image/9.jpg', 'conference', '2026-03-25 00:00:00', '2026-03-26 00:00:00', '08:30:00', '17:30:00', 'Hôtel Mercure', '20 Rue Jean Jaurès, 92800 Puteaux', 150, 150, 'Business Leaders Institute', NULL, NULL, 'actif', '2026-02-07 23:00:00', '2026-03-04 03:37:23'),
(7, 'Salon du Livre et de l\'Édition', 'Rencontres avec des auteurs, dédicaces et conférences littéraires', 'image/ia_1772246194540.png', 'salon', '2026-06-05 00:00:00', '2026-06-08 00:00:00', '10:00:00', '19:00:00', 'Espace Culturel', '15 Boulevard Raspail, 75007 Paris', 5000, 5000, 'Association des Éditeurs', NULL, NULL, 'actif', '2026-01-31 23:00:00', '2026-02-28 02:36:39'),
(8, 'Atelier Photographie de Portrait', 'Atelier pratique de photographie avec un photographe professionnel', 'image/ia_1772595069234.png', 'atelier', '2026-04-18 00:00:00', '2026-04-19 00:00:00', '10:00:00', '17:00:00', 'Studio Photo Paris', '28 Rue de la République, 75011 Paris', 16, 16, 'Paris Photo Studio', NULL, NULL, 'actif', '2026-02-11 23:00:00', '2026-03-04 03:31:13'),
(9, 'Exposition Sculptures Modernes', 'Découverte des œuvres de sculpteurs contemporains européens', 'C:/Users/selmi/OneDrive - ESPRIT/Bureau/event.jpg', 'exposition', '2026-01-15 00:00:00', '2026-02-10 00:00:00', '10:00:00', '18:00:00', 'Galerie d\'Art Moderne', '42 Rue de Turenne, 75003 Paris', 150, 0, 'Galerie Moderne Paris', 'info@galerie-moderne.fr', '+33142345678', 'termine', '2025-12-07 23:00:00', '2026-02-10 23:00:00'),
(10, 'Formation Gestion de Projet Agile', 'Formation certifiante Scrum Master et méthodologies agiles', 'image/10.jpg', 'formation', '2026-05-20 00:00:00', '2026-05-22 00:00:00', '09:00:00', '18:00:00', 'Centre de Formation Agile', '10 Avenue des Champs-Élysées, 75008 Paris', 40, 40, 'Agile Training Center', NULL, NULL, 'actif', '2026-02-09 23:00:00', '2026-02-28 00:16:02'),
(11, 'Conférence Cybersécurité 2026', 'Conférence sur les menaces cyber et les stratégies de protection', 'image/10.jpg', 'conference', '2026-03-30 00:00:00', '2026-03-30 00:00:00', '09:00:00', '18:00:00', 'Centre de Conventions', '5 Place de la Défense, 92400 Courbevoie', 800, 800, 'CyberSec Institute', NULL, NULL, 'actif', '2026-02-07 23:00:00', '2026-02-28 00:19:04'),
(12, 'Salon des Startups et Innovation', 'Salon dédié aux startups innovantes et aux investisseurs', 'image/6.jpg', 'salon', '2026-05-12 00:00:00', '2026-05-14 00:00:00', '09:00:00', '19:00:00', 'Paris Expo Porte de Versailles', 'Place de la Porte de Versailles, 75015 Paris', 10000, 10000, 'Innovation Hub France', NULL, NULL, 'actif', '2026-02-04 23:00:00', '2026-03-04 03:28:32'),
(13, 'Atelier Développement Web React', 'Atelier pratique de développement d\'applications web avec React.js', 'image/ia_1772594983300.png', 'atelier', '2026-04-25 00:00:00', '2026-04-25 00:00:00', '14:00:00', '19:00:00', 'Coding Academy', '18 Rue du Faubourg Saint-Antoine, 75012 Paris', 20, 20, 'Web Dev Academy', NULL, NULL, 'actif', '2026-02-13 23:00:00', '2026-03-04 03:29:49'),
(15, 'Formation Intelligence Artificielle', 'Formation complète sur le Machine Learning et Deep Learning', 'image/ia_1772237703525.png', 'formation', '2026-06-01 00:00:00', '2026-06-05 00:00:00', '09:00:00', '17:00:00', 'AI Training Center', '25 Rue de Clichy, 75009 Paris', 35, 35, 'AI Institute Paris', NULL, NULL, 'actif', '2026-02-11 23:00:00', '2026-02-28 00:15:07'),
(16, 'Conférence Blockchain et Finance', 'Conférence internationale sur la blockchain et les cryptomonnaies', 'C:/Users/selmi/OneDrive - ESPRIT/Bureau/event.jpg', 'conference', '2026-02-10 00:00:00', '2026-02-10 00:00:00', '08:30:00', '18:00:00', 'Station F', '5 Parvis Alan Turing, 75013 Paris', 600, 0, 'FinTech Innovation Hub', 'conference@fintechhub.io', '+33142567891', 'annule', '2026-01-09 23:00:00', '2026-02-04 23:00:00'),
(17, 'Salon de l\'Emploi Tech 2026', 'Salon de recrutement dédié aux métiers de la tech et du digital', 'image/ia_1772595359712.png', 'salon', '2026-04-08 00:00:00', '2026-04-09 00:00:00', '09:00:00', '18:00:00', 'Grande Halle de la Villette', '211 Avenue Jean Jaurès, 75019 Paris', 5000, 5000, 'JobTech France', NULL, NULL, 'actif', '2026-02-07 23:00:00', '2026-03-04 03:36:00'),
(18, 'Atelier Design Thinking', 'Atelier interactif sur les méthodologies de Design Thinking', 'image/ia_1772594276999.png', 'atelier', '2026-05-15 00:00:00', '2026-05-15 00:00:00', '09:30:00', '17:30:00', 'Innovation Lab', '32 Rue de Rivoli, 75004 Paris', 30, 30, 'Design Lab Paris', NULL, NULL, 'actif', '2026-02-10 23:00:00', '2026-03-04 03:17:59'),
(37, 'naama', 'naama', 'image/1.jpg', 'formation', '2026-02-21 00:00:00', '2026-02-22 00:00:00', '14:30:00', '15:30:00', 'hotel Andalucia', 'Bizerte,Tunis', 30, 30, 'naama', NULL, NULL, 'actif', '2026-02-20 21:06:54', '2026-03-04 03:36:52'),
(39, 'Journée d’Innovation en Agriculture Intelligente', 'La Journée d’Innovation en Agriculture Intelligente est un événement dédié aux agriculteurs, étudiants et professionnels du secteur agricole souhaitant découvrir les dernières technologies appliquées au domaine agricole.', 'image/15.jpg', 'formation', '2026-03-04 00:00:00', '2026-03-04 00:00:00', '08:30:00', '14:30:00', 'Institut National Agronomique de Tunisie', '43 Avenue Charles Nicolle, 1082 Tunis, Tunisie', 30, 30, 'naama', NULL, NULL, 'actif', '2026-02-27 01:32:46', '2026-03-04 03:38:51'),
(43, 'test', 'test', 'image/1.jpg', 'atelier', '2026-02-28 00:00:00', '2026-03-02 00:00:00', '12:00:00', '14:00:00', 'bizerte', '7000', 10, 9, 'test', NULL, NULL, 'actif', '2026-02-28 00:20:52', '2026-03-01 01:19:35');

-- --------------------------------------------------------

--
-- Structure de la table `fournisseurs`
--

CREATE TABLE `fournisseurs` (
  `id` int(11) NOT NULL,
  `nom_entreprise` varchar(200) NOT NULL,
  `contact_nom` varchar(100) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `adresse` text DEFAULT NULL,
  `ville` varchar(100) DEFAULT NULL,
  `actif` tinyint(1) DEFAULT 1,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `fournisseurs`
--

INSERT INTO `fournisseurs` (`id`, `nom_entreprise`, `contact_nom`, `email`, `telephone`, `adresse`, `ville`, `actif`, `date_creation`) VALUES
(1, 'AgriTech Tunisie', 'Ahmed Sassi', 'contact@agritech.tn', '+216 71 234 567', 'fgfdgfdg', 'Tunis', 1, '2026-02-05 18:23:37'),
(2, 'EquipAgro SARL', 'Leila Mansouri', 'info@equipagro.tn', '+216 73 456 789', NULL, 'Sousse', 0, '2026-02-05 18:23:37'),
(3, 'FarmTools International', 'Karim Ben Salem', 'sales@farmtools.com', '+216 72 345 678', NULL, 'Sfax', 0, '2026-02-05 18:23:37'),
(4, 'IrrigaPro', 'Sonia Gharbi', 'contact@irrigapro.tn', '+216 74 567 890', NULL, 'Bizerte', 1, '2026-02-05 18:23:37'),
(6, 'retretre', 'retretrt', 'ezrre@ezre.tn', '+216 12 345 655', 'uiuyiuyi', 'uiuyi', 1, '2026-02-14 19:01:14'),
(9, 'MachinesAgri Plus', 'Youssef Mejri', 'contact@machinesagri.tn', '+216 71 456 123', 'Zone Industrielle Ben Arous', 'Ben Arous', 1, '2026-02-14 21:07:57'),
(10, 'SemaPlant SARL', 'Nadia Belhadj', 'info@semaplant.tn', '+216 72 789 456', 'Route de Sousse Km 5', 'Monastir', 1, '2026-02-14 21:07:57'),
(11, 'TractoPieces Tunisie', 'Riadh Bouazizi', 'ventes@tractopieces.tn', '+216 74 321 654', 'Avenue de la République', 'Sfax', 1, '2026-02-14 21:07:57'),
(12, 'AgroSolutions', 'Samia Khemiri', 'contact@agrosolutions.tn', '+216 78 852 963', 'Rue Ibn Khaldoun', 'Kairouan', 1, '2026-02-14 21:07:57'),
(13, 'Ferme&Jardin', 'Mohamed Chaabane', 'service@fermeetjardin.tn', '+216 71 147 258', 'Centre Urbain Nord', 'Tunis', 1, '2026-02-14 21:07:57'),
(14, 'IrrigaTech', 'Olfa Maalej', 'contact@irrigatech.tn', '+216 73 369 741', 'Zone Industrielle Agba', 'Sousse', 1, '2026-02-14 21:07:57'),
(15, 'MatérielAgro', 'Slim Hammami', 'info@materielagro.tn', '+216 75 258 147', 'Route de Gabès', 'Médenine', 1, '2026-02-14 21:07:57'),
(16, 'BioAgri Distribution', 'Ines Zouari', 'bio@bioagri.tn', '+216 76 963 852', 'Avenue Farhat Hached', 'Nabeul', 1, '2026-02-14 21:07:57'),
(17, 'TunisieEquip', 'Hatem Jlassi', 'contact@tunisieequip.tn', '+216 70 741 852', 'Rue de Palestine', 'Tunis', 1, '2026-02-14 21:07:57'),
(18, 'SudAgri Import', 'Mouna Masmoudi', 'import@sudagri.tn', '+216 75 159 357', 'Zone Commerciale', 'Gabès', 1, '2026-02-14 21:07:57'),
(19, 'NordFerme', 'Khaled Triki', 'info@nordferme.tn', '+216 72 753 159', 'Avenue Habib Thameur', 'Bizerte', 1, '2026-02-14 21:07:57'),
(20, 'AgriCap Tunisie', 'Sarra Bouzid', 'cap@agricap.tn', '+216 79 456 789', 'Cité Olympique', 'Tunis', 0, '2026-02-14 21:07:57'),
(21, 'MatAgri Express', 'Fathi Gharbi', 'express@matagri.tn', '+216 71 852 456', 'Route de la Marsa', 'La Marsa', 1, '2026-02-14 21:07:57'),
(22, 'ProFarm Équipements', 'Wafa Amri', 'pro@profarm.tn', '+216 73 147 963', 'Zone Industrielle', 'Mahdia', 1, '2026-02-14 21:07:57'),
(23, 'Delta Agri Services', 'Nizar Ayari', 'delta@deltaagri.tn', '+216 74 369 852', 'Avenue Majida Boulila', 'Sfax', 1, '2026-02-14 21:07:57');

-- --------------------------------------------------------

--
-- Structure de la table `locations`
--

CREATE TABLE `locations` (
  `id` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `type_location` enum('vehicule','terrain') NOT NULL,
  `vehicule_id` int(11) DEFAULT NULL,
  `terrain_id` int(11) DEFAULT NULL,
  `numero_location` varchar(50) NOT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date NOT NULL,
  `duree_jours` int(11) DEFAULT NULL,
  `prix_total` decimal(10,2) NOT NULL,
  `caution` decimal(10,2) DEFAULT 0.00,
  `statut` enum('en_attente','confirmee','en_cours','terminee','annulee') DEFAULT 'en_attente',
  `date_reservation` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `participations`
--

CREATE TABLE `participations` (
  `id_participation` int(11) NOT NULL,
  `id_evenement` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `statut` enum('en_attente','confirme','annule') DEFAULT 'confirme',
  `date_inscription` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_annulation` datetime DEFAULT NULL,
  `nombre_accompagnants` int(11) DEFAULT 0,
  `commentaire` text DEFAULT NULL,
  `code_participation` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `participations`
--

INSERT INTO `participations` (`id_participation`, `id_evenement`, `id_utilisateur`, `statut`, `date_inscription`, `date_annulation`, `nombre_accompagnants`, `commentaire`, `code_participation`) VALUES
(35, 37, 14, 'confirme', '2026-03-04 03:10:54', NULL, 1, 'test', 'PART-LSNA7');

-- --------------------------------------------------------

--
-- Structure de la table `personne`
--

CREATE TABLE `personne` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `personne`
--

INSERT INTO `personne` (`id`, `nom`, `prenom`) VALUES
(1, 'naama', 'selmi'),
(2, 'naama', 'selmi'),
(3, 'hamzaa', 'slimani'),
(4, 'naaaa', 'hhbhjg'),
(5, 'hjbgkhhk', 'b n,bn,nb'),
(6, 'n,bn,b,', 'nbnjbnjnj'),
(7, 'hhhhhhhh', 'jhuhuh');

-- --------------------------------------------------------

--
-- Structure de la table `post`
--

CREATE TABLE `post` (
  `id` int(11) NOT NULL,
  `utilisateur_id` int(11) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `contenu` text NOT NULL,
  `categorie` varchar(100) DEFAULT NULL,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp(),
  `statut` varchar(50) DEFAULT 'actif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `post`
--

INSERT INTO `post` (`id`, `utilisateur_id`, `titre`, `contenu`, `categorie`, `date_creation`, `statut`) VALUES
(1, 1, 'gfxgzdf', 'cegdvcevrcjerv', 'Transactions > Offre & demande', '2026-03-03 12:27:36', 'actif');

-- --------------------------------------------------------

--
-- Structure de la table `profile`
--

CREATE TABLE `profile` (
  `id_profile` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `photo_profil` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `date_naissance` date DEFAULT NULL,
  `genre` enum('homme','femme','autre') DEFAULT NULL,
  `pays` varchar(100) DEFAULT NULL,
  `ville` varchar(100) DEFAULT NULL,
  `derniere_mise_a_jour` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `profile`
--

INSERT INTO `profile` (`id_profile`, `id_utilisateur`, `photo_profil`, `bio`, `date_naissance`, `genre`, `pays`, `ville`, `derniere_mise_a_jour`) VALUES
(2, 11, 'C:\\Users\\Ramez Hadhri\\Downloads\\timeConfig.png', 'xbhedbcjhbfcjhbfe', '2026-03-29', 'homme', 'sfax', 'jbeniana', '2026-03-03 19:26:08'),
(3, 12, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-03 15:59:31'),
(4, 13, 'C:\\Users\\zussl\\Pictures\\Screenshots\\Capture d\'écran 2026-02-09 191609.png', '', NULL, NULL, '', 'test', '2026-03-04 02:00:13'),
(5, 14, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-04 03:10:17');

-- --------------------------------------------------------

--
-- Structure de la table `technicien`
--

CREATE TABLE `technicien` (
  `id_tech` int(11) NOT NULL,
  `id_utilisateur` int(11) DEFAULT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `specialite` varchar(100) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `disponibilite` tinyint(1) DEFAULT 1,
  `localisation` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `date_naissance` date DEFAULT NULL,
  `cin` varchar(20) DEFAULT NULL,
  `latitude` double DEFAULT NULL,
  `longitude` double DEFAULT NULL,
  `partage_position` tinyint(1) DEFAULT 0,
  `derniere_maj_position` timestamp NULL DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `partage_auto` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `technicien`
--

INSERT INTO `technicien` (`id_tech`, `id_utilisateur`, `nom`, `prenom`, `email`, `specialite`, `telephone`, `disponibilite`, `localisation`, `image`, `age`, `date_naissance`, `cin`, `latitude`, `longitude`, `partage_position`, `derniere_maj_position`, `password`, `partage_auto`) VALUES
(1, NULL, 'molka', 'ajengui', 'molkaajengui@gmail.com', 'Informatique', '54955040', 1, 'aryena tunis', 'tech_34e2da00.jpg', 21, '2004-03-03', '1111111', 36.8065, 10.1815, 1, '2026-03-02 12:18:47', '123', 0);

-- --------------------------------------------------------

--
-- Structure de la table `terrains`
--

CREATE TABLE `terrains` (
  `id` int(11) NOT NULL,
  `categorie_id` int(11) NOT NULL,
  `titre` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `superficie_hectares` decimal(10,2) NOT NULL,
  `ville` varchar(100) NOT NULL,
  `adresse` text DEFAULT NULL,
  `prix_mois` decimal(10,2) DEFAULT NULL,
  `prix_annee` decimal(10,2) NOT NULL,
  `caution` decimal(10,2) DEFAULT 0.00,
  `image_url` varchar(255) DEFAULT NULL,
  `disponible` tinyint(1) DEFAULT 1,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `terrains`
--

INSERT INTO `terrains` (`id`, `categorie_id`, `titre`, `description`, `superficie_hectares`, `ville`, `adresse`, `prix_mois`, `prix_annee`, `caution`, `image_url`, `disponible`, `date_creation`) VALUES
(1, 4, 'Terre arable fertile Manouba', 'Terrain plat avec accès eau et électricité, sol argileux fertile', 5.50, 'Manouba', 'Route Borj El Amri Km 12', 700.00, 8000.00, 2000.00, 'file:/C:/Users/zussl/Desktop/finale/firma/src/main/resources/image/i2.png', 1, '2026-02-05 18:23:37'),
(2, 5, 'Grand pâturage Zaghouan', 'Pâturage clôturé avec point d\'eau naturel', 10.00, 'Zaghouan', 'Douar Henchir Toumia', 550.00, 6000.00, 1500.00, 'file:/C:/Users/zussl/Desktop/finale/firma/src/main/resources/image/i2.png', 1, '2026-02-05 18:23:37'),
(3, 6, 'Verger d\'oliviers Sfax', 'Verger de 300 oliviers centenaires en production', 8.00, 'Sfax', 'Route Mahres Km 8', 850.00, 10000.00, 2500.00, 'file:/C:/Users/zussl/Desktop/finale/firma/src/main/resources/image/i2.png', 1, '2026-02-05 18:23:37'),
(4, 4, 'Terre maraîchère Nabeul', 'Proche mer, idéal cultures primeurs', 44.00, 'Nabeul', 'Zone Bou Argoub', 650.00, 7500.00, 1800.00, 'file:/C:/Users/zussl/Desktop/finale/firma/src/main/resources/image/i2.png', 1, '2026-02-05 18:23:37'),
(6, 5, 'test 1', 'fghfgh', 14.00, 'dkfhskjfh', 'dgfkgsdkfjg', 123121.00, 21212.00, 0.00, 'file:/C:/Users/zussl/Desktop/finale/firma/src/main/resources/image/i2.png', 1, '2026-02-14 18:57:46'),
(7, 4, 'Serre moderne Ariana', 'Serre équipée irrigation automatique, 2000m²', 0.20, 'Ariana', 'Route Raoued Km 3', 1200.00, 14000.00, 3500.00, 'file:/C:/Users/zussl/Desktop/finale/firma/src/main/resources/image/i2.png', 1, '2026-02-14 21:08:29'),
(8, 5, 'Vignoble Grombalia', 'Vignoble AOC Mornag, cépage Muscat', 12.00, 'Grombalia', 'Domaine Ben Khélifa', 1500.00, 18000.00, 5000.00, 'file:/C:/Users/zussl/Desktop/finale/firma/src/main/resources/image/i2.png', 1, '2026-02-14 21:08:29'),
(9, 6, 'Palmeraie Tozeur', 'Palmeraie 500 palmiers Deglet Nour', 15.00, 'Tozeur', 'Oasis Ibn Chabbat', 2000.00, 24000.00, 6000.00, 'file:/C:/Users/zussl/Desktop/finale/firma/src/main/resources/image/i2.png', 1, '2026-02-14 21:08:29'),
(10, 4, 'Parcelle irriguée Jendouba', 'Terrain plat avec forage, sol fertile', 7.00, 'Jendouba', 'Plaine de Bulla Regia', 600.00, 7000.00, 1800.00, 'file:/C:/Users/zussl/Desktop/finale/firma/src/main/resources/image/i2.png', 1, '2026-02-14 21:08:29'),
(11, 5, 'Prairie Béja', 'Pâturage naturel avec bergerie', 18.00, 'Béja', 'Route Nefza Km 15', 480.00, 5500.00, 1400.00, 'file:/C:/Users/zussl/Desktop/finale/firma/src/main/resources/image/i2.png', 1, '2026-02-14 21:08:29'),
(12, 6, 'Orangeraie Cap Bon', 'Verger agrumes 400 arbres production', 6.00, 'Hammamet', 'Zone Bir Bouregba', 950.00, 11000.00, 2800.00, 'file:/C:/Users/zussl/Desktop/finale/firma/src/main/resources/image/i2.png', 1, '2026-02-14 21:08:29'),
(13, 6, 'Oliveraie Sousse', 'Plantation 200 oliviers Chemlali', 5.00, 'Sousse', 'Route Enfidha', 700.00, 8000.00, 2000.00, 'file:/C:/Users/zussl/Desktop/finale/firma/src/main/resources/image/i2.png', 1, '2026-02-14 21:08:29'),
(14, 4, 'Terrain maraîcher Bizerte', 'Sol sablonneux, idéal cultures précoces', 4.00, 'Bizerte', 'Zone Utique', 550.00, 6500.00, 1600.00, 'file:/C:/Users/zussl/Desktop/finale/firma/src/main/resources/image/i2.png', 1, '2026-02-14 21:08:29'),
(15, 4, 'Complexe serres Sousse', 'Ensemble 4 serres chauffées totalisant 8000m²', 0.80, 'Sousse', 'Zone Agricole Msaken', 2500.00, 30000.00, 8000.00, 'file:/C:/Users/zussl/Desktop/finale/firma/src/main/resources/image/i2.png', 1, '2026-02-14 21:08:29'),
(16, 6, 'Palmeraie Kébili', 'Palmeraie traditionnelle 300 palmiers', 10.00, 'Kébili', 'Oasis Douz', 1800.00, 20000.00, 5000.00, 'file:/C:/Users/zussl/Desktop/finale/firma/src/main/resources/image/i2.png', 1, '2026-02-14 21:08:29'),
(17, 5, 'Ranch Siliana', 'Grand pâturage avec étable et hangar', 25.00, 'Siliana', 'Route Le Kef Km 8', 750.00, 8500.00, 2200.00, 'file:/C:/Users/zussl/Desktop/finale/firma/src/main/resources/image/i2.png', 1, '2026-02-14 21:08:29'),
(18, 6, 'Citronneraie Nabeul', 'Verger citrons et clémentines 250 arbres', 4.50, 'Tunis', 'Ahmed Tlili, Délégation El Omrane Supérieur, Tunis, Gouvernorat Tunis, 1091, Tunisie', 800.00, 9000.00, 2300.00, 'file:/C:/Users/zussl/Desktop/finale/firma/src/main/resources/image/i2.png', 1, '2026-02-14 21:08:29'),
(19, 4, 'Grande parcelle Kairouan', 'Terrain céréalier irrigable par pivot', 30.00, 'Kairouan', 'Plaine El Ala', 1100.00, 12000.00, 3000.00, 'file:/C:/Users/zussl/Desktop/finale/firma/src/main/resources/image/i2.png', 1, '2026-02-14 21:08:29'),
(20, 5, 'Domaine viticole Mornag', 'Vignoble avec cave de vinification', 8.00, 'Ben Arous', 'Collines Mornag', 1400.00, 16000.00, 4000.00, 'file:/C:/Users/zussl/Desktop/finale/firma/src/main/resources/image/i2.png', 1, '2026-02-14 21:08:29'),
(21, 6, 'Verger amandiers Kasserine', 'Plantation amandiers et pistachiers', 7.50, 'Kasserine', 'Route Sbeitla', 500.00, 5800.00, 1500.00, 'file:/C:/Users/zussl/Desktop/finale/firma/src/main/resources/image/i2.png', 1, '2026-02-14 21:08:29');

-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs`
--

CREATE TABLE `utilisateurs` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `adresse` text DEFAULT NULL,
  `ville` varchar(100) DEFAULT NULL,
  `type_user` enum('client','admin','technicien') NOT NULL DEFAULT 'client',
  `mot_de_passe` varchar(255) NOT NULL,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id`, `nom`, `prenom`, `email`, `telephone`, `adresse`, `ville`, `type_user`, `mot_de_passe`, `date_creation`) VALUES
(1, 'naama', 'selmi', 'naama@firma.tn', '55555858', 'bizerte', 'nbhjbh', 'admin', '$2a$12$p70IXEBnrWbZdPTSlaz8FOvUmd/fUQa21kd5qxHk6hB2TU5B0U1Mm', '2026-02-11 00:08:46'),
(4, 'molka', 'ajengui', 'molkaajengui@gmail.com', '54955040', 'aryena tunis', 'Tunis', 'technicien', '$2a$12$p70IXEBnrWbZdPTSlaz8FOvUmd/fUQa21kd5qxHk6hB2TU5B0U1Mm', '2026-03-02 13:12:31'),
(11, 'test', 'test', 'ramezhadhri1@gmail.com', '12312312', 'sfax', 'jbeniana', 'client', '$2a$12$S/Vtj.3sCOIWw3YRpQeRDOYGOfOW8MUJw74TI.m0Gokz/LBc9QGq6', '2026-03-03 14:43:19'),
(12, 'ahmed', 'tt', 'ramezhadhri@ieee.org', '12312312', 'sfax', 'jbeniana', 'client', '$2a$12$x84FWwo0KPOtvXopU4ME0eyJ/HuDEhUMGHTTvgP32jejyqi.Fgfpe', '2026-03-03 15:59:31'),
(13, 'selmi', 'naama', 'naama.selmi@esprit.tn', '21788895', 'test', 'test', 'client', '$2a$12$fOeUPB3m5n8WfqPyHZdlWOfqId0M0URqoslGd5UKkN0GssNAwBw7i', '2026-03-04 01:58:44'),
(14, 'slimani', 'hamza', 'hamza.slimani@esprit.tn', '21788895', 'test', 'test', 'client', '$2a$12$/ViV7qEA15V34YY51TkjzeeFnEawq07v8mpuXKm0tpDJ0hANv2uoO', '2026-03-04 03:10:16');

-- --------------------------------------------------------

--
-- Structure de la table `vehicules`
--

CREATE TABLE `vehicules` (
  `id` int(11) NOT NULL,
  `categorie_id` int(11) NOT NULL,
  `nom` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `marque` varchar(100) DEFAULT NULL,
  `modele` varchar(100) DEFAULT NULL,
  `immatriculation` varchar(50) DEFAULT NULL,
  `prix_jour` decimal(10,2) NOT NULL,
  `prix_semaine` decimal(10,2) DEFAULT NULL,
  `prix_mois` decimal(10,2) DEFAULT NULL,
  `caution` decimal(10,2) DEFAULT 0.00,
  `image_url` varchar(255) DEFAULT NULL,
  `disponible` tinyint(1) DEFAULT 1,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `vehicules`
--

INSERT INTO `vehicules` (`id`, `categorie_id`, `nom`, `description`, `marque`, `modele`, `immatriculation`, `prix_jour`, `prix_semaine`, `prix_mois`, `caution`, `image_url`, `disponible`, `date_creation`) VALUES
(1, 1, 'Tracteur John Deere 75CV', 'Tracteur compact avec cabine climatisée, idéal polyculture', 'John Deere', '5075E', 'TUN-1234', 150.00, 900.00, 3200.00, 500.00, 'file:/C:/Users/zussl/Desktop/finale/firma/src/main/resources/image/i1.png', 1, '2026-02-05 18:23:37'),
(2, 1, 'Tracteur Massey Ferguson 90CV', 'Tracteur puissant pour grands travaux', 'Massey Ferguson', 'MF 5710', 'TUN-5678', 180.00, 1100.00, 3800.00, 600.00, 'file:/C:/Users/zussl/Desktop/finale/firma/src/main/resources/image/i1.png', 1, '2026-02-05 18:23:37'),
(3, 2, 'Moissonneuse Case IH', 'Moissonneuse-batteuse 6m de coupe', 'Case IH', 'Axial-Flow 7140', 'TUN-9012', 300.00, 1800.00, 6500.00, 1000.00, 'file:/C:/Users/zussl/Desktop/finale/firma/src/main/resources/image/i1.png', 1, '2026-02-05 18:23:37'),
(4, 3, 'Camion benne agricole', 'Camion 12 tonnes pour transport récoltes', 'Renault', 'K-380', '123TUN3456', 120.00, 700.00, 2500.00, 400.00, 'file:/C:/Users/zussl/Desktop/finale/firma/src/main/resources/image/i1.png', 0, '2026-02-05 18:23:37'),
(7, 3, 'oooooooo', 'bbb', 'hjjhkgkg', '222222222', '123tun1234', 66666.00, 6666.00, 6666.00, 99999.00, 'file:/C:/Users/zussl/Desktop/finale/firma/src/main/resources/image/i1.png', 1, '2026-02-14 19:47:48'),
(8, 1, 'Tracteur New Holland 65CV', 'Tracteur compact polyvalent avec chargeur frontal', 'New Holland', 'T4.65', '123 TUN 4567', 140.00, 850.00, 3000.00, 450.00, 'file:/C:/Users/zussl/Desktop/finale/firma/src/main/resources/image/i1.png', 1, '2026-02-14 21:08:47'),
(9, 1, 'Tracteur Kubota 50CV', 'Mini-tracteur idéal maraîchage et vergers', 'Kubota', 'M5040', '234 TUN 5678', 110.00, 650.00, 2300.00, 350.00, 'file:/C:/Users/zussl/Desktop/finale/firma/src/main/resources/image/i1.png', 1, '2026-02-14 21:08:47'),
(10, 2, 'Moissonneuse Claas', 'Moissonneuse-batteuse 5m coupe céréales', 'Claas', 'Tucano 320', '345 TUN 6789', 280.00, 1700.00, 6000.00, 900.00, 'file:/C:/Users/zussl/Desktop/finale/firma/src/main/resources/image/i1.png', 1, '2026-02-14 21:08:47'),
(11, 3, 'Camion plateau Isuzu', 'Camion 8 tonnes plateau aluminium', 'Isuzu', 'NPR75', '456 TUN 7890', 100.00, 600.00, 2100.00, 350.00, 'file:/C:/Users/zussl/Desktop/finale/firma/src/main/resources/image/i1.png', 1, '2026-02-14 21:08:47'),
(12, 3, 'Remorque basculante 8T', 'Remorque agricole basculante hydraulique', 'Rolland', 'RollSpeed', '567 TUN 8901', 45.00, 270.00, 950.00, 200.00, 'file:/C:/Users/zussl/Desktop/finale/firma/src/main/resources/image/i1.png', 1, '2026-02-14 21:08:47'),
(13, 1, 'Quad Can-Am agricole', 'Quad 4x4 pour exploitation agricole', 'Can-Am', 'Outlander 570', '678 TUN 9012', 65.00, 380.00, 1350.00, 300.00, 'file:/C:/Users/zussl/Desktop/finale/firma/src/main/resources/image/i1.png', 1, '2026-02-14 21:08:47'),
(14, 3, 'Fourgon Renault Master', 'Fourgon grand volume 15m³', 'Renault', 'Master L3H2', '789 TUN 0123', 85.00, 500.00, 1800.00, 300.00, 'file:/C:/Users/zussl/Desktop/finale/firma/src/main/resources/image/i1.png', 1, '2026-02-14 21:08:47'),
(15, 1, 'Chargeur télescopique JCB', 'Télescopique 7m hauteur levage', 'JCB', '535-95', '890 TUN 1234', 200.00, 1200.00, 4200.00, 700.00, 'file:/C:/Users/zussl/Desktop/finale/firma/src/main/resources/image/i1.png', 1, '2026-02-14 21:08:47'),
(16, 1, 'Tracteur Deutz-Fahr 100CV', 'Tracteur puissant avec prises hydrauliques', 'Deutz-Fahr', '5100', '901 TUN 2345', 190.00, 1150.00, 4000.00, 650.00, 'file:/C:/Users/zussl/Desktop/finale/firma/src/main/resources/image/i1.png', 1, '2026-02-14 21:08:47'),
(17, 3, 'Bétaillère 12 bovins', 'Remorque transport animaux aluminium', 'Ifor Williams', 'TA510', '012 TUN 3456', 175.00, 450.00, 1600.00, 400.00, 'file:/C:/Users/zussl/Desktop/finale/firma/src/main/resources/image/i1.png', 0, '2026-02-14 21:08:47'),
(18, 3, 'Camion citerne eau 10000L', 'Camion citerne pour irrigation mobile', 'MAN', 'TGS 18.360', '111 TUN 4444', 150.00, 900.00, 3200.00, 500.00, 'file:/C:/Users/zussl/Desktop/finale/firma/src/main/resources/image/i1.png', 0, '2026-02-14 21:08:47'),
(19, 3, 'Remorque plateau 12T', 'Grande remorque plateau pour balles', 'Pronar', 'T026', '222 TUN 5555', 55.00, 320.00, 1100.00, 250.00, 'file:/C:/Users/zussl/Desktop/finale/firma/src/main/resources/image/i1.png', 1, '2026-02-14 21:08:47'),
(20, 2, 'Ensileuse automotrice', 'Ensileuse pour maïs et sorgho', 'Krone', 'Big X 480', '333 TUN 6666', 350.00, 2100.00, 7500.00, 1200.00, 'file:/C:/Users/zussl/Desktop/finale/firma/src/main/resources/image/i1.png', 1, '2026-02-14 21:08:47'),
(21, 1, 'Chargeuse compacte Bobcat', 'Mini chargeuse pour travaux divers', 'Bobcat', 'S450', '444 TUN 7777', 130.00, 780.00, 2800.00, 450.00, 'file:/C:/Users/zussl/Desktop/finale/firma/src/main/resources/image/i1.png', 1, '2026-02-14 21:08:47'),
(22, 3, 'Pick-up Toyota Hilux', 'Pick-up 4x4 double cabine', 'Toyota', 'Hilux DC', '555 TUN 8888', 95.00, 560.00, 2000.00, 400.00, 'file:/C:/Users/zussl/Desktop/finale/firma/src/main/resources/image/i1.png', 1, '2026-02-14 21:08:47'),
(24, 3, 'test1', 'fklqsfhdkqfhkh', 'WV', 'stest', '123 TUN 1234', 12.00, 14.00, 20.00, 100.00, 'file:/C:/Users/zussl/Desktop/finale/firma/src/main/resources/image/i1.png', 1, '2026-02-18 09:52:06');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `accompagnants`
--
ALTER TABLE `accompagnants`
  ADD PRIMARY KEY (`id_accompagnant`),
  ADD KEY `fk_accompagnant_participation` (`id_participation`);

--
-- Index pour la table `achats_fournisseurs`
--
ALTER TABLE `achats_fournisseurs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `equipement_id` (`equipement_id`),
  ADD KEY `idx_fournisseur` (`fournisseur_id`),
  ADD KEY `idx_date` (`date_achat`);

--
-- Index pour la table `avis`
--
ALTER TABLE `avis`
  ADD PRIMARY KEY (`id_avis`),
  ADD KEY `fk_avis_technicien` (`id_tech`),
  ADD KEY `fk_avis_demande` (`id_demande`),
  ADD KEY `idx_avis_utilisateur` (`id_utilisateur`),
  ADD KEY `idx_avis_technicien` (`id_tech`),
  ADD KEY `idx_avis_demande` (`id_demande`);

--
-- Index pour la table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `categorie_forum`
--
ALTER TABLE `categorie_forum`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nom` (`nom`);

--
-- Index pour la table `commandes`
--
ALTER TABLE `commandes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_commande` (`numero_commande`),
  ADD KEY `idx_commandes_utilisateur` (`id_utilisateur`);

--
-- Index pour la table `commentaire`
--
ALTER TABLE `commentaire`
  ADD PRIMARY KEY (`id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `fk_commentaire_utilisateur` (`utilisateur_id`);

--
-- Index pour la table `demande`
--
ALTER TABLE `demande`
  ADD PRIMARY KEY (`id_demande`),
  ADD KEY `fk_demande_technicien` (`id_tech`),
  ADD KEY `fk_demande_utilisateur` (`id_utilisateur`);

--
-- Index pour la table `details_commandes`
--
ALTER TABLE `details_commandes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_details_commande` (`commande_id`),
  ADD KEY `idx_details_equipement` (`equipement_id`);

--
-- Index pour la table `equipements`
--
ALTER TABLE `equipements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_equipements_categorie` (`categorie_id`),
  ADD KEY `idx_equipements_fournisseur` (`fournisseur_id`);

--
-- Index pour la table `evenements`
--
ALTER TABLE `evenements`
  ADD PRIMARY KEY (`id_evenement`);

--
-- Index pour la table `fournisseurs`
--
ALTER TABLE `fournisseurs`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_location` (`numero_location`),
  ADD KEY `idx_locations_utilisateur` (`id_utilisateur`),
  ADD KEY `idx_locations_vehicule` (`vehicule_id`),
  ADD KEY `idx_locations_terrain` (`terrain_id`);

--
-- Index pour la table `participations`
--
ALTER TABLE `participations`
  ADD PRIMARY KEY (`id_participation`),
  ADD UNIQUE KEY `unique_participation` (`id_evenement`,`id_utilisateur`),
  ADD KEY `id_utilisateur` (`id_utilisateur`);

--
-- Index pour la table `personne`
--
ALTER TABLE `personne`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `post`
--
ALTER TABLE `post`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_post_utilisateur` (`utilisateur_id`);

--
-- Index pour la table `profile`
--
ALTER TABLE `profile`
  ADD PRIMARY KEY (`id_profile`),
  ADD KEY `fk_profile_utilisateur` (`id_utilisateur`);

--
-- Index pour la table `technicien`
--
ALTER TABLE `technicien`
  ADD PRIMARY KEY (`id_tech`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `id_utilisateur` (`id_utilisateur`),
  ADD UNIQUE KEY `email_2` (`email`),
  ADD UNIQUE KEY `cin` (`cin`),
  ADD KEY `idx_technicien_utilisateur` (`id_utilisateur`);

--
-- Index pour la table `terrains`
--
ALTER TABLE `terrains`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_terrains_categorie` (`categorie_id`);

--
-- Index pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Index pour la table `vehicules`
--
ALTER TABLE `vehicules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `immatriculation` (`immatriculation`),
  ADD KEY `idx_vehicules_categorie` (`categorie_id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `accompagnants`
--
ALTER TABLE `accompagnants`
  MODIFY `id_accompagnant` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT pour la table `achats_fournisseurs`
--
ALTER TABLE `achats_fournisseurs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `avis`
--
ALTER TABLE `avis`
  MODIFY `id_avis` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT pour la table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT pour la table `categorie_forum`
--
ALTER TABLE `categorie_forum`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `commandes`
--
ALTER TABLE `commandes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT pour la table `commentaire`
--
ALTER TABLE `commentaire`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `demande`
--
ALTER TABLE `demande`
  MODIFY `id_demande` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT pour la table `details_commandes`
--
ALTER TABLE `details_commandes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT pour la table `equipements`
--
ALTER TABLE `equipements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT pour la table `evenements`
--
ALTER TABLE `evenements`
  MODIFY `id_evenement` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT pour la table `fournisseurs`
--
ALTER TABLE `fournisseurs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT pour la table `locations`
--
ALTER TABLE `locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `participations`
--
ALTER TABLE `participations`
  MODIFY `id_participation` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT pour la table `personne`
--
ALTER TABLE `personne`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `post`
--
ALTER TABLE `post`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `profile`
--
ALTER TABLE `profile`
  MODIFY `id_profile` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `technicien`
--
ALTER TABLE `technicien`
  MODIFY `id_tech` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `terrains`
--
ALTER TABLE `terrains`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT pour la table `vehicules`
--
ALTER TABLE `vehicules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `accompagnants`
--
ALTER TABLE `accompagnants`
  ADD CONSTRAINT `fk_accompagnant_participation` FOREIGN KEY (`id_participation`) REFERENCES `participations` (`id_participation`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `achats_fournisseurs`
--
ALTER TABLE `achats_fournisseurs`
  ADD CONSTRAINT `achats_fournisseurs_ibfk_1` FOREIGN KEY (`fournisseur_id`) REFERENCES `fournisseurs` (`id`),
  ADD CONSTRAINT `achats_fournisseurs_ibfk_2` FOREIGN KEY (`equipement_id`) REFERENCES `equipements` (`id`);

--
-- Contraintes pour la table `avis`
--
ALTER TABLE `avis`
  ADD CONSTRAINT `fk_avis_demande` FOREIGN KEY (`id_demande`) REFERENCES `demande` (`id_demande`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_avis_technicien` FOREIGN KEY (`id_tech`) REFERENCES `technicien` (`id_tech`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_avis_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `commandes`
--
ALTER TABLE `commandes`
  ADD CONSTRAINT `fk_commandes_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `commentaire`
--
ALTER TABLE `commentaire`
  ADD CONSTRAINT `commentaire_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `post` (`id`),
  ADD CONSTRAINT `fk_commentaire_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `demande`
--
ALTER TABLE `demande`
  ADD CONSTRAINT `fk_demande_technicien` FOREIGN KEY (`id_tech`) REFERENCES `technicien` (`id_tech`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_demande_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `details_commandes`
--
ALTER TABLE `details_commandes`
  ADD CONSTRAINT `fk_details_commandes_commande` FOREIGN KEY (`commande_id`) REFERENCES `commandes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_details_commandes_equipement` FOREIGN KEY (`equipement_id`) REFERENCES `equipements` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `equipements`
--
ALTER TABLE `equipements`
  ADD CONSTRAINT `fk_equipements_categorie` FOREIGN KEY (`categorie_id`) REFERENCES `categories` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_equipements_fournisseur` FOREIGN KEY (`fournisseur_id`) REFERENCES `fournisseurs` (`id`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `locations`
--
ALTER TABLE `locations`
  ADD CONSTRAINT `fk_locations_terrain` FOREIGN KEY (`terrain_id`) REFERENCES `terrains` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_locations_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_locations_vehicule` FOREIGN KEY (`vehicule_id`) REFERENCES `vehicules` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `participations`
--
ALTER TABLE `participations`
  ADD CONSTRAINT `participations_ibfk_1` FOREIGN KEY (`id_evenement`) REFERENCES `evenements` (`id_evenement`) ON DELETE CASCADE,
  ADD CONSTRAINT `participations_ibfk_2` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `post`
--
ALTER TABLE `post`
  ADD CONSTRAINT `fk_post_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `profile`
--
ALTER TABLE `profile`
  ADD CONSTRAINT `fk_profile_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `technicien`
--
ALTER TABLE `technicien`
  ADD CONSTRAINT `fk_technicien_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `terrains`
--
ALTER TABLE `terrains`
  ADD CONSTRAINT `fk_terrains_categorie` FOREIGN KEY (`categorie_id`) REFERENCES `categories` (`id`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `vehicules`
--
ALTER TABLE `vehicules`
  ADD CONSTRAINT `fk_vehicules_categorie` FOREIGN KEY (`categorie_id`) REFERENCES `categories` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
