<?php
include_once("AccessBDD.php");

/**
 * Classe de construction des requêtes SQL
 * hérite de AccessBDD qui contient les requêtes de base
 * Pour ajouter une requête :
 * - créer la fonction qui crée une requête (prendre modèle sur les fonctions 
 *   existantes qui ne commencent pas par 'traitement')
 * - ajouter un 'case' dans un des switch des fonctions redéfinies 
 * - appeler la nouvelle fonction dans ce 'case'
 */
class MyAccessBDD extends AccessBDD {
	    
    /**
     * constructeur qui appelle celui de la classe mère
     */
    public function __construct(){
        try{
            parent::__construct();
        }catch(\Exception $e){
            throw $e;
        }
    }

    /**
     * demande de recherche
     * @param string $table
     * @param array|null $champs nom et valeur de chaque champ
     * @return array|null tuples du résultat de la requête ou null si erreur
     * @override
     */	
    protected function traitementSelect(string $table, ?array $champs) : ?array{
        switch($table){
            case "abonnement" :
                return $this->selectAbonnementsRevue($champs);
            case "livre" :
                return $this->selectAllLivres();
            case "dvd" :
                return $this->selectAllDvd();
            case "revue" :
                return $this->selectAllRevues();
            case "exemplaire" :
                return $this->selectExemplairesRevue($champs);
            case "genre" :
            case "public" :
            case "rayon" :
            case "etat" :
                // select portant sur une table contenant juste id et libelle
                return $this->selectTableSimple($table);
            case "commandedocument" :
                return $this->selectCommandesDocument($champs);
            case "" :
                // return $this->uneFonction(parametres);
            default:
                // cas général
                return $this->selectTuplesOneTable($table, $champs);
        }	
    }

    /**
     * demande d'ajout (insert)
     * @param string $table
     * @param array|null $champs nom et valeur de chaque champ
     * @return int|null nombre de tuples ajoutés ou null si erreur
     * @override
     */	
    protected function traitementInsert(string $table, ?array $champs) : ?int{
        switch($table){
            case "commandedocument" :
                return $this->insertCommandeDocument($champs);
            case "revue" :
                return $this->insertRevue($champs);
            case "livre" :
                return $this->insertLivre($champs);
            case "dvd" :
                return $this->insertDvd($champs);
            case "abonnement" :
                return $this->insertAbonnement($champs);
            case "" :
                // return $this->uneFonction(parametres);
            default:                    
                // cas général
                return $this->insertOneTupleOneTable($table, $champs);	
        }
    }
    
    /**
     * demande de modification (update)
     * @param string $table
     * @param string|null $id
     * @param array|null $champs nom et valeur de chaque champ
     * @return int|null nombre de tuples modifiés ou null si erreur
     * @override
     */	
    protected function traitementUpdate(string $table, ?string $id, ?array $champs) : ?int{
        switch($table){
            case "commandedocument" :
                return $this->updateCommandeDocument($id, $champs);
            case "revue" :
                return $this->updateRevue($id, $champs);
            case "dvd" :
                return $this->updateDvd($id, $champs);
            case "livre" :
                return $this->updateLivre($id, $champs);
            case "" :
                // return $this->uneFonction(parametres);
            default:                    
                // cas général
                return $this->updateOneTupleOneTable($table, $id, $champs);
        }	
    }  
    
