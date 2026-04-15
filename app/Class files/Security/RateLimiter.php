<?php
class RateLimiter {
    protected $db;
    protected $dialect;

    public function __construct($db, $dialect) {
        $this->db = $db;
        $this->dialect = $dialect;
    }

    public function checkBan(string $ip) {
        // Clear expired
        $qb = new QueryBuilder($this->dialect);
        $sql = $qb->table('banned_ips')->where('biExpires', '<', (new DateTime())->format('Y-m-d H:i:s'))->delete();
        $stmt = $this->db->prepare($sql);
        $qb->bindTo($stmt);
        $stmt->execute();

        // Check if currently banned
        $qb = new QueryBuilder($this->dialect);
        $sql = $qb->table('banned_ips')->select(['biPK'])->where('biIP', '=', $ip)->toSQL();
        $stmt = $this->db->prepare($sql);
        $qb->bindTo($stmt);
        $stmt->execute();
        
        return $stmt->fetch() !== false;
    }

    public function banIp(string $ip, string $reason, int $minutes = 15) {
        if ($this->checkBan($ip)) return;

        $expires = (new DateTime())->modify("+$minutes minutes")->format('Y-m-d H:i:s');
        
        $qb = new QueryBuilder($this->dialect);
        $sql = $qb->table('banned_ips')->insert([
            'biIP' => $ip,
            'biReason' => substr($reason, 0, 255),
            'biExpires' => $expires
        ]);
        
        $stmt = $this->db->prepare($sql);
        $qb->bindTo($stmt);
        $stmt->execute();
    }

    public function incrementAndCheck(string $ip) {
        $oneMinuteAgo = (new DateTime())->modify("-1 minute")->format('Y-m-d H:i:s');
        
        $qb = new QueryBuilder($this->dialect);
        $sql = $qb->table('httpAction')
                  ->select([$qb->raw('COUNT(*)')])
                  ->where('haIP', '=', $ip)
                  ->where('haDate', '>=', $oneMinuteAgo)
                  ->toSQL();
                  
        $stmt = $this->db->prepare($sql);
        $qb->bindTo($stmt);
        $stmt->execute();
        $countRow = $stmt->fetch(PDO::FETCH_NUM);
        $count = $countRow[0] ?? 0;

        if($ip == '127.0.0.1'){
            return true;
        }

        if ($count >= 50) {
            $this->banIp($ip, 'Rate limit exceeded: >50 requests per minute', 5);
            return false; 
        }
        return true; 
    }

    /**
     * Detects if a client has rotated between 5 or more different session cookies in the last hour.
     * Bans the IP for 7 days if detected.
     */
    public function checkCookieRotation(string $ip) {
        if ($ip == '127.0.0.1') {
            return true;
        }

        $oneHourAgo = (new DateTime())->modify("-60 minutes")->format('Y-m-d H:i:s');
        
        $qb = new QueryBuilder($this->dialect);
        $sql = $qb->table('httpAction')
                  ->select([$qb->raw('COUNT(DISTINCT haSessionFK)')])
                  ->where('haIP', '=', $ip)
                  ->where('haDate', '>=', $oneHourAgo)
                  ->toSQL();
                  
        $stmt = $this->db->prepare($sql);
        $qb->bindTo($stmt);
        $stmt->execute();
        $countRow = $stmt->fetch(PDO::FETCH_NUM);
        
        $count = $countRow[0] ?? 0;

        if ($count >= 5) {
            $this->banIp($ip, 'Cookie rotation detected: >=5 different session IDs in 60 minutes', 10080); // 7 days
            return false;
        }
        return true;
    }
}
?>
