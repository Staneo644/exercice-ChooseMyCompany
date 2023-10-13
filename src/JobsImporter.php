<?php

class JobsImporter
{
    private PDO $db;

    public function __construct(string $host, string $username, string $password, string $databaseName)
    {
        try {
            $this->db = new PDO('mysql:host=' . $host . ';dbname=' . $databaseName, $username, $password);
        } catch (Exception $e) {
            die('DB error: ' . $e->getMessage() . "\n");
        }
    }

    
    private function importJobsFromRegionJob(string $file): int {
        $xml = simplexml_load_file($file);

        $count = 0;
        foreach ($xml->item as $item) {
            $this->db->exec('INSERT INTO job (reference, title, description, url, company_name, publication) VALUES ('
                . '\'' . addslashes($item->ref) . '\', '
                . '\'' . addslashes($item->title) . '\', '
                . '\'' . addslashes($item->description) . '\', '
                . '\'' . addslashes($item->url) . '\', '
                . '\'' . addslashes($item->company) . '\', '
                . '\'' . addslashes($item->pubDate) . '\')'
            );
            $count++;
        }
        return $count;
    }
    
    private function importJobsFromJobTeaser(string $file) : int {
        
        $this->db->exec('DELETE FROM job');
        
        $jsonContent = file_get_contents($file);
        $data = json_decode($jsonContent, true);
        
        if ($data === null) {
            throw new Exception('Error reading the JSON file.');
        }
        $urlPrefix = $data['offerUrlPrefix'];
        $itemList = $data['offers'];
        $count = 0;
        foreach ($itemList as $item) {

            $dateTime = DateTime::createFromFormat('D M d H:i:s T Y', $item['publishedDate']);

            $this->db->exec('INSERT INTO job (reference, title, description, url, company_name, publication) VALUES ('
                . '\'' . addslashes($item['reference']) . '\', '
                . '\'' . addslashes($item['title']) . '\', '
                . '\'' . addslashes($item['description']) . '\', '
                . '\'' . addslashes($urlPrefix . $item['urlPath']) . '\', '
                . '\'' . addslashes($item['companyname']) . '\', '
                . '\'' . addslashes($dateTime->format('Y-m-d')) . '\')'
            );
            
            $count++;
        }
        return $count;
    }
    public function importJobs(): int
    {
        $this->db->exec('DELETE FROM job');

        $count = $this->importJobsFromJobTeaser( RESSOURCES_DIR . 'jobteaser.json');
        $count += $this->importJobsFromRegionJob( RESSOURCES_DIR . 'regionsjob.xml');
        return $count;
    }
}