    /**
     * demande de suppression (delete)
     * @param string $table
     * @param array|null $champs nom et valeur de chaque champ
     * @return int|null nombre de tuples supprimés ou null si erreur
     * @override
     */	
    protected function traitementDelete(string $table, ?array $champs) : ?int{
        switch($table){
            case "abonnement" :
                return $this->deleteAbonnement($champs);
            case "revue" :
                return $this->deleteRevue($champs);
            case "dvd" :
                return $this->deleteDvd($champs);
            case "livre" :
                return $this->deleteLivre($champs);
            case "commandedocument" :
                return $this->deleteCommandeDocument($champs);
            case "" :
                // return $this->uneFonction(parametres);
            default:                    
                // cas général
                return $this->deleteTuplesOneTable($table, $champs);	
        }
    }
    
        
    /**
     * récupère les tuples d'une seule table
     * @param string $table
     * @param array|null $champs
     * @return array|null 
     */
    private function selectTuplesOneTable(string $table, ?array $champs) : ?array{
        if(empty($champs)){
            // tous les tuples d'une table
            $requete = "select * from $table;";
            return $this->conn->queryBDD($requete);  
        }else{
            // tuples spécifiques d'une table
            $requete = "select * from $table where ";
            foreach ($champs as $key => $value){
                $requete .= "$key=:$key and ";
            }
            // (enlève le dernier and)
            $requete = substr($requete, 0, strlen($requete)-5);	          
            return $this->conn->queryBDD($requete, $champs);
        }
    }
    


    /**
     * demande d'ajout (insert) d'un tuple dans une table
     * @param string $table
     * @param array|null $champs
     * @return int|null nombre de tuples ajoutés (0 ou 1) ou null si erreur
     */	
    private function insertOneTupleOneTable(string $table, ?array $champs) : ?int{
        if(empty($champs)){
            return null;
        }
        // construction de la requête
        $requete = "insert into $table (";
        foreach ($champs as $key => $value){
            $requete .= "$key,";
        }
        // (enlève la dernière virgule)
        $requete = substr($requete, 0, strlen($requete)-1);
        $requete .= ") values (";
        foreach ($champs as $key => $value){
            $requete .= ":$key,";
        }
        // (enlève la dernière virgule)
        $requete = substr($requete, 0, strlen($requete)-1);
        $requete .= ");";
        return $this->conn->updateBDD($requete, $champs);
    }

    /**
     * demande d'ajout d'un livre dans les tables document, livres_dvd et livre
     * @param array|null $champs nom et valeur des champs du livre
     * @return int|null nombre de tuples ajoutés ou null si erreur
     */
    private function insertLivre(?array $champs) : ?int{
        if(empty($champs))
        {
            return null;
        }

        $existant = $this->selectTuplesOneTable("document", ["id" => $champs["id"]]);
        if(!empty($existant))
        {
            return -1;
        }
        
        $document = [
            "id" => $champs["id"],
            "titre" => $champs["titre"],
            "image" => $champs["image"],
            "idGenre" => $champs["idGenre"],
            "idPublic" => $champs["idPublic"],
            "idRayon" => $champs["idRayon"]
        ];

        $livresDvd = [
            "id" => $champs["id"]
        ];

        $livre = [
            "id" => $champs["id"],
            "ISBN" => $champs["isbn"],
            "auteur" => $champs["auteur"],
            "collection" => $champs["collection"]
        ];

        try{
            $this->conn->beginTransaction();

            $nb1 = $this->insertOneTupleOneTable("document", $document);
            if($nb1 !== 1){
                $this->conn->rollBack();
                return null;
            }

            $nb2 = $this->insertOneTupleOneTable("livres_dvd", $livresDvd);
            if($nb2 !== 1){
                $this->conn->rollBack();
                return null;
            }

            $nb3 = $this->insertOneTupleOneTable("livre", $livre);
            if($nb3 !== 1){
                $this->conn->rollBack();
                return null;
            }

            $this->conn->commit();
            return 1;
        }catch(Exception $e){
            $this->conn->rollBack();
            return null;
        }
    }
    
