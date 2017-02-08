<?php
class GetDedao
{
    const getUrl='https://m.igetget.com/share/audio/tid/';
    const errmsg = 'error-404';
    const dbName = 'dedao';
    const tbRanger = ' ranger ';
    const tbEntries = ' entries ';
    const tbError = ' errors ';
    const bkMin = 8;
    const defaultRange = 100;
    const pageSize = 20;
    const encode = 'utf8';
    private $_begin;
    private $_end;
    private $_db;
    private $_page_range;

    function __construct()
    {
        try{
            $this->_db = new PDO(
                'mysql:host=localhost;dbname='.self::dbName.';charset='.self::encode,
                'vagrant',
                'vagrant',
                [
                    PDO::ATTR_PERSISTENT => true,
                ]);
            $this->_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);

        } catch (PDOException $e) {
            print "Error!: " . $e->getMessage() . "<br/>";
            die();
        }
    }

    function __destruct()
    {
        $this->_db = null;
    }

    public function CreateDateBase()
    {
        $entries = 'create table '.self::tbEntries.
            ' (id int unsigned not null AUTO_INCREMENT,
            title varchar(128) not null,
            number int unsigned not null,
            time varchar(32) not null,
            is_book tinyint(1) not null,
            done tinyint not null default 0,
            audio varchar(256) not null,
            primary key (id,number)) CHARACTER SET utf8 ENGINE=InnoDB';
        $rangers = 'create table '.self::tbRanger.
            ' (id int unsigned not null AUTO_INCREMENT,
            begin int unsigned not null,
            end int unsigned not null,
            primary key(id)) CHARACTER SET utf8 ENGINE=InnoDB';
        $insertRanger = 'insert into '.self::tbRanger.
            '(begin, end) values (0,6400)';
        $errNotice = 'create table '.self::tbError.
            '(id int unsigned not null AUTO_INCREMENT,
            number int unsigned not null,
            notice varchar(256) not null,
            time timestamp not null default CURRENT_TIMESTAMP,
            primary key(id)) CHARACTER SET utf8 ENGINE=InnoDB';

        try {
            $this->_db->query($rangers);
            $this->_db->query($insertRanger);
            $this->_db->query($entries);
            $this->_db->query($errNotice);
        } catch (PDOException $e) {
            print "Error!: " . $e->getMessage() . "<br/>";
            die();
        }
    }

    // everyday  update
    public function Update($range = self::defaultRange)
    {
        $this->GetRanger();
        $this->GetRemote($this->end, $this->end + $range);
        $this->SetRanger($this->end, $this->end + $range);
    }

    // from Remote server get entry
    public function GetRemote($begin,$end)
    {
        $entries = [];
        for($i = $begin; $i< $end ;$i++){
            $url = self::getUrl.$i;
            $context = file_get_contents($url);
            if(strpos($context, self::errmsg)) {
                continue;
            }
            if(!preg_match('@audio_title(?:[^>]+)>([^<]+)@',$context,$matches)){
                $this->InsertError($i,'find audio_title error');
                continue;
            }
            $entry["title"] =  $matches[1];

            if(!preg_match('/showTime(?:[^>]+)>([^<]+)/',$context,$matches)){
                $this->InsertError($i,'find showTime error');
                continue;
            }
            $entry["time"] = $matches[1];

            if(!preg_match('/audio_url" (?:[^"]+)"([^"]+)/',$context,$matches)){
                $this->InsertError($i,'find showTime error');
                continue;
            }
            $entry["audio"] = $matches[1];

            $entry["number"] = $i;
            $entry["is_book"] = (self::IsBook($entry['time']) ? 1 : 0);
            print_r($entry);
            $entries[] = $entry;
        }
        $this->InsertEntries($entries);
    }

    protected function InsertEntries($entries)
    {
        $stmt = $this->_db->prepare("INSERT INTO ".self::tbEntries.
            " (title,number,audio,time,is_book) VALUES (:title,:number,:audio,:time,:is_book)");
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':number', $number);
        $stmt->bindParam(':audio', $audio);
        $stmt->bindParam(':time', $time);
        $stmt->bindParam(':is_book', $is_book);
        foreach($entries as $entry) {
            $title = $entry["title"];
            $time = $entry["time"];
            $number = $entry["number"];
            $audio = $entry["audio"];
            $is_book = $entry["is_book"];
            try {
                $stmt->execute();
            } catch (PDOException $e) {
                echo 'Connection failed: ' . $e->getMessage();
                exit;
            }
        }
    }

    public function GetEntries($page = null)
    {
        $query = "select number, title, time, is_book, audio,done from "
            .self::tbEntries.
            ' order by number Desc limit ' .self::pageSize;
        if($page && $page > 1 && $page <= $this->GetPageRanger() ) {
            $offset = ($page-1) * self::pageSize;
            $query .= ' offset '. $offset;
        }
        return $this->_db->query($query);
    }

    public function GetPageRanger()
    {
        if(!$this->_page_range) {
            $count = $this->_db->query("select count(*) from ". self::tbEntries);
            $count = $count->fetch();
            $this->_page_range = (int)($count[0] / self::pageSize) + 1; // how many pages
        }
        return $this->_page_range;
    }



    protected function  InsertError($number, $notice)
    {
        $stmt = $this->_db->prepare("INSERT INTO " .self::tbError.
            " (number,notice) VALUES (:number,:notice)");
        //$one = [];
        $stmt->bindParam(':notice', $notice);
        $stmt->bindParam(':number', $number);
        $stmt->execute();
    }

    public function GetError()
    {
        return $this->_db->query('select number,notice,time from ' . self::tbError);
    }

    protected function GetRanger()
    {
        $rows = $this->_db->query('select * from '. self::tbRanger. 'where id=1');
        $_begin = $row[0]['begin'];
        $_end = $row[0]['end'];
    }

    protected function SetRanger()
    {
        $row = $this->_db->query('update ' . self::tbRanger .'SET begin='.$_begin.',end='.$_end.'where id=1');
    }

    static function IsBook($time)
    {
        $min = explode(":", $time);
        return $min[0] >= self::bkMin;
    }


}
?>
