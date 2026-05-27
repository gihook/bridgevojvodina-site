-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 27, 2026 at 08:59 PM
-- Server version: 10.11.15-MariaDB-log
-- PHP Version: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bridgevo_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `dogadjaj`
--

CREATE TABLE `dogadjaj` (
  `Id` int(11) NOT NULL,
  `Naziv` varchar(255) NOT NULL,
  `Opis` varchar(15000) DEFAULT NULL,
  `Datum` int(11) NOT NULL,
  `KorisnikovKlubId` int(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_croatian_ci;

--
-- Dumping data for table `dogadjaj`
--

INSERT INTO `dogadjaj` (`Id`, `Naziv`, `Opis`, `Datum`, `KorisnikovKlubId`) VALUES
(2, 'Prvenstvo Evrope malih federacija u bridžu', 'Odigrava se od 20. do 23. oktobra 2019 u Novom Sadu na Ribarskom ostrvu.', 2019, 1),
(3, 'OPP', 'Otvoreno parsko prvenstvo Novog Sada za 2019.', 2019, 1),
(4, 'Meč za treće mesto Lige srbije za sezonu 2019/2020', 'NS1 protiv NSBK\r\nNovi Sad, Hotel Aurora, 08.10.2020. i 15.10.2020 od 16.45h\r\n2 x 32 borda sa obračunom na 16 bordova.\r\nRezultat: NS1 +17    NSBK  -17', 2020, 1),
(5, 'Tužna vest', 'U subotu, 01.05.2021 napustio nas je naš Branislav Đuričić Đura (1960), istaknuti bridž igrač i reprezentativac. ', 2021, 1),
(6, 'Kvalifikacije za ligu Srbije za region Vojvodne', 'Kvalifikacioni turnir se igra 15.01.2022 u prostorijama Sportskog centra Medijana, Stojana Novakovića 2, sa početkom u 11.00 časova.\r\nIgra se 3 puta 16 bordova. Imp obračun.', 2022, 1),
(7, 'Novi Sad Bridge Festival - 2024', 'May 8 - 12, 2024\r\nFor more details:\r\nhttps://bridgescanner.news/event/novi-sad-bridge-festival-2024', 2024, 1),
(8, 'Novi Sad Bridž festival 2025', 'Od 07.05. do 11.05.2025\r\nRezultati:   https://www.bridzs.hu/hu/versenyek-eredmenyek?vid=9131', 2025, 1);

-- --------------------------------------------------------

--
-- Table structure for table `igrac`
--

CREATE TABLE `igrac` (
  `Id` int(255) NOT NULL,
  `Ime` varchar(150) NOT NULL,
  `Prezime` varchar(150) NOT NULL,
  `KlubId` int(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_croatian_ci;

--
-- Dumping data for table `igrac`
--

INSERT INTO `igrac` (`Id`, `Ime`, `Prezime`, `KlubId`) VALUES
(2, 'Stevan', 'Miškov', 1),
(3, 'Đuro', 'Opačić', 1),
(4, 'Darko', 'Parežanin', 2),
(5, 'Aleksandar', 'Fain', 4),
(6, 'Jovana', 'Maričić', 6),
(7, 'Jonel', 'Simu', 3),
(8, 'Ljubomir', 'Blagojević', 3),
(9, 'Nedeljko', 'Vuleta', 3),
(10, 'Viorel', 'Beka', 3),
(13, 'Dimitraki', 'Zipovski', 2),
(14, 'Goran', 'Radišić', 2),
(15, 'Gorana', 'Mitić', 2),
(16, 'Ivo', 'Đukanović', 2),
(18, 'Selena', 'Pepić', 6),
(19, 'Stojan', 'Važić', 6),
(20, 'Aleksandra', 'Ovuka', 1),
(21, 'Atila', 'Baba', 1),
(22, 'Bogdan', 'Veličković', 1),
(23, 'Ivica', 'Bošnjak', 1),
(24, 'Jovan', 'Poljački', 1),
(26, 'Milina', 'Maksimović', 1),
(27, 'Miloš', 'Vlaškalić', 1),
(29, 'Obrad', 'Medić', 1),
(30, 'Slobodan', 'Gužvica', 1),
(31, 'Tamara', 'Nikolić', 1),
(32, 'Tamara', 'Milutinović', 1),
(33, 'Andrija', 'Gluščević', 4),
(34, 'Branislav', 'Kardašević', 4),
(36, 'Edita', 'Vrbaški', 4),
(37, 'Jovan', 'Mojašević', 4),
(38, 'Miladin', 'Dendić', 1),
(39, 'Miloš', 'Kostevski', 4),
(46, 'Vladan', 'Kardašević', 4),
(48, 'Zoran', 'Ilijašević', 4),
(49, 'Zoran', 'Veselinov', 4),
(50, 'Vuk', 'Trnavac', 1),
(51, 'Nebojša', 'Todorović', 2),
(52, 'Marko', 'Mladenović', 2),
(53, 'Marko', 'Gligorijević', 2),
(54, 'Danko', 'Ukropina', 2),
(55, 'Aleksa', 'Milićević', 2),
(56, 'Stefan', 'Tambur', 2),
(57, 'Zoran', 'Šarić', 1),
(58, 'Dušica', 'Šarić', 1),
(59, 'Anja', 'Ekres', 1),
(60, 'Milica', 'Vojnović', 1),
(61, 'Marko', 'Seizović', 6),
(62, 'Filip', 'Jelić', 6),
(63, 'Matko', 'Ferenca', 6),
(64, 'Emanuel', 'Evačić', 6),
(65, 'Ivan', 'Bilušić', 6),
(66, 'Filip', 'Katušić', 6),
(67, 'Srđan', 'Katušić', 6),
(68, 'Igor', 'Stefanović', 6),
(69, 'Milica', 'Jarić', 6),
(70, 'Aleksandar', 'Stefanović', 6),
(71, 'Aldo Giovani', 'Gerli', 6),
(72, 'Piotr', 'Tuczynski', 6),
(73, 'Pavle', 'Stanojević', 6),
(74, 'Rastko', 'Stanojević', 6),
(75, 'Antonia', 'Vladimir', 6),
(76, 'Branka', 'Hadžić', 1),
(77, 'Nikola', 'Đukanović', 1);

-- --------------------------------------------------------

--
-- Table structure for table `klub`
--

CREATE TABLE `klub` (
  `Id` int(10) NOT NULL,
  `Naziv` varchar(250) NOT NULL,
  `Mesto` varchar(100) NOT NULL,
  `Adresa` varchar(250) NOT NULL,
  `Zastupnik` varchar(250) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `Telefon` varchar(100) NOT NULL,
  `Status` varchar(100) NOT NULL,
  `Link` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_croatian_ci;

--
-- Dumping data for table `klub`
--

INSERT INTO `klub` (`Id`, `Naziv`, `Mesto`, `Adresa`, `Zastupnik`, `Email`, `Telefon`, `Status`, `Link`) VALUES
(1, 'Novosadski bridž klub  NSBK', 'Novi Sad', 'Raše Radujkova 4', 'Ivica Bošnjak', 'ivbdva@gmail.com ', '060 687 8746', 'Aktivan', 'https://www.nsbk.rs/'),
(2, 'Bridž klub NS-1', 'Novi Sad', 'Dragiše Brašovana 6', 'Darko Parežanin', 'darkoparezanin@yahoo.com', '063 508 011', 'Aktivan', ''),
(3, 'Bridž klub BNS', 'Banatsko Novo Selo', 'Maršala Tita 67', 'Jonel Simu', 'jonel.simu@nis.rs', '064 8888 981', 'Aktivan', ''),
(4, 'Bridž klub Panonija', 'Novi Sad', 'Blagoja Parovića 1', 'Vladan Kardašević', 'bkpanonija@gmail.com', '062 216 125', 'Aktivan', 'http://bridzklub.com/'),
(5, 'BK Kikinda', 'Kikinda', '.', 'Stevan Božin', '.', '.', 'Neaktivan', ''),
(6, ' SBU Ekspert', 'Novi Sad', 'Danila Kiša 25', 'Stojan Važić', '@', '+381', 'Aktivan', '');

-- --------------------------------------------------------

--
-- Table structure for table `korisnik`
--

CREATE TABLE `korisnik` (
  `Id` int(11) NOT NULL,
  `Ime` varchar(100) NOT NULL,
  `Prezime` varchar(100) NOT NULL,
  `KorIme` varchar(100) NOT NULL,
  `Lozinka` varchar(100) NOT NULL,
  `StatusId` int(10) NOT NULL,
  `KlubId` int(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_croatian_ci;

--
-- Dumping data for table `korisnik`
--

INSERT INTO `korisnik` (`Id`, `Ime`, `Prezime`, `KorIme`, `Lozinka`, `StatusId`, `KlubId`) VALUES
(1, 'Stevan', 'Miškov', 'asmiskov', 'test', 2, 1),
(2, 'Jovana', 'Maričić', 'jovana', 'test', 1, 6),
(3, 'Nikola', 'Đukanović', 'nikola', 'test', 2, 1);

-- --------------------------------------------------------

--
-- Table structure for table `status`
--

CREATE TABLE `status` (
  `Id` int(10) NOT NULL,
  `Naziv` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_croatian_ci;

--
-- Dumping data for table `status`
--

INSERT INTO `status` (`Id`, `Naziv`) VALUES
(1, 'Admin'),
(2, 'SuperAdmin');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `dogadjaj`
--
ALTER TABLE `dogadjaj`
  ADD PRIMARY KEY (`Id`);

--
-- Indexes for table `igrac`
--
ALTER TABLE `igrac`
  ADD PRIMARY KEY (`Id`);

--
-- Indexes for table `klub`
--
ALTER TABLE `klub`
  ADD PRIMARY KEY (`Id`);

--
-- Indexes for table `korisnik`
--
ALTER TABLE `korisnik`
  ADD PRIMARY KEY (`Id`);

--
-- Indexes for table `status`
--
ALTER TABLE `status`
  ADD PRIMARY KEY (`Id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `dogadjaj`
--
ALTER TABLE `dogadjaj`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `igrac`
--
ALTER TABLE `igrac`
  MODIFY `Id` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT for table `klub`
--
ALTER TABLE `klub`
  MODIFY `Id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `korisnik`
--
ALTER TABLE `korisnik`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `status`
--
ALTER TABLE `status`
  MODIFY `Id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