    /**
    * demande d'ajout d'un dvd dans les tables document, livres_dvd et dvd
    * @param array|null $champs nom et valeur des champs du dvd
    * @return int|null nombre de tuples ajoutés ou null si erreur
    */
    private function insertDvd(?array $champs) : ?int{
        if(empty($champs)){
            return null;
        }

        $existant = $this->selectTuplesOneTable("document", ["id" => $champs["id"]]);
        if(!empty($existant)){
            return -1;
        }

        $document = [
            "id" => $champs["id"],
            "titre" => $champs["titre"],
            "image" => $champs["image"],
            "idGenre" => $champs["idGenre"],
            "idPublic" => $champs["idPublic"],
            "idRayon" => $champs["idRayon"]
        ];

        $livresDvd = [
            "id" => $champs["id"]
        ];

        $dvd = [
            "id" => $champs["id"],
            "synopsis" => $champs["synopsis"],
            "realisateur" => $champs["realisateur"],
            "duree" => $champs["duree"]
        ];

        try{
            $this->conn->beginTransaction();

            $nbDocument = $this->insertOneTupleOneTable("document", $document);
            if($nbDocument !== 1){
                $this->conn->rollBack();
                return null;
            }

            $nbLivresDvd = $this->insertOneTupleOneTable("livres_dvd", $livresDvd);
            if($nbLivresDvd !== 1){
                $this->conn->rollBack();
                return null;
            }

            $nbDvd = $this->insertOneTupleOneTable("dvd", $dvd);
            if($nbDvd !== 1){
                $this->conn->rollBack();
                return null;
            }

            $this->conn->commit();
            return 1;
        }catch(Exception $e){
            $this->conn->rollBack();
            return null;
        }
    }

    /**
    * demande d'ajout d'une revue dans les tables document et revue
    * @param array|null $champs nom et valeur des champs de la revue
    * @return int|null nombre de tuples ajoutés ou null si erreur
    */
    private function insertRevue(?array $champs) : ?int{
        if(empty($champs)){
            return null;
        }

        $existant = $this->selectTuplesOneTable("document", ["id" => $champs["id"]]);
        if(!empty($existant)){
            return -1;
        }

        $document = [
            "id" => $champs["id"],
            "titre" => $champs["titre"],
            "image" => $champs["image"],
            "idGenre" => $champs["idGenre"],
            "idPublic" => $champs["idPublic"],
            "idRayon" => $champs["idRayon"]
        ];

        $revue = [
            "id" => $champs["id"],
            "periodicite" => $champs["periodicite"],
            "delaiMiseADispo" => $champs["delaiMiseADispo"]
        ];

        try{
            $this->conn->beginTransaction();

            $nbDocument = $this->insertOneTupleOneTable("document", $document);
            if($nbDocument !== 1){
                $this->conn->rollBack();
                return null;
            }

            $nbRevue = $this->insertOneTupleOneTable("revue", $revue);
            if($nbRevue !== 1){
                $this->conn->rollBack();
                return null;
            }

            $this->conn->commit();
            return 1;
        }catch(Exception $e){
            $this->conn->rollBack();
            return null;
        }
    }

    /**
    * demande d'ajout d'une commande de livre ou dvd
    * @param array|null $champs nom et valeur des champs
    * @return int|null nombre de tuples ajoutés ou null si erreur
    */
    private function insertCommandeDocument(?array $champs) : ?int{
        if(empty($champs)){
            return null;
        }
        
        $existant = $this->selectTuplesOneTable("commande", ["id" => $champs["id"]]);
        if(!empty($existant)){
            return -1;
        }
        
        $commande = [
            "id" => $champs["id"],
            "dateCommande" => $champs["dateCommande"],
            "montant" => $champs["montant"]
        ];

        $commandeDocument = [
            "id" => $champs["id"],
            "nbExemplaire" => $champs["nbExemplaire"],
            "idLivreDvd" => $champs["idLivreDvd"],
            "idSuivi" => "00001"
        ];

        try{
            $this->conn->beginTransaction();

            $nbCommande = $this->insertOneTupleOneTable("commande", $commande);
            if($nbCommande !== 1){
                $this->conn->rollBack();
                return null;
            }

            $nbCommandeDocument = $this->insertOneTupleOneTable("commandedocument", $commandeDocument);
            if($nbCommandeDocument !== 1){
                $this->conn->rollBack();
                return null;
            }

            $this->conn->commit();
            return 1;
        }catch(Exception $e){
            $this->conn->rollBack();
            return null;
        }
    }

