-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost
-- Généré le : mer. 23 avr. 2025 à 12:50
-- Version du serveur : 10.4.28-MariaDB
-- Version de PHP : 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `chomage_db`
--

-- --------------------------------------------------------

--
-- Structure de la table `departements`
--

CREATE TABLE `departements` (
  `code_departement` varchar(10) NOT NULL,
  `nom_departement` varchar(100) DEFAULT NULL,
  `trimestre_1` float DEFAULT NULL,
  `trimestre_2` float DEFAULT NULL,
  `trimestre_3` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `departements`
--

INSERT INTO `departements` (`code_departement`, `nom_departement`, `trimestre_1`, `trimestre_2`, `trimestre_3`) VALUES
('01', 'Ain', 5.5, 5.6, 5.7),
('02', 'Aisne', 10.2, 10.6, 10.8),
('03', 'Allier', 7.9, 8.1, 8),
('04', 'Alpes-de-Haute-Provence', 7.7, 8, 8.2),
('05', 'Hautes-Alpes', 6, 6.2, 6.6),
('06', 'Alpes-Maritimes', 6.7, 6.7, 7.2),
('07', 'Ardèche', 7.6, 7.8, 8.1),
('08', 'Ardennes', 9.7, 10, 9.9),
('09', 'Ariège', 9, 9.3, 9.5),
('10', 'Aube', 9.4, 9.7, 9.8),
('11', 'Aude', 10.1, 10.3, 10.5),
('12', 'Aveyron', 5.4, 5.6, 5.8),
('13', 'Bouches-du-Rhône', 8.3, 8.5, 8.7),
('14', 'Calvados', 6.6, 6.8, 6.6),
('15', 'Cantal', 4.2, 4.3, 4.3),
('16', 'Charente', 7.3, 7.5, 7.2),
('17', 'Charente-Maritime', 6.8, 7, 7),
('18', 'Cher', 6.9, 7.2, 7.3),
('19', 'Corrèze', 5.8, 6, 6.1),
('21', 'Côte-d\'Or', 5.7, 5.9, 5.9),
('22', 'Côtes-d\'Armor', 6.1, 6.3, 6.4),
('23', 'Creuse', 7, 7, 7.3),
('24', 'Dordogne', 6.9, 7.1, 7.3),
('25', 'Doubs', 6.9, 7, 6.7),
('26', 'Drôme', 7.6, 7.9, 8.2),
('27', 'Eure', 6.9, 7.1, 7.2),
('28', 'Eure-et-Loir', 6.8, 6.9, 7.1),
('29', 'Finistère', 6.1, 6.3, 6.4),
('2A', 'Corse-du-Sud', 6.1, 6.2, 6.1),
('2B', 'Haute-Corse', 6.7, 6.8, 7),
('30', 'Gard', 9.5, 10, 10.2),
('31', 'Haute-Garonne', 7.7, 7.7, 7.5),
('32', 'Gers', 5.5, 5.7, 5.7),
('33', 'Gironde', 6.7, 6.8, 6.7),
('34', 'Hérault', 10.1, 10.4, 10.4),
('35', 'Ille-et-Vilaine', 5.8, 5.9, 5.8),
('36', 'Indre', 6.7, 6.9, 7.1),
('37', 'Indre-et-Loire', 6.4, 6.7, 6.7),
('38', 'Isère', 5.9, 6, 6.1),
('39', 'Jura', 5.2, 5.4, 5.3),
('40', 'Landes', 6.5, 6.8, 6.9),
('41', 'Loir-et-Cher', 6.1, 6.3, 6.1),
('42', 'Loire', 7.6, 7.7, 7.6),
('43', 'Haute-Loire', 5.5, 5.6, 5.7),
('44', 'Loire-Atlantique', 5.6, 5.6, 5.6),
('45', 'Loiret', 7.3, 7.5, 7.4),
('46', 'Lot', 7, 7.3, 7.5),
('47', 'Lot-et-Garonne', 7.1, 7.4, 7.4),
('48', 'Lozère', 4.6, 4.8, 4.8),
('49', 'Maine-et-Loire', 6.3, 6.4, 6.4),
('50', 'Manche', 5.3, 5.4, 5.3),
('51', 'Marne', 7.1, 7.3, 7.5),
('52', 'Haute-Marne', 6.5, 6.7, 6.6),
('53', 'Mayenne', 5.2, 5.2, 4.8),
('54', 'Meurthe-et-Moselle', 6.7, 7, 7.2),
('55', 'Meuse', 7.2, 7.4, 7.5),
('56', 'Morbihan', 5.7, 5.9, 6),
('57', 'Moselle', 7, 7.3, 7.3),
('58', 'Nièvre', 6.7, 6.8, 6.8),
('59', 'Nord', 9.3, 9.7, 9.9),
('60', 'Oise', 7.3, 7.5, 7.6),
('61', 'Orne', 6.8, 7, 6.8),
('62', 'Pas-de-Calais', 8.2, 8.6, 8.8),
('63', 'Puy-de-Dôme', 6.3, 6.5, 6.6),
('64', 'Pyrénées-Atlantiques', 5.6, 5.8, 5.8),
('65', 'Hautes-Pyrénées', 7.6, 7.7, 7.9),
('66', 'Pyrénées-Orientales', 12.1, 12.4, 12.4),
('67', 'Bas-Rhin', 6.3, 6.5, 6.5),
('68', 'Haut-Rhin', 7, 7.3, 7.3),
('69', 'Rhône', 6.5, 6.6, 6.6),
('70', 'Haute-Saône', 6.5, 6.7, 6.6),
('71', 'Saône-et-Loire', 6.4, 6.7, 6.7),
('72', 'Sarthe', 7.1, 7.4, 7.2),
('73', 'Savoie', 5.2, 5.4, 5.4),
('74', 'Haute-Savoie', 5.4, 5.5, 5.6),
('75', 'Paris', 5.7, 5.7, 5.9),
('76', 'Seine-Maritime', 7.9, 8.2, 8),
('77', 'Seine-et-Marne', 6.8, 6.9, 6.9),
('78', 'Yvelines', 6.5, 6.6, 6.6),
('79', 'Deux-Sèvres', 5.5, 5.6, 5.4),
('80', 'Somme', 8.1, 8.4, 8.8),
('81', 'Tarn', 7.7, 8, 8.1),
('82', 'Tarn-et-Garonne', 8.6, 8.8, 8.7),
('83', 'Var', 7.1, 7.2, 7.4),
('84', 'Vaucluse', 9.5, 9.7, 9.9),
('85', 'Vendée', 5.3, 5.4, 5.3),
('86', 'Vienne', 6.2, 6.3, 6.2),
('87', 'Haute-Vienne', 6.7, 6.9, 6.7),
('88', 'Vosges', 7.5, 7.8, 7.9),
('89', 'Yonne', 7.1, 7.3, 7.2),
('90', 'Territoire de Belfort', 8.8, 9, 8.5),
('91', 'Essonne', 6.5, 6.5, 6.6),
('92', 'Hauts-de-Seine', 6.1, 6.1, 6.1),
('93', 'Seine-Saint-Denis', 10.2, 10.4, 10.6),
('94', 'Val-de-Marne', 7.2, 7.3, 7.4),
('95', 'Val-d\'Oise', 7.9, 8, 8.3),
('971', 'Guadeloupe', 15.8, 18.7, 19.3),
('972', 'Martinique', 13.5, 10.4, 10.2),
('973', 'Guyane', 15.2, 17.5, 16.5),
('974', 'La Réunion', 16.8, 17.5, 18.7),
('F', 'France hors Mayotte', 7.3, 7.4, 7.5),
('M', 'France métropolitaine', 7.1, 7.2, 7.3);

-- --------------------------------------------------------

--
-- Structure de la table `regions`
--

CREATE TABLE `regions` (
  `code_departement` varchar(10) NOT NULL,
  `nom_departement` varchar(100) DEFAULT NULL,
  `trimestre_1` float DEFAULT NULL,
  `trimestre_2` float DEFAULT NULL,
  `trimestre_3` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `regions`
--

INSERT INTO `regions` (`code_departement`, `nom_departement`, `trimestre_1`, `trimestre_2`, `trimestre_3`) VALUES
('01', 'Guadeloupe', 15.8, 18.7, 19.3),
('02', 'Martinique', 13.5, 10.4, 10.2),
('03', 'Guyane', 15.2, 17.5, 16.5),
('04', 'La Réunion', 16.8, 17.5, 18.7),
('11', 'Île-de-France', 7, 7, 7.1),
('24', 'Centre-Val de Loire', 6.8, 7, 7),
('27', 'Bourgogne-Franche-Comté', 6.5, 6.7, 6.6),
('28', 'Normandie', 7, 7.2, 7.1),
('32', 'Hauts-de-France', 8.7, 9.1, 9.3),
('44', 'Grand Est', 7.1, 7.3, 7.4),
('52', 'Pays de la Loire', 5.9, 6, 5.9),
('53', 'Bretagne', 5.9, 6, 6.1),
('75', 'Nouvelle-Aquitaine', 6.5, 6.7, 6.6),
('76', 'Occitanie', 8.7, 8.9, 8.9),
('84', 'Auvergne-Rhône-Alpes', 6.3, 6.4, 6.5),
('93', 'Provence-Alpes-Côte d\'Azur', 7.7, 7.9, 8.1),
('94', 'Corse', 6.4, 6.5, 6.6),
('F', 'France hors Mayotte', 7.3, 7.4, 7.5),
('M', 'France métropolitaine', 7.1, 7.2, 7.3);

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `departements`
--
ALTER TABLE `departements`
  ADD PRIMARY KEY (`code_departement`);

--
-- Index pour la table `regions`
--
ALTER TABLE `regions`
  ADD PRIMARY KEY (`code_departement`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
