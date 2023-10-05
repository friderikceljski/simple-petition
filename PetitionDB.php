<?php

require_once "./DBInit.php";

class PetitionDB {

    public static function getAll() {
        $db = DBInit::getInstance();

        $statement = $db->prepare("SELECT author, institution FROM signatures");
        $statement->execute();

        return $statement->fetchAll();
    }
	
	public static function getFirst100() {
        $db = DBInit::getInstance();

        $statement = $db->prepare("SELECT author, institution FROM signatures LIMIT 100");
        $statement->execute();

        return $statement->fetchAll();
    }

    public static function getCount() {
        $db = DBInit::getInstance();

        $statement = $db->prepare("SELECT COUNT(*) FROM signatures");
        $statement->execute();

        return $statement->fetch();
    }

    public static function insert($author, $institution, $email, $consent, $ip) {
        $db = DBInit::getInstance();

        $statement = $db->prepare("INSERT INTO signatures (author, institution, email, consent, IP)
            VALUES (:author, :institution, :email, :consent, :ip)");
        $statement->bindParam(":author", $author);
        $statement->bindParam(":institution", $institution);
        $statement->bindParam(":email", $email);
		$statement->bindParam(":consent", $consent);
		$statement->bindParam(":ip", $ip);
        $statement->execute();
    }

    public static function getCountOfIPs($ip) {
        $db = DBInit::getInstance();

        $statement = $db->prepare("SELECT COUNT(*) FROM signatures WHERE IP=:ip");
        $statement->bindParam(":ip", $ip);
        $statement->execute();

        return $statement->fetch();
    }
    
    public static function insertSpecialty($author, $institution, $email, $ip)
    {
        $db = DBInit::getInstance();

        $statement = $db->prepare("INSERT INTO special_signature (author_field, institution, email_field, IP)
            VALUES (:author, :institution, :email, :ip)");
        $statement->bindParam(":author", $author);
        $statement->bindParam(":institution", $institution);
        $statement->bindParam(":email", $email);
		$statement->bindParam(":ip", $ip);
        $statement->execute();
    }
}