    /**
     * demande d'ajout d'un abonnement
     * @param array|null $champs nom et valeur des champs
     * @return int|null nombre de tuples ajoutés ou null si erreur
     */
    private function insertAbonnement(?array $champs) : ?int{
        if(empty($champs)){
            return null;
        }

        $existant = $this->selectTuplesOneTable("commande", ["id" => $champs["id"]]);
        if(!empty($existant)){
            return -1;
        }

        $commande = [
            "id" => $champs["id"],
            "dateCommande" => $champs["dateCommande"],
            "montant" => $champs["montant"]
        ];

        $abonnement = [
            "id" => $champs["id"],
            "dateFinAbonnement" => $champs["dateFinAbonnement"],
            "idRevue" => $champs["idRevue"]
        ];

        try{
            $this->conn->beginTransaction();

            $nbCommande = $this->insertOneTupleOneTable("commande", $commande);
            if($nbCommande !== 1){
                $this->conn->rollBack();
                return null;
            }

            $nbAbonnement = $this->insertOneTupleOneTable("abonnement", $abonnement);
            if($nbAbonnement !== 1){
                $this->conn->rollBack();
                return null;
            }

            $this->conn->commit();
            return 1;
        }catch(Exception $e){
            $this->conn->rollBack();
            return null;
        }
    }

    /**
     * demande de modification (update) d'un tuple dans une table
     * @param string $table
     * @param string\null $id
     * @param array|null $champs 
     * @return int|null nombre de tuples modifiés (0 ou 1) ou null si erreur
     */	
    private function updateOneTupleOneTable(string $table, ?string $id, ?array $champs) : ?int {
         if(empty($champs)){
             return null;
         }
         if(is_null($id)){
             return null;
         }
         // construction de la requête
         $requete = "update $table set ";
         foreach ($champs as $key => $value){
             $requete .= "$key=:$key,";
         }
         // (enlève la dernière virgule)
         $requete = substr($requete, 0, strlen($requete)-1);				
         $champs["id"] = $id;
         $requete .= " where id=:id;";		
         return $this->conn->updateBDD($requete, $champs);
     }

    /**
     * demande de modification d'un livre dans les tables document et livre
     * @param string|null $id identifiant du livre à modifier
     * @param array|null $champs nom et valeur des champs du livre
     * @return int|null nombre de tuples modifiés ou null si erreur
     */
    private function updateLivre(?string $id, ?array $champs) : ?int{
         if(is_null($id) || empty($champs))
         {
             return null;
         }

         $document = [
             "titre" => $champs["titre"],
             "image" => $champs["image"],
             "idGenre" => $champs["idGenre"],
             "idPublic" => $champs["idPublic"],
             "idRayon" => $champs["idRayon"]
         ];

         $livre = [
            "ISBN" => $champs["isbn"],
            "auteur" => $champs["auteur"],
            "collection" => $champs["collection"]
         ];

         try
         {
             $this->conn->beginTransaction();

             $nbDocument = $this->updateOneTupleOneTable("document", $id, $document);
             if(is_null($nbDocument)){
                $this->conn->rollBack();
                return null;
             }

             $nbLivre = $this->updateOneTupleOneTable("livre", $id, $livre);
             if(is_null($nbLivre)){
                $this->conn->rollBack();
                return null;
             }

             $this->conn->commit();
             return 1;
         }catch(Exception $e)
         {
             $this->conn->rollBack();
             return null;
         }
     }

