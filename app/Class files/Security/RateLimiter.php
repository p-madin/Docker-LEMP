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
}
?>
