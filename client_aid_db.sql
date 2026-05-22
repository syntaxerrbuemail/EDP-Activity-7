-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:5222
-- Generation Time: Apr 18, 2026 at 12:59 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `client_aid_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `address`
--

CREATE TABLE `address` (
  `address_id` int(11) NOT NULL,
  `region` varchar(45) NOT NULL,
  `province` varchar(45) NOT NULL,
  `municipality` varchar(45) NOT NULL,
  `district` varchar(45) NOT NULL,
  `barangay` varchar(45) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `address`
--

INSERT INTO `address` (`address_id`, `region`, `province`, `municipality`, `district`, `barangay`) VALUES
(701, 'Region V', 'Albay', 'Legazpi City', 'District 1', 'Barangay Bigaa'),
(702, 'Region V', 'Albay', 'Daraga', 'District 2', 'Barangay Budiao'),
(703, 'Region V', 'Camarines Sur', 'Naga City', 'District 1', 'Barangay Abella'),
(704, 'Region V', 'Camarines Sur', 'Pili', 'District 2', 'Barangay Palestina'),
(705, 'Region V', 'Camarines Norte', 'Daet', 'District 1', 'Barangay Lag-on'),
(706, 'Region V', 'Sorsogon', 'Sorsogon City', 'District 1', 'Barangay Balogo'),
(707, 'Region V', 'Sorsogon', 'Bulusan', 'District 2', 'Barangay Bagacay'),
(708, 'Region V', 'Masbate', 'Masbate City', 'District 1', 'Barangay Bapor'),
(709, 'Region V', 'Catanduanes', 'Virac', 'District 1', 'Barangay Balite'),
(710, 'Region V', 'Albay', 'Tabaco City', 'District 2', 'Barangay Bangkilingan');

-- --------------------------------------------------------

--
-- Table structure for table `beneficiary`
--

CREATE TABLE `beneficiary` (
  `client_relationship` varchar(45) NOT NULL,
  `beneficiary_id` int(11) NOT NULL,
  `category` varchar(45) NOT NULL,
  `subcategory` varchar(45) NOT NULL,
  `client_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `beneficiary`
--

INSERT INTO `beneficiary` (`client_relationship`, `beneficiary_id`, `category`, `subcategory`, `client_id`) VALUES
('Son', 101, 'Financial', 'Education Assistance', 201),
('Daughter', 102, 'Financial', 'Medical Assistance', 202),
('Brother', 103, 'Financial', 'Burial Assistance', 203),
('Sister', 104, 'Financial', 'Disability Assistance', 204),
('Father', 105, 'Financial', 'Medical Assistance', 205),
('Mother', 106, 'Financial', 'Education Assistance', 206),
('Son', 107, 'Financial', 'Burial Assistance', 207),
('Daughter', 108, 'Financial', 'Disability Assistance', 208),
('Brother', 109, 'Financial', 'Medical Assistance', 209),
('Sister', 110, 'Financial', 'Education Assistance', 210);

-- --------------------------------------------------------