    /**
    * demande de modification d'un dvd dans les tables document et dvd
    * @param string|null $id identifiant du dvd à modifier
    * @param array|null $champs nom et valeur des champs du dvd
    * @return int|null nombre de tuples modifiés ou null si erreur
    */
    private function updateDvd(?string $id, ?array $champs) : ?int{
        if(is_null($id) || empty($champs)){
            return null;
        }

        $document = [
            "titre" => $champs["titre"],
            "image" => $champs["image"],
            "idGenre" => $champs["idGenre"],
            "idPublic" => $champs["idPublic"],
            "idRayon" => $champs["idRayon"]
        ];

        $dvd = [
            "synopsis" => $champs["synopsis"],
            "realisateur" => $champs["realisateur"],
            "duree" => $champs["duree"]
        ];

        try{
            $this->conn->beginTransaction();

            $nbDocument = $this->updateOneTupleOneTable("document", $id, $document);
            if(is_null($nbDocument)){
                $this->conn->rollBack();
                return null;
            }

            $nbDvd = $this->updateOneTupleOneTable("dvd", $id, $dvd);
            if(is_null($nbDvd)){
                $this->conn->rollBack();
                return null;
            }

            $this->conn->commit();
            return 1;
        }catch(Exception $e){
            $this->conn->rollBack();
            return null;
        }
    }
    
    /**
    * demande de modification d'une revue dans les tables document et revue
    * @param string|null $id identifiant de la revue à modifier
    * @param array|null $champs nom et valeur des champs de la revue
    * @return int|null nombre de tuples modifiés ou null si erreur
    */
    private function updateRevue(?string $id, ?array $champs) : ?int{
        if(is_null($id) || empty($champs)){
            return null;
        }

        $document = [
            "titre" => $champs["titre"],
            "image" => $champs["image"],
            "idGenre" => $champs["idGenre"],
            "idPublic" => $champs["idPublic"],
            "idRayon" => $champs["idRayon"]
        ];

        $revue = [
            "periodicite" => $champs["periodicite"],
            "delaiMiseADispo" => $champs["delaiMiseADispo"]
        ];

        try{
            $this->conn->beginTransaction();

            $nbDocument = $this->updateOneTupleOneTable("document", $id, $document);
            if(is_null($nbDocument)){
                $this->conn->rollBack();
                return null;
            }

            $nbRevue = $this->updateOneTupleOneTable("revue", $id, $revue);
            if(is_null($nbRevue)){
                $this->conn->rollBack();
                return null;
            }

            $this->conn->commit();
            return 1;
        }catch(Exception $e){
            $this->conn->rollBack();
            return null;
        }
    }

    /**
    * demande de modification du suivi d'une commande de document
    * @param string|null $id identifiant de la commande
    * @param array|null $champs nom et valeur des champs
    * @return int|null nombre de tuples modifiés ou null si erreur
    */
    private function updateCommandeDocument(?string $id, ?array $champs) : ?int{
        if(is_null($id) || empty($champs)){
            return null;
        }

        if(!array_key_exists("idSuivi", $champs)){
            return null;
        }

        $commandeActuelle = $this->selectTuplesOneTable("commandedocument", ["id" => $id]);
        if(empty($commandeActuelle)){
            return null;
        }

        $ancienSuivi = $commandeActuelle[0]["idSuivi"];
        $nouveauSuivi = $champs["idSuivi"];

        // livree ou reglee ne peut pas revenir en arriere
        if(($ancienSuivi == "00003" || $ancienSuivi == "00004") &&
           ($nouveauSuivi == "00001" || $nouveauSuivi == "00002")){
            return null;
        }

        // reglee seulement si deja livree
        if($nouveauSuivi == "00004" && $ancienSuivi != "00003"){
            return null;
        }
        
        if($ancienSuivi == "00004" && $nouveauSuivi != "00004"){
            return null;
        }

        return $this->updateOneTupleOneTable("commandedocument", $id, ["idSuivi" => $nouveauSuivi]);
    }
    
