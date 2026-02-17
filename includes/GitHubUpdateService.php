<?php
/**
 * GitHub Update Service
 * Integrates with GitHub API to fetch release information
 */

class GitHubUpdateService {
    private $repoOwner = 'teknologelis-stack';
    private $repoName = 'hlm';
    private $apiBase = 'https://api.github.com';
    
    /**
     * Get the latest release from GitHub
     * @return array|null Release data or null on failure
     */
    public function getLatestRelease() {
        $url = "{$this->apiBase}/repos/{$this->repoOwner}/{$this->repoName}/releases/latest";
        $response = $this->makeRequest($url);
        
        if (!$response) {
            return null;
        }
        
        return $this->parseRelease($response);
    }
    
    /**
     * Get all releases from GitHub
     * @param int $limit Number of releases to fetch
     * @return array Array of releases
     */
    public function getAllReleases($limit = 10) {
        $url = "{$this->apiBase}/repos/{$this->repoOwner}/{$this->repoName}/releases?per_page={$limit}";
        $response = $this->makeRequest($url);
        
        if (!$response || !is_array($response)) {
            return [];
        }
        
        $releases = [];
        foreach ($response as $releaseData) {
            $releases[] = $this->parseRelease($releaseData);
        }
        
        return $releases;
    }
    
    /**
     * Get a specific release by tag
     * @param string $tag Tag name (e.g., 'v1.0.1')
     * @return array|null Release data or null on failure
     */
    public function getReleaseByTag($tag) {
        // Ensure tag starts with 'v'
        if (substr($tag, 0, 1) !== 'v') {
            $tag = 'v' . $tag;
        }
        
        $url = "{$this->apiBase}/repos/{$this->repoOwner}/{$this->repoName}/releases/tags/{$tag}";
        $response = $this->makeRequest($url);
        
        if (!$response) {
            return null;
        }
        
        return $this->parseRelease($response);
    }
    
    /**
     * Download a release ZIP file
     * @param string $downloadUrl URL to download from
     * @param string $savePath Path to save the file
     * @return bool Success status
     */
    public function downloadRelease($downloadUrl, $savePath) {
        try {
            // Ensure directory exists
            $dir = dirname($savePath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            // Use cURL for better control over the download
            $ch = curl_init($downloadUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'HLM-Update-Manager/1.0');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 300); // 5 minutes timeout
            
            $data = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200 || $data === false) {
                error_log("[GitHubUpdateService] Failed to download release: HTTP {$httpCode}");
                return false;
            }
            
            // Save to file
            $result = file_put_contents($savePath, $data);
            
            if ($result === false) {
                error_log("[GitHubUpdateService] Failed to save file to: {$savePath}");
                return false;
            }
            
            return true;
        } catch (Exception $e) {
            error_log("[GitHubUpdateService] Download error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Compare two version strings
     * @param string $current Current version
     * @param string $latest Latest version
     * @return int -1 if current < latest, 0 if equal, 1 if current > latest
     */
    public function compareVersions($current, $latest) {
        // Remove 'v' prefix if present
        $current = ltrim($current, 'v');
        $latest = ltrim($latest, 'v');
        
        return version_compare($current, $latest);
    }
    
    /**
     * Parse release data from GitHub API response
     * @param array $data Raw release data
     * @return array Parsed release data
     */
    private function parseRelease($data) {
        if (!is_array($data)) {
            return null;
        }
        
        // Extract version from tag (remove 'v' prefix)
        $version = isset($data['tag_name']) ? ltrim($data['tag_name'], 'v') : 'unknown';
        
        // Parse changelog from body
        $changelog = $this->parseChangelog($data['body'] ?? '');
        
        return [
            'version' => $version,
            'tag_name' => $data['tag_name'] ?? '',
            'name' => $data['name'] ?? "Release {$version}",
            'changelog' => $changelog,
            'body' => $data['body'] ?? '',
            'published_at' => $data['published_at'] ?? date('Y-m-d\TH:i:s\Z'),
            'html_url' => $data['html_url'] ?? '',
            'zipball_url' => $data['zipball_url'] ?? '',
            'tarball_url' => $data['tarball_url'] ?? '',
            'prerelease' => $data['prerelease'] ?? false,
            'draft' => $data['draft'] ?? false
        ];
    }
    
    /**
     * Parse changelog from release body
     * @param string $body Release body text
     * @return array Array of changelog items
     */
    private function parseChangelog($body) {
        $changelog = [];
        
        if (empty($body)) {
            return $changelog;
        }
        
        // Split by lines
        $lines = explode("\n", $body);
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Skip empty lines and headers
            if (empty($line) || preg_match('/^#+\s/', $line)) {
                continue;
            }
            
            // Check for list items (-, *, +)
            if (preg_match('/^[-*+]\s+(.+)$/', $line, $matches)) {
                $changelog[] = trim($matches[1]);
            }
            // Also include lines that start with numbers (numbered lists)
            elseif (preg_match('/^\d+\.\s+(.+)$/', $line, $matches)) {
                $changelog[] = trim($matches[1]);
            }
        }
        
        return $changelog;
    }
    
    /**
     * Make HTTP request to GitHub API
     * @param string $url URL to request
     * @return mixed Decoded JSON response or null on failure
     */
    private function makeRequest($url) {
        try {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'HLM-Update-Manager/1.0');
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: application/vnd.github.v3+json'
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($response === false) {
                error_log("[GitHubUpdateService] cURL error: {$error}");
                return null;
            }
            
            if ($httpCode === 404) {
                error_log("[GitHubUpdateService] Resource not found: {$url}");
                return null;
            }
            
            if ($httpCode === 403) {
                error_log("[GitHubUpdateService] Rate limit exceeded or forbidden");
                return null;
            }
            
            if ($httpCode !== 200) {
                error_log("[GitHubUpdateService] HTTP error {$httpCode} for: {$url}");
                return null;
            }
            
            $data = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("[GitHubUpdateService] JSON decode error: " . json_last_error_msg());
                return null;
            }
            
            return $data;
        } catch (Exception $e) {
            error_log("[GitHubUpdateService] Request error: " . $e->getMessage());
            return null;
        }
    }
}
