<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Rats\Zkteco\Lib\ZKTeco;

class ZKTecoService
{
    private $ip;
    private $port;
    private $zk;
    
    public function __construct($ip = '192.168.1.201', $port = 4370)
    {
        $this->ip = $ip;
        $this->port = $port;
        $this->zk = new ZKTeco($ip, $port);
    }
    
    /**
     * Connecter à l'appareil ZKTeco
     */
    public function connect()
    {
        try {
            $connected = $this->zk->connect();
            
            if ($connected) {
                Log::info("Connecté à ZKTeco F18: {$this->ip}:{$this->port}");
                return true;
            }
            
            Log::error("Impossible de se connecter à ZKTeco: {$this->ip}:{$this->port}");
            return false;
            
        } catch (Exception $e) {
            Log::error("Erreur connexion ZKTeco: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Déconnecter de l'appareil
     */
    public function disconnect()
    {
        try {
            if ($this->zk) {
                $this->zk->disconnect();
            }
        } catch (Exception $e) {
            Log::error("Erreur déconnexion ZKTeco: " . $e->getMessage());
        }
    }
    
    /**
     * Obtenir les données de pointage
     */
    public function getAttendanceData($dateFrom = null, $dateTo = null)
    {
        try {
            if (!$this->connect()) {
                return [];
            }
            
            // Récupérer tous les pointages
            $attendance = $this->zk->getAttendance();
            
            $filteredData = [];
            
            foreach ($attendance as $record) {
                $timestamp = $this->parseTimestamp($record['timestamp']);
                
                // Filtrer par date si spécifié
                if ($dateFrom && $timestamp < strtotime($dateFrom)) {
                    continue;
                }
                if ($dateTo && $timestamp > strtotime($dateTo . ' 23:59:59')) {
                    continue;
                }
                
                $filteredData[] = [
                    'uid' => $record['uid'] ?? null,
                    'user_id' => $record['id'] ?? null,
                    'timestamp' => date('Y-m-d H:i:s', $timestamp),
                    'status' => $record['status'] ?? 0,
                    'type' => $this->getAttendanceType($record['status'] ?? 0),
                ];
            }
            
            $this->disconnect();
            return $filteredData;
            
        } catch (Exception $e) {
            Log::error("Erreur récupération pointage ZKTeco: " . $e->getMessage());
            $this->disconnect();
            return [];
        }
    }
    
    /**
     * Synchroniser les utilisateurs vers ZKTeco
     */
    public function syncUsersToDevice($users)
    {
        try {
            if (!$this->connect()) {
                return false;
            }
            
            $successCount = 0;
            
            foreach ($users as $user) {
                try {
                    // Ajouter l'utilisateur à ZKTeco
                    $this->zk->setUser(
                        $user['uid'],
                        $user['user_id'] ?? $user['uid'],
                        $user['name'],
                        $user['password'] ?? '123456',
                        $user['role'] ?? 0,
                        $user['cardno'] ?? 0
                    );
                    
                    $successCount++;
                    Log::info("Utilisateur ajouté à ZKTeco: " . $user['name']);
                    
                } catch (Exception $e) {
                    Log::error("Erreur ajout utilisateur {$user['name']}: " . $e->getMessage());
                }
            }
            
            $this->disconnect();
            return $successCount > 0;
            
        } catch (Exception $e) {
            Log::error("Erreur synchronisation ZKTeco: " . $e->getMessage());
            $this->disconnect();
            return false;
        }
    }
    
    /**
     * Supprimer un utilisateur de ZKTeco
     */
    public function deleteUser($uid)
    {
        try {
            if (!$this->connect()) {
                return false;
            }
            
            // Récupérer tous les utilisateurs
            $users = $this->zk->getUser();
            
            foreach ($users as $user) {
                if ($user['uid'] == $uid) {
                    $this->zk->removeUser($user['uid']);
                    Log::info("Utilisateur supprimé de ZKTeco: " . $uid);
                    $this->disconnect();
                    return true;
                }
            }
            
            $this->disconnect();
            return false;
            
        } catch (Exception $e) {
            Log::error("Erreur suppression utilisateur ZKTeco: " . $e->getMessage());
            $this->disconnect();
            return false;
        }
    }
    
    /**
     * Vérifier la connexion à l'appareil
     */
    public function testConnection()
    {
        return $this->connect();
    }
    
    /**
     * Obtenir les informations de l'appareil
     */
    public function getDeviceInfo()
    {
        try {
            if (!$this->connect()) {
                return [
                    'ip' => $this->ip,
                    'port' => $this->port,
                    'connected' => false,
                    'error' => 'Impossible de se connecter',
                ];
            }
            
            $info = [
                'ip' => $this->ip,
                'port' => $this->port,
                'connected' => true,
            ];
            
            // Essayer de récupérer des infos de l'appareil
            try {
                $deviceInfo = $this->zk->deviceInfo();
                if ($deviceInfo) {
                    $info['device_name'] = $deviceInfo['deviceName'] ?? 'ZKTeco F18';
                    $info['firmware_version'] = $deviceInfo['fwVersion'] ?? 'Unknown';
                    $info['serial_number'] = $deviceInfo['serialNumber'] ?? 'Unknown';
                }
            } catch (Exception $e) {
                // Ignorer si non supporté
                $info['device_name'] = 'ZKTeco F18';
                $info['firmware_version'] = 'Unknown';
                $info['serial_number'] = 'Unknown';
            }
            
            // Compter les utilisateurs
            try {
                $users = $this->zk->getUser();
                $info['users_count'] = count($users);
            } catch (Exception $e) {
                $info['users_count'] = 0;
            }
            
            // Compter les logs
            try {
                $attendance = $this->zk->getAttendance();
                $info['logs_count'] = count($attendance);
            } catch (Exception $e) {
                $info['logs_count'] = 0;
            }
            
            $this->disconnect();
            return $info;
            
        } catch (Exception $e) {
            return [
                'ip' => $this->ip,
                'port' => $this->port,
                'connected' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Vider les logs ZKTeco
     */
    public function clearDeviceData()
    {
        try {
            if (!$this->connect()) {
                return false;
            }
            
            $this->zk->clearAttendance();
            Log::info("Données ZKTeco effacées");
            
            $this->disconnect();
            return true;
            
        } catch (Exception $e) {
            Log::error("Erreur effacement données ZKTeco: " . $e->getMessage());
            $this->disconnect();
            return false;
        }
    }
    
    /**
     * Parser le timestamp ZKTeco
     */
    private function parseTimestamp($zkTimestamp)
    {
        // Format ZKTeco: YYMMDDHHMMSS
        if (strlen($zkTimestamp) == 12) {
            $year = '20' . substr($zkTimestamp, 0, 2);
            $month = substr($zkTimestamp, 2, 2);
            $day = substr($zkTimestamp, 4, 2);
            $hour = substr($zkTimestamp, 6, 2);
            $minute = substr($zkTimestamp, 8, 2);
            $second = substr($zkTimestamp, 10, 2);
            
            return strtotime("$year-$month-$day $hour:$minute:$second");
        }
        
        return time();
    }
    
    /**
     * Déterminer le type de pointage
     */
    private function getAttendanceType($status)
    {
        // Codes ZKTeco:
        // 0: Check-in
        // 1: Check-out
        // 4: Overtime-in
        // 5: Overtime-out
        
        if ($status == 0 || $status == 4) {
            return 'entree';
        } elseif ($status == 1 || $status == 5) {
            return 'sortie';
        }
        
        return 'inconnu';
    }
    
    public function userExists($uid)
    {
        if (!$this->connect()) return false;
        
        $users = $this->zk->getUser();
        $this->disconnect();

        foreach ($users as $user) {
            if ($user['userid'] == $uid) {
                return true;
            }
        }
        return false;
    }

    /**
     * Ajouter un utilisateur sur l'appareil avec numéro de carte
     */
    public function setUser($uid, $name, $cardno = '')
    {
        if (!$this->connect()) return false;
        
        // Paramètres: $uid, $userid (string), $name, $password, $role, $cardno
        $result = $this->zk->setUser($uid, $uid, $name, '', 0, $cardno);
        $this->disconnect();
        
        return $result;
    }
}