<?php
// Display errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection details
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "TruckSystem";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function create_tables($conn) {
   
    $conn->query("SET FOREIGN_KEY_CHECKS = 0;");

    $sql = "
    
    CREATE TABLE SystemRole (
        RoleID INT PRIMARY KEY AUTO_INCREMENT,
        RoleName VARCHAR(50) UNIQUE NOT NULL 
    );

    CREATE TABLE BranchManager (
        ManagerID INT PRIMARY KEY AUTO_INCREMENT,
        FirstName VARCHAR(50) NOT NULL,
        LastName VARCHAR(50) NOT NULL,
        ContactPhone VARCHAR(20) NOT NULL,
        EmailAddress VARCHAR(150) UNIQUE NOT NULL,
        PasswordHash VARCHAR(255) NOT NULL, 
        DateAppointed DATETIME DEFAULT CURRENT_TIMESTAMP
    );

   
    CREATE TABLE Branch (
        BranchID INT PRIMARY KEY AUTO_INCREMENT,
        ManagerID INT UNIQUE,
        CityName VARCHAR(100) NOT NULL,
        ContactPhone VARCHAR(20) NOT NULL,
        IsHeadOffice BOOLEAN DEFAULT FALSE,
        FOREIGN KEY (ManagerID) REFERENCES BranchManager(ManagerID) ON DELETE SET NULL
    );

   
    CREATE TABLE SystemUser (
        UserID INT PRIMARY KEY AUTO_INCREMENT,
        BranchID INT,
        RoleID INT,
        Username VARCHAR(100) UNIQUE NOT NULL,
        EmailAddress VARCHAR(150) UNIQUE NOT NULL,
        FirstName VARCHAR(50) NOT NULL,
        LastName VARCHAR(50) NOT NULL,
        PasswordHash VARCHAR(255) NOT NULL,
        FOREIGN KEY (BranchID) REFERENCES Branch(BranchID),
        FOREIGN KEY (RoleID) REFERENCES SystemRole(RoleID)
    );

   
    CREATE TABLE Truck (
        TruckID INT PRIMARY KEY AUTO_INCREMENT,
        CurrentBranchID INT,
        Status ENUM('AVAILABLE', 'IN_TRANSIT', 'MAINTENANCE') DEFAULT 'AVAILABLE',
        IdleStartTime DATETIME,
        MaxCapacity DECIMAL(10,2) NOT NULL DEFAULT 500.00,
        FOREIGN KEY (CurrentBranchID) REFERENCES Branch(BranchID)
    );

    CREATE TABLE RouteRate (
        RouteID INT PRIMARY KEY AUTO_INCREMENT,
        OriginBranchID INT,
        DestinationBranchID INT,
        CostPerCubicMeter DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (OriginBranchID) REFERENCES Branch(BranchID),
        FOREIGN KEY (DestinationBranchID) REFERENCES Branch(BranchID),
        UNIQUE (OriginBranchID, DestinationBranchID)
    );

    CREATE TABLE Consignment (
        ConsignmentID INT PRIMARY KEY AUTO_INCREMENT,
        OriginBranchID INT,
        DestinationBranchID INT,
        SenderName VARCHAR(255),
        SenderAddress TEXT,
        ReceiverName VARCHAR(255),
        ReceiverAddress TEXT,
        Volume DECIMAL(10,2) NOT NULL, 
        Status ENUM('PENDING_TRUCK', 'DISPATCHED', 'DELIVERED') DEFAULT 'PENDING_TRUCK',
        EntryTimestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (OriginBranchID) REFERENCES Branch(BranchID),
        FOREIGN KEY (DestinationBranchID) REFERENCES Branch(BranchID)
    );

    CREATE TABLE RouteVolumeAccumulator (
        AccumulatorID INT PRIMARY KEY AUTO_INCREMENT,
        OriginBranchID INT NOT NULL,
        DestinationBranchID INT NOT NULL,
        CurrentVolume DECIMAL(10,2) DEFAULT 0.00,
        LastUpdated DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (OriginBranchID) REFERENCES Branch(BranchID),
        FOREIGN KEY (DestinationBranchID) REFERENCES Branch(BranchID),
        UNIQUE (OriginBranchID, DestinationBranchID)
    );

   
    CREATE TABLE Billing (
        InvoiceID INT PRIMARY KEY AUTO_INCREMENT,
        ConsignmentID INT UNIQUE,
        TransportCharge DECIMAL(12,2) NOT NULL,
        InvoiceDate DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (ConsignmentID) REFERENCES Consignment(ConsignmentID)
    );

    CREATE TABLE Dispatch (
        DispatchID INT PRIMARY KEY AUTO_INCREMENT,
        TruckID INT,
        ConsignmentID INT,
        DispatchTimestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (TruckID) REFERENCES Truck(TruckID),
        FOREIGN KEY (ConsignmentID) REFERENCES Consignment(ConsignmentID)
    );

    CREATE TABLE TruckStateLog (
        LogID INT PRIMARY KEY AUTO_INCREMENT,
        TruckID INT,
        State ENUM('AVAILABLE', 'IN_TRANSIT', 'MAINTENANCE') NOT NULL,
        Timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (TruckID) REFERENCES Truck(TruckID)
    );

    CREATE TABLE ConsignmentTracking (
        TrackingID INT PRIMARY KEY AUTO_INCREMENT,
        ConsignmentID INT NOT NULL,
        Status ENUM('REGISTERED', 'PENDING_TRUCK', 'LOADED', 'IN_TRANSIT', 'ARRIVED', 'DELIVERED') NOT NULL,
        CurrentBranchID INT, 
        UpdateTimestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
        UpdatedByUserID INT,
        FOREIGN KEY (ConsignmentID) REFERENCES Consignment(ConsignmentID),
        FOREIGN KEY (CurrentBranchID) REFERENCES Branch(BranchID),
        FOREIGN KEY (UpdatedByUserID) REFERENCES SystemUser(UserID)
    );

    CREATE TABLE OperationalAudit (
        AuditID INT PRIMARY KEY AUTO_INCREMENT,
        UserID INT NOT NULL,
        ActionType VARCHAR(50) NOT NULL,
        TargetTable VARCHAR(50) NOT NULL,
        TargetID INT NOT NULL,
        ActionTimestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (UserID) REFERENCES SystemUser(UserID)
    );
    ";

     try {
        if ($conn->multi_query($sql) === TRUE) {
            while ($conn->more_results() && $conn->next_result());
            echo 'All tables created successfully.';
        } else {
            throw new Exception('Error creating tables: ' . $conn->error);
        }
    } catch (Exception $e) {
        echo '❌ ' . $e->getMessage();
    } finally {
        $conn->close();
    }
}

create_tables($conn);
?>
