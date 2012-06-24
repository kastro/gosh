CREATE TABLE User(
    userId int(11) NOT NULL AUTO_INCREMENT,
    username varchar(100) NOT NULL,
    password varchar(40) NOT NULL,
    email varchar(255) NOT NULL,
    level int(11) NOT NULL,
    PRIMARY KEY (userId),
    UNIQUE KEY username_UNIQUE (username),
    UNIQUE KEY email_UNIQUE (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Table containing administrative users.';

CREATE TABLE Rate(
    rateId int(11) NOT NULL AUTO_INCREMENT,
    name varchar(100) DEFAULT NULL,
    PRIMARY KEY (rateId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='This table holds rates. These refer to their subrates which ';

CREATE TABLE Subrate(
    subrateId int(11) NOT NULL AUTO_INCREMENT,
    rate int(11) NOT NULL,
    start date NOT NULL,
    end date NOT NULL,
    price decimal(10, 0 ) NOT NULL,
    creation datetime NOT NULL,
    lastEdit timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    lastEditor int(11) NOT NULL,
    PRIMARY KEY (subrateId),
    KEY fk_Subrate_1 (rate),
    KEY fk_Subrate_2 (lastEditor),
    CONSTRAINT fk_Subrate_1 FOREIGN KEY (rate)
        REFERENCES Rate (rateId),
    CONSTRAINT fk_Subrate_2 FOREIGN KEY (lastEditor)
        REFERENCES User (userId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='This table holds the actual prices which are being collected';

CREATE TABLE Apartment(
    apartmentId int(11) NOT NULL AUTO_INCREMENT,
    name varchar(45) NOT NULL,
    capacity tinyint(4) NOT NULL DEFAULT '1',
    rate int(11) DEFAULT NULL,
    link varchar(255) DEFAULT NULL,
    hide bit(1) NOT NULL DEFAULT b'0',
    creation datetime NOT NULL,
    lastEdit timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    lastEditor int(11) NOT NULL,
    PRIMARY KEY (apartmentId),
    UNIQUE KEY name_UNIQUE (name),
    KEY fk_Apartment_1 (lastEditor),
    KEY fk_Apartment_2 (rate),
    CONSTRAINT fk_Apartment_1 FOREIGN KEY (lastEditor)
        REFERENCES User (userId),
    CONSTRAINT fk_Apartment_2 FOREIGN KEY (rate)
        REFERENCES Rate (rateId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE Country(
    countryId int(11) NOT NULL AUTO_INCREMENT,
    Name varchar(255) NOT NULL,
    PRIMARY KEY (countryId),
    UNIQUE KEY Name_UNIQUE (Name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Table containing all countries.';

CREATE TABLE Guest(
    guestId int(11) NOT NULL AUTO_INCREMENT,
    firstName varchar(255) NOT NULL,
    middleName varchar(255) DEFAULT NULL,
    lastName varchar(255) NOT NULL,
    gender bit(1) NOT NULL DEFAULT b'0',
    country int(11) NOT NULL,
    rating tinyint(4) NOT NULL DEFAULT '2',
    creation datetime NOT NULL,
    lastEdit timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    lastEditor int(11) NOT NULL,
    PRIMARY KEY (guestId),
    KEY fk_User (lastEditor),
    KEY fk_Country (country),
    CONSTRAINT fk_Country FOREIGN KEY (country)
    	REFERENCES Country (countryId),
    CONSTRAINT fk_User FOREIGN KEY (lastEditor)
        REFERENCES User (userId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Table holding all general information regarding the guests.';

CREATE TABLE GuestDetails (
	guestId int(11) NOT NULL,
	guestDetailType int(4) NOT NULL,
	value  varchar(255) NOT NULL,
    PRIMARY KEY (`guestId`,`value`),
    KEY fk_guestDetailType (guestDetailType),
    KEY fk_guest (guestId),
    CONSTRAINT fk_guestDetailType FOREIGN KEY (guestDetailType)
    	REFERENCES GuestDetailType (guestDetailTypeId),
   	CONSTRAINT fk_guest FOREIGN KEY (guestId)
   		REFERENCES Guest (guestId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Table holding additional information regarding the guests.';


CREATE TABLE GuestDetailType (
	guestDetailTypeId int(4) NOT NULL AUTO_INCREMENT,
	name varchar(255) NOT NULL,
	PRIMARY KEY (guestDetailTypeId),
	UNIQUE KEY name_UNIQUE (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `GuestDetailType` (`guestDetailTypeId`, `name`) VALUES
(1, 'address'),
(2, 'email'),
(3, 'phone'),
(4, 'fax');

CREATE TABLE Occupancy(
    occupancyId int(11) NOT NULL AUTO_INCREMENT,
    guest int(11) NOT NULL,
    apartment int(11) NOT NULL,
    arrival date NOT NULL,
    departure date NOT NULL,
    price decimal(10, 0 ) NOT NULL,
    priceOverride bit(1) NOT NULL DEFAULT b'0',
    payed bit(1) NOT NULL DEFAULT b'0',
    hidden bit(1) NOT NULL DEFAULT b'0',
    creation datetime NOT NULL,
    lastEdit timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    lastEditor int(11) NOT NULL,
    PRIMARY KEY (occupancyId),
    KEY fk_Occupancy_1 (guest),
    KEY fk_Occupancy_2 (apartment),
    KEY fk_Occupancy_3 (lastEditor),
    CONSTRAINT fk_Occupancy_1 FOREIGN KEY (guest)
        REFERENCES Guest (guestId),
    CONSTRAINT fk_Occupancy_2 FOREIGN KEY (apartment)
        REFERENCES Apartment (apartmentId),
    CONSTRAINT fk_Occupancy_3 FOREIGN KEY (lastEditor)
        REFERENCES User (userId)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Table holding all occupancies.';