    /**
     * demande de suppression (delete) d'un ou plusieurs tuples dans une table
     * @param string $table
     * @param array|null $champs
     * @return int|null nombre de tuples supprimés ou null si erreur
     */
    private function deleteTuplesOneTable(string $table, ?array $champs) : ?int
    {
        if(empty($champs))
        {
            return null;
        }
        // construction de la requête
        $requete = "delete from $table where ";
        foreach ($champs as $key => $value){
            $requete .= "$key=:$key and ";
        }
        // (enlève le dernier and)
        $requete = substr($requete, 0, strlen($requete)-5);   
        return $this->conn->updateBDD($requete, $champs);	        
    }
 
    /**
    * vérifie si un document possède des exemplaires
    * @param string $id identifiant du document
    * @return bool true si au moins un exemplaire existe
    */
    private function hasExemplaires(string $id) : bool
    {
        $requete = "select count(*) as nb from exemplaire where id = :id;";
        $result = $this->conn->queryBDD($requete, ["id" => $id]);
        return !empty($result) && intval($result[0]["nb"]) > 0;
    }
    
    /**
    * vérifie si un livre ou dvd possède des commandes
    * @param string $id identifiant du livre ou dvd
    * @return bool true si au moins une commande existe
    */
    private function hasCommandesLivreDvd(string $id) : bool
    {
        $requete = "select count(*) as nb from commandedocument where idLivreDvd = :id;";
        $result = $this->conn->queryBDD($requete, ["id" => $id]);
        return !empty($result) && intval($result[0]["nb"]) > 0;
    }
    
    /**
    * demande de suppression d'un livre
    * suppression autorisée uniquement s'il n'a ni exemplaire ni commande
    * @param array|null $champs nom et valeur des champs
    * @return int|null nombre de tuples supprimés ou null si erreur
    */
    private function deleteLivre(?array $champs) : ?int{
        if(empty($champs) || !array_key_exists("id", $champs))
        {
           return null;
        }

        $id = $champs["id"];

        if($this->hasExemplaires($id) || $this->hasCommandesLivreDvd($id))
        {
           return null;
        }

        try{
            $this->conn->beginTransaction();

            $nbLivre = $this->deleteTuplesOneTable("livre", ["id" => $id]);
            if(is_null($nbLivre))
            {
               $this->conn->rollBack();
               return null;
            }

            $nbLivresDvd = $this->deleteTuplesOneTable("livres_dvd", ["id" => $id]);
            if(is_null($nbLivresDvd))
            {
               $this->conn->rollBack();
               return null;
            }

            $nbDocument = $this->deleteTuplesOneTable("document", ["id" => $id]);
            if(is_null($nbDocument)){
               $this->conn->rollBack();
               return null;
            }

            $this->conn->commit();
            return 1;
        }catch(Exception $e)
        {
            $this->conn->rollBack();
            return null;
        }
    }


    /**
    * demande de suppression d'un dvd
    * suppression autorisée uniquement s'il n'a ni exemplaire ni commande
    * @param array|null $champs nom et valeur des champs
    * @return int|null nombre de tuples supprimés ou null si erreur
    */
    private function deleteDvd(?array $champs) : ?int{
        if(empty($champs) || !array_key_exists("id", $champs)){
            return null;
        }

        $id = $champs["id"];

        if($this->hasExemplaires($id) || $this->hasCommandesLivreDvd($id)){
            return null;
        }

        try{
            $this->conn->beginTransaction();

            $nbDvd = $this->deleteTuplesOneTable("dvd", ["id" => $id]);
            if(is_null($nbDvd)){
                $this->conn->rollBack();
                return null;
            }

            $nbLivresDvd = $this->deleteTuplesOneTable("livres_dvd", ["id" => $id]);
            if(is_null($nbLivresDvd)){
                $this->conn->rollBack();
                return null;
            }

            $nbDocument = $this->deleteTuplesOneTable("document", ["id" => $id]);
            if(is_null($nbDocument)){
                $this->conn->rollBack();
                return null;
            }
           
            $this->conn->commit();
            return 1;
        }catch(Exception $e){
            $this->conn->rollBack();
            return null;
        }
    }