--
-- Stand-in structure for view `beneficiary_occupation`
-- (See below for the actual view)
--
CREATE TABLE `beneficiary_occupation` (
`first_name` varchar(45)
,`last_name` varchar(45)
,`occupation` varchar(45)
,`estimated_monthly_income` varchar(45)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `beneficiary_records`
-- (See below for the actual view)
--
CREATE TABLE `beneficiary_records` (
`first_name` varchar(45)
,`last_name` varchar(45)
,`category` varchar(45)
,`subcategory` varchar(45)
,`client_relationship` varchar(45)
,`client_name` varchar(91)
);

-- --------------------------------------------------------

--
-- Table structure for table `birthplace`
--

CREATE TABLE `birthplace` (
  `birthplace_id` int(11) NOT NULL,
  `province` varchar(45) NOT NULL,
  `municipality` varchar(45) NOT NULL,
  `city` varchar(45) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `birthplace`
--

INSERT INTO `birthplace` (`birthplace_id`, `province`, `municipality`, `city`) VALUES
(801, 'Albay', 'Legazpi City', 'Legazpi City'),
(802, 'Albay', 'Daraga', 'Daraga'),
(803, 'Camarines Sur', 'Naga City', 'Naga City'),
(804, 'Camarines Sur', 'Pili', 'Pili'),
(805, 'Camarines Norte', 'Daet', 'Daet'),
(806, 'Sorsogon', 'Sorsogon City', 'Sorsogon City'),
(807, 'Sorsogon', 'Bulusan', 'Bulusan'),
(808, 'Masbate', 'Masbate City', 'Masbate City'),
(809, 'Catanduanes', 'Virac', 'Virac'),
(810, 'Albay', 'Tabaco City', 'Tabaco City');

-- --------------------------------------------------------

--
-- Table structure for table `client`
--

CREATE TABLE `client` (
  `client_id` int(11) NOT NULL,
  `religion` varchar(45) DEFAULT NULL,
  `philhealt_num` varchar(45) NOT NULL,
  `contact_num` varchar(45) NOT NULL,
  `nationality` varchar(45) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `client`
--

INSERT INTO `client` (`client_id`, `religion`, `philhealt_num`, `contact_num`, `nationality`) VALUES
(201, 'Catholic', 'PH-201-2023', '0909090909', 'Filipino'),
(202, 'Catholic', 'PH-202-2023', '09182345678', 'Filipino'),
(203, 'Islam', 'PH-203-2023', '09193456789', 'Filipino'),
(204, 'Catholic', 'PH-204-2023', '09204567890', 'Filipino'),
(205, 'Born Again', 'PH-205-2023', '09215678901', 'Filipino'),
(206, 'Catholic', 'PH-206-2023', '09226789012', 'Filipino'),
(207, 'Iglesia ni Cristo', 'PH-207-2023', '09237890123', 'Filipino'),
(208, 'Catholic', 'PH-208-2023', '09248901234', 'Filipino'),
(209, 'Protestant', 'PH-209-2023', '09259012345', 'Filipino'),
(210, 'Catholic', 'PH-210-2023', '09260123456', 'Filipino');

--
-- Triggers `client`
--
DELIMITER $$
CREATE TRIGGER `deleteClient` BEFORE DELETE ON `client` FOR EACH ROW BEGIN

    -- This trigger ensures that when a client record is deleted, all related records connected to that client are also removed
    -- Since the client is linked to multiple tables such as: person, beneficiary, address, and birthplace, deleting only the client record may leave orphan records in the database
    -- Therefore, I implement this trigger which performs a cascading cleanup by manually deleting the related records in the correct order
   
    -- VARIABLE DECLARATIONS
    -- These variables temporarily store the necessary values of related records so that they can be deleted later in the process
    DECLARE toDeleteAddressID    INT;
    DECLARE toDeleteBirthplaceID INT;
    DECLARE toBeneAddressID      INT;
    DECLARE toBeneBirthplaceID   INT;
    DECLARE toBenePersonID       INT; 


	-- TEMPORARILY DISABLE FOREIGN KEY CONSTRAINTS
    -- This allows the trigger to delete records that are linked by foreign keys without violating referential integrity during the deletion process
    SET FOREIGN_KEY_CHECKS = 0;


    -- RETRIEVE CLIENT'S PERSON DETAILS
    -- The system fetches the address and birthplace associated with the client so they can also be deleted later
    SELECT address_id, birthplace_id
    INTO toDeleteAddressID, toDeleteBirthplaceID
    FROM person
    WHERE person_id = OLD.client_id;


    -- RETRIEVE BENEFICIARY'S PERSON DETAILS
    -- If the client has a beneficiary, this query retrieves the beneficiary's person ID, address ID, and birthplace ID.These will also be deleted to avoid leaving unused records
    SELECT b.beneficiary_id, p.address_id, p.birthplace_id
    INTO toBenePersonID, toBeneAddressID, toBeneBirthplaceID
    FROM beneficiary b
    JOIN person p ON p.person_id = b.beneficiary_id
    WHERE b.client_id = OLD.client_id;


    -- DELETION PROCESS
    -- The following steps delete records in the correct order to maintain database integrity
    
    -- 1. Delete the beneficiary record linked to the client
    DELETE FROM beneficiary
    WHERE client_id = OLD.client_id;

    -- 2. Delete the beneficiary's person information
    DELETE FROM person
    WHERE person_id = toBenePersonID;

    -- 3. Delete the beneficiary's address record
    DELETE FROM address
    WHERE address_id = toBeneAddressID;

    -- 4. Delete the beneficiary's birthplace record
    DELETE FROM birthplace
    WHERE birthplace_id = toBeneBirthplaceID;

    -- 5. Delete the client's person record
    DELETE FROM person
    WHERE person_id = OLD.client_id;

    -- 6. Delete the client's address record
    DELETE FROM address
    WHERE address_id = toDeleteAddressID;

    -- 7. Delete the client's birthplace record
    DELETE FROM birthplace
    WHERE birthplace_id = toDeleteBirthplaceID;


    -- RE-ENABLE FOREIGN KEY CONSTRAINTS
    -- After all necessary deletions are completed, foreign key checking is turned back on to maintain database integrity
    SET FOREIGN_KEY_CHECKS = 1;

END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `updatePhilhealthNum` BEFORE UPDATE ON `client` FOR EACH ROW BEGIN

    -- This trigger ensures that the PhilHealth number being updated follows the correct format and remains unique among all clients in the system
    -- It performs two main validations:
    -- 1. Format validation using Regular Expression (REGEXP)
    -- 2. Duplicate checking to prevent two clients from having the same PhilHealth number

    -- VALIDATE PHILHEALTH NUMBER FORMAT
    -- The expected format (PH-000-0000)
    -- If the format does not match the required pattern, the system throws an error and stops the update
    IF NEW.philhealt_num NOT REGEXP '^PH-[0-9]{3}-[0-9]{4}$' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Invalid format of Philhealth Number use the valid format (PH-000-0000)';
    END IF; 

    -- CHECK FOR DUPLICATE PHILHEALTH NUMBERS
    -- This query checks whether another client already has the same PhilHealth number
    -- The condition excludes the current client being updated to avoid false duplicate detection
    IF EXISTS (
        SELECT 1 
        FROM client 
        WHERE NEW.philhealt_num = philhealt_num
        AND OLD.client_id != client_id
    ) THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = "The Philhealth Number already Occured";
    END IF;

END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `client_records`
-- (See below for the actual view)
--
CREATE TABLE `client_records` (
`first_name` varchar(45)
,`last_name` varchar(45)
,`philhealt_num` varchar(45)
,`contact_num` varchar(45)
);

-- --------------------------------------------------------

--
-- Table structure for table `person`
--

CREATE TABLE `person` (
  `person_id` int(11) NOT NULL,
  `first_name` varchar(45) NOT NULL,
  `last_name` varchar(45) NOT NULL,
  `middle_name` varchar(45) DEFAULT NULL,
  `birthdate` varchar(45) NOT NULL,
  `birthplace_id` int(11) NOT NULL,
  `civil_status` varchar(45) NOT NULL,
  `sex` varchar(45) NOT NULL,
  `estimated_monthly_income` varchar(45) NOT NULL,
  `address_id` int(11) NOT NULL,
  `occupation` varchar(45) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `person`
--

INSERT INTO `person` (`person_id`, `first_name`, `last_name`, `middle_name`, `birthdate`, `birthplace_id`, `civil_status`, `sex`, `estimated_monthly_income`, `address_id`, `occupation`) VALUES
(101, 'Ramon', 'Castillo', 'Vergara', '1993-05-20', 801, 'Single', 'Male', '13200', 701, 'Programmer'),
(102, 'Elena', 'Bautista', 'Soriano', '1987-08-15', 802, 'Married', 'Female', '22000', 702, 'Lawyer'),
(103, 'Diego', 'Fernandez', 'Guevara', '1996-03-10', 803, 'Single', 'Male', '9000', 703, 'Carpenter'),
(104, 'Sofia', 'Gomez', 'Salazar', '1980-12-25', 804, 'Married', 'Female', '28000', 704, 'Dentist'),
(105, 'Luis', 'Hernandez', 'Miranda', '1991-07-04', 805, 'Single', 'Male', '16000', 705, 'Electrician'),
(106, 'Carmen', 'Jimenez', 'Dela Rosa', '1986-01-30', 806, 'Married', 'Female', '19000', 706, 'Pharmacist'),
(107, 'Roberto', 'Luna', 'Espiritu', '1979-09-08', 807, 'Widowed', 'Male', '32000', 707, 'Police'),
(108, 'Isabel', 'Magno', 'Ocampo', '1994-11-17', 808, 'Single', 'Female', '11000', 708, 'Cashier'),
(109, 'Andres', 'Nieto', 'Padilla', '1982-04-22', 809, 'Married', 'Male', '40000', 709, 'Architect'),
(110, 'Theresa', 'Ortega', 'Quizon', '1997-06-13', 810, 'Single', 'Female', '14000', 710, 'Social Worker'),
(201, 'Juan', 'Dela Cruz', 'Santos', '1995-01-15', 801, 'Single', 'Male', '12000', 701, 'Student'),
(202, 'Maria', 'Reyes', 'Lopez', '1990-03-22', 802, 'Married', 'Female', '18000', 702, 'Teacher'),
(203, 'Pedro', 'Santos', 'Garcia', '1985-07-10', 803, 'Married', 'Male', '25000', 703, 'Driver'),
(204, 'Ana', 'Cruz', 'Mendoza', '2000-11-05', 804, 'Single', 'Female', '8000', 704, 'Vendor'),
(205, 'Jose', 'Ramos', 'Bautista', '1978-06-30', 805, 'Married', 'Male', '35000', 705, 'Engineer'),
(206, 'Rosa', 'Flores', 'Torres', '1992-09-14', 806, 'Single', 'Female', '15000', 706, 'Nurse'),
(207, 'Carlo', 'Villanueva', 'Castillo', '1988-12-01', 807, 'Widowed', 'Male', '20000', 707, 'Farmer'),
(208, 'Liza', 'Morales', 'Rivera', '1975-04-18', 808, 'Married', 'Female', '45000', 708, 'Doctor'),
(209, 'Mark', 'Aquino', 'Diaz', '1998-08-25', 809, 'Single', 'Male', '10000', 709, 'Mechanic'),
(210, 'Grace', 'Pascual', 'Navarro', '1983-02-11', 810, 'Married', 'Female', '30000', 710, 'Accountant');

--
-- Triggers `person`
--
DELIMITER $$
CREATE TRIGGER `nameFormater` BEFORE INSERT ON `person` FOR EACH ROW BEGIN
    -- TRIGGER PURPOSE:
    -- This trigger automatically formats the person's name fields (first_name, middle_name, last_name) into
    -- Title Case format before the record is inserted
    -- Example: gabARDA -> Gabarda, aRvie -> Arvie, RivERA -> Rivera
    -- This ensures consistency and improves the readability of stored names in the database

    -- FORMAT THE MIDDLE NAME
    -- This first checks whether the middle_name is NULL. If it exists, it converts the first letter to uppercase and the remaining letters to lowercase
    IF NEW.middle_name IS NOT NULL THEN
        SET NEW.middle_name = CONCAT (
            UPPER (SUBSTRING(NEW.middle_name, 1, 1)), 
            LOWER (SUBSTRING(NEW.middle_name, 2))
        );
    END IF;

    -- FORMAT THE FIRST NAME
    -- Converts the first letter to uppercase and the rest of the characters to lowercase
    SET NEW.first_name = CONCAT (
        UPPER (SUBSTRING(NEW.first_name, 1, 1)), 
        LOWER (SUBSTRING(NEW.first_name, 2))
    );

    -- FORMAT THE LAST NAME
    -- Ensures that the last name also follows the Title Case format for consistency across the database
    SET NEW.last_name = CONCAT (
        UPPER (SUBSTRING(NEW.last_name, 1, 1)), 
        LOWER (SUBSTRING(NEW.last_name, 2))
    );

END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure for view `beneficiary_occupation`
--
DROP TABLE IF EXISTS `beneficiary_occupation`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `beneficiary_occupation`  AS SELECT `person`.`first_name` AS `first_name`, `person`.`last_name` AS `last_name`, `person`.`occupation` AS `occupation`, `person`.`estimated_monthly_income` AS `estimated_monthly_income` FROM (`person` join `beneficiary` `b` on(`b`.`beneficiary_id` = `person`.`person_id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `beneficiary_records`
--
DROP TABLE IF EXISTS `beneficiary_records`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `beneficiary_records`  AS SELECT `p`.`first_name` AS `first_name`, `p`.`last_name` AS `last_name`, `b`.`category` AS `category`, `b`.`subcategory` AS `subcategory`, `b`.`client_relationship` AS `client_relationship`, concat(`cl_person`.`first_name`,' ',`cl_person`.`last_name`) AS `client_name` FROM (((`person` `p` join `beneficiary` `b` on(`b`.`beneficiary_id` = `p`.`person_id`)) join `client` `c` on(`c`.`client_id` = `b`.`client_id`)) join `person` `cl_person` on(`cl_person`.`person_id` = `c`.`client_id`)) ;

-- --------------------------------------------------------

--
-- Structure for view `client_records`
--
DROP TABLE IF EXISTS `client_records`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `client_records`  AS SELECT `p`.`first_name` AS `first_name`, `p`.`last_name` AS `last_name`, `c`.`philhealt_num` AS `philhealt_num`, `c`.`contact_num` AS `contact_num` FROM (`person` `p` join `client` `c` on(`c`.`client_id` = `p`.`person_id`)) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `address`
--
ALTER TABLE `address`
  ADD PRIMARY KEY (`address_id`);

--
-- Indexes for table `beneficiary`
--
ALTER TABLE `beneficiary`
  ADD PRIMARY KEY (`beneficiary_id`,`client_id`),
  ADD KEY `fk_beneficiary_client1_idx` (`client_id`);

--
-- Indexes for table `birthplace`
--
ALTER TABLE `birthplace`
  ADD PRIMARY KEY (`birthplace_id`);

--
-- Indexes for table `client`
--
ALTER TABLE `client`
  ADD PRIMARY KEY (`client_id`);

--
-- Indexes for table `person`
--
ALTER TABLE `person`
  ADD PRIMARY KEY (`person_id`,`birthplace_id`,`address_id`),
  ADD KEY `fk_person_birthplace1_idx` (`birthplace_id`),
  ADD KEY `fk_person_address1_idx` (`address_id`);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `beneficiary`
--
ALTER TABLE `beneficiary`
  ADD CONSTRAINT `fk_beneficiary_client1` FOREIGN KEY (`client_id`) REFERENCES `client` (`client_id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_beneficiary_person1` FOREIGN KEY (`beneficiary_id`) REFERENCES `person` (`person_id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints for table `client`
--
ALTER TABLE `client`
  ADD CONSTRAINT `fk_client_person` FOREIGN KEY (`client_id`) REFERENCES `person` (`person_id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints for table `person`
--
ALTER TABLE `person`
  ADD CONSTRAINT `fk_person_address1` FOREIGN KEY (`address_id`) REFERENCES `address` (`address_id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_person_birthplace1` FOREIGN KEY (`birthplace_id`) REFERENCES `birthplace` (`birthplace_id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `person_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` varchar(20) DEFAULT 'admin',
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `fk_users_person` (`person_id`),
  CONSTRAINT `fk_users_person` FOREIGN KEY (`person_id`) REFERENCES `person` (`person_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
