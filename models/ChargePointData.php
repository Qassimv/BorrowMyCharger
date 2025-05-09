<?php

class ChargePointModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

   public function searchChargingPoints($search, $filter) {
    $sql = "SELECT * FROM charge_points_pr WHERE 1=1";
    $params = [];

    if (!empty($search)) {
        $sql .= " AND (
            LOWER(address) LIKE :addressSearch OR
            LOWER(postcode) LIKE :postcodeSearch OR
            CAST(latitude AS CHAR) LIKE :latitudeSearch OR
            CAST(longitude AS CHAR) LIKE :longitudeSearch OR
            CAST(price_per_kwh AS CHAR) LIKE :priceSearch OR
            CAST(isAvailable AS CHAR) LIKE :availabilitySearch
        )";

        $searchTerm = '%' . strtolower($search) . '%';
        $params['addressSearch'] = $searchTerm;
        $params['postcodeSearch'] = $searchTerm;
        $params['latitudeSearch'] = $searchTerm;
        $params['longitudeSearch'] = $searchTerm;
        $params['priceSearch'] = $searchTerm;
        $params['availabilitySearch'] = $searchTerm;
    }

    if (!empty($filter) && $filter !== 'all') {
        $sql .= " AND charger_type = :filter";
        $params['filter'] = $filter;
    }

    $stmt = $this->pdo->prepare($sql);

    foreach ($params as $key => $value) {
        $stmt->bindValue(':' . $key, $value);
    }

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


    public function getAllChargerTypes() {
        $sql = "SELECT DISTINCT charger_type FROM charge_points_pr";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