    /**
    * vérifie si une revue possède des abonnements
    * @param string $id identifiant de la revue
    * @return bool true si au moins un abonnement existe
    */
    private function hasCommandesRevue(string $id) : bool{
        $requete = "select count(*) as nb from abonnement where idRevue = :id;";
        $result = $this->conn->queryBDD($requete, ["id" => $id]);
        return !empty($result) && intval($result[0]["nb"]) > 0;
    }
    
    /**
    * demande de suppression d'une revue
    * suppression autorisée uniquement si elle n'a ni exemplaire ni abonnement
    * @param array|null $champs nom et valeur des champs
    * @return int|null nombre de tuples supprimés ou null si erreur
    */
    private function deleteRevue(?array $champs) : ?int{
        if(empty($champs) || !array_key_exists("id", $champs)){
            return null;
        }

        $id = $champs["id"];

        if($this->hasExemplaires($id) || $this->hasCommandesRevue($id)){
            return null;
        }

        try{
            $this->conn->beginTransaction();

            $nbRevue = $this->deleteTuplesOneTable("revue", ["id" => $id]);
            if(is_null($nbRevue)){
                $this->conn->rollBack();
                return null;
            }

            $nbDocument = $this->deleteTuplesOneTable("document", ["id" => $id]);
            if(is_null($nbDocument)){
                $this->conn->rollBack();
                return null;
            }

            $this->conn->commit();
            return 1;
        }catch(Exception $e){
            $this->conn->rollBack();
            return null;
        }
    }
    
    /**
    * demande de suppression d'une commande de document
    * @param array|null $champs nom et valeur des champs
    * @return int|null nombre de tuples supprimés ou null si erreur
    */
    private function deleteCommandeDocument(?array $champs) : ?int{
        if(empty($champs) || !array_key_exists("id", $champs)){
            return null;
        }

        $id = $champs["id"];

        try{
            $this->conn->beginTransaction();

            $nbCommandeDocument = $this->deleteTuplesOneTable("commandedocument", ["id" => $id]);
            if(is_null($nbCommandeDocument)){
                $this->conn->rollBack();
                return null;
            }

            $nbCommande = $this->deleteTuplesOneTable("commande", ["id" => $id]);
            if(is_null($nbCommande)){
                $this->conn->rollBack();
                return null;
            }

            $this->conn->commit();
            return 1;
        }catch(Exception $e){
            $this->conn->rollBack();
            return null;
        }
    }
    
    /**
    * demande de suppression d'un abonnement
    * @param array|null $champs nom et valeur des champs
    * @return int|null nombre de tuples supprimés ou null si erreur
    */
    private function deleteAbonnement(?array $champs) : ?int{
        if(empty($champs) || !array_key_exists("id", $champs)){
            return null;
        }

        return $this->deleteTuplesOneTable("abonnement", ["id" => $champs["id"]]);
    }

    /**
     * récupère toutes les lignes d'une table simple (qui contient juste id et libelle)
     * @param string $table
     * @return array|null
     */
    private function selectTableSimple(string $table) : ?array{
        $requete = "select * from $table order by libelle;";		
        return $this->conn->queryBDD($requete);	    
    }
    
    /**
     * récupère toutes les lignes de la table Livre et les tables associées
     * @return array|null
     */
    private function selectAllLivres() : ?array{
        $requete = "Select l.id, l.ISBN, l.auteur, d.titre, d.image, l.collection, ";
        $requete .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $requete .= "from livre l join document d on l.id=d.id ";
        $requete .= "join genre g on g.id=d.idGenre ";
        $requete .= "join public p on p.id=d.idPublic ";
        $requete .= "join rayon r on r.id=d.idRayon ";
        $requete .= "order by titre ";		
        return $this->conn->queryBDD($requete);
    }	

    /**
     * récupère toutes les lignes de la table DVD et les tables associées
     * @return array|null
     */
    private function selectAllDvd() : ?array{
        $requete = "Select l.id, l.duree, l.realisateur, d.titre, d.image, l.synopsis, ";
        $requete .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $requete .= "from dvd l join document d on l.id=d.id ";
        $requete .= "join genre g on g.id=d.idGenre ";
        $requete .= "join public p on p.id=d.idPublic ";
        $requete .= "join rayon r on r.id=d.idRayon ";
        $requete .= "order by titre ";	
        return $this->conn->queryBDD($requete);
    }	

    /**
     * récupère toutes les lignes de la table Revue et les tables associées
     * @return array|null
     */
    private function selectAllRevues() : ?array{
        $requete = "Select l.id, l.periodicite, d.titre, d.image, l.delaiMiseADispo, ";
        $requete .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $requete .= "from revue l join document d on l.id=d.id ";
        $requete .= "join genre g on g.id=d.idGenre ";
        $requete .= "join public p on p.id=d.idPublic ";
        $requete .= "join rayon r on r.id=d.idRayon ";
        $requete .= "order by titre ";
        return $this->conn->queryBDD($requete);
    }	

    /**
     * récupère tous les exemplaires d'une revue
     * @param array|null $champs 
     * @return array|null
     */
    private function selectExemplairesRevue(?array $champs) : ?array{
        if(empty($champs)){
            return null;
        }
        if(!array_key_exists('id', $champs)){
            return null;
        }
        $champNecessaire['id'] = $champs['id'];
        $requete = "Select e.id, e.numero, e.dateAchat, e.photo, e.idEtat ";
        $requete .= "from exemplaire e join document d on e.id=d.id ";
        $requete .= "where e.id = :id ";
        $requete .= "order by e.dateAchat DESC";
        return $this->conn->queryBDD($requete, $champNecessaire);
    }
    
    /**
    * récupère les commandes d'un livre ou d'un dvd avec leur suivi
    * @param array|null $champs
    * @return array|null
    */
    private function selectCommandesDocument(?array $champs) : ?array{
        if(empty($champs)){
            return null;
        }
        if(!array_key_exists('idLivreDvd', $champs)){
            return null;
        }

        $champNecessaire['idLivreDvd'] = $champs['idLivreDvd'];

        $requete = "select c.id, c.dateCommande, c.montant, cd.nbExemplaire, ";
        $requete .= "cd.idLivreDvd, cd.idSuivi, s.libelle as libelleSuivi ";
        $requete .= "from commandedocument cd ";
        $requete .= "join commande c on c.id = cd.id ";
        $requete .= "join suivi s on s.id = cd.idSuivi ";
        $requete .= "where cd.idLivreDvd = :idLivreDvd ";
        $requete .= "order by c.dateCommande desc;";

        return $this->conn->queryBDD($requete, $champNecessaire);
    }
    
    /**
    * récupère les abonnements d'une revue
    * @param array|null $champs
    * @return array|null
    */
    private function selectAbonnementsRevue(?array $champs) : ?array{
        if(empty($champs)){
            return null;
        }
        if(!array_key_exists('idRevue', $champs)){
            return null;
        }

        $champNecessaire['idRevue'] = $champs['idRevue'];

        $requete = "select a.id, c.dateCommande, c.montant, a.dateFinAbonnement, a.idRevue ";
        $requete .= "from abonnement a ";
        $requete .= "join commande c on a.id = c.id ";
        $requete .= "where a.idRevue = :idRevue ";
        $requete .= "order by c.dateCommande desc;";

        return $this->conn->queryBDD($requete, $champNecessaire);
    }

}
