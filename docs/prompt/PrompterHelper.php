<?php
/*****************************
Generate prompt into clipboard
php prompt.php | pbcopy

Update files from clipboard
pbpaste | php update.php
*****************************/

class PrompterHelper
{
    private array $ignoredDirs = [];
    private string $root;

    public function __construct(string $root, $ignoreDirs = [])
    {
        $this->root = $this->canonicalizePath($root);
        foreach ($ignoreDirs as $dir) 
        {
            $this->addIgnoredDir($dir);
        }
        
        echo "Workspace root: " . $this->root . "\n";
    }

    public function dumpWorkspace()
    {
        echo "\nDirectory structure:\n";
        echo $this->tree() . "\n";
        echo "contains there files:\n";
        echo $this->fullFiles();
        echo "\n\n";
    }

    public function addIgnoredDir(string $dir): void
    {
        $this->ignoredDirs[] = $this->canonicalizePath($this->root . DIRECTORY_SEPARATOR . $dir);
    }

    public function tree(string $dir = null): string
    {
        $directory = $this->canonicalizePath($dir ? $this->root . DIRECTORY_SEPARATOR . $dir : $this->root);
        $output = '';

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $file) {
            $relativePath = str_replace($this->root . DIRECTORY_SEPARATOR, '', $file->getPathname());

            if ($file->isDir() || $this->isIgnored($file->getPathname())) {
                continue;
            }

            $output .= $relativePath . "\n";
        }

        return $output;
    }

    public function fullFiles(string $dir = null): string
    {
        $directory = $this->canonicalizePath($dir ? $this->root . DIRECTORY_SEPARATOR . $dir : $this->root);
        $output = '';

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $file) {
            $relativePath = str_replace($this->root . DIRECTORY_SEPARATOR, '', $file->getPathname());
            
            if ($file->isDir() || $this->isIgnored($file->getPathname())) {
                continue;
            }

            $output .=  $this->fullFile($relativePath). "\n\n";
        }

        return $output;
    }

    private function isIgnored(string $path): bool
    {
        $canonicalPath = $this->canonicalizePath($path);

        foreach ($this->ignoredDirs as $ignoredDir) {
            if (str_starts_with($canonicalPath, $ignoredDir)) {
                return true;
            }
        }
        return false;
    }

    private function canonicalizePath(string $path): string
    {
        $realPath = realpath($path);
        return $realPath ? $realPath : $path;
    }

    public function fullFile(string $path): string
    {
        $result = str_pad("\n--- File: $path ", 40, "-", STR_PAD_RIGHT)."\n";
        $fullPath = $this->canonicalizePath($this->root . '/' . $path);
        if (file_exists($fullPath)) {
            if ($this->isBinary($fullPath)) {
                $result .= "--- binary data ...";
            } else {
                $result .= file_get_contents($fullPath);
            }        
        } else {
            $result .= "File not found.";
        }
        $result .= str_pad("\n--- End of file: $path ", 40, "-", STR_PAD_RIGHT)."\n\n";
        return $result;
    }

    public function isBinary($filePath, $checkLength = 512, $threshold = 0.1) {
        $handle = fopen($filePath, 'r');
        $contents = fread($handle, $checkLength);        
        fclose($handle);
    
        if ($contents === false || strlen($contents) === 0) {
            return false; // Empty file, we'll consider it a text file
        }
    
        // Check for null bytes
        if (strpos($contents, "\0") !== false) {
            return true; // Contains null byte, most likely binary
        }
    
        // Check if the contents are valid UTF-8
        if (mb_check_encoding($contents, 'UTF-8')) {
            return false; // File is valid UTF-8, thus it's text
        }
    
        $nonPrintableChars = 0;
        $length = strlen($contents);
    
        // Count non-printable characters
        for ($i = 0; $i < $length; $i++) {
            $char = $contents[$i];
            if (ord($char) < 32 && !in_array($char, ["\n", "\r", "\t"])) {
                $nonPrintableChars++;
            }
        }
    
        $ratio = $nonPrintableChars / $length;
        
        return $ratio > $threshold;
    }
    

    public function updateFiles(string $json): void
    {
        $data = json_decode($json, true);
        if (!isset($data['files'])) {
            echo "Invalid JSON format.";
            return;
        }

        foreach ($data['files'] as $file) {
            $relativePath = $file['path'];
            $fullPath = $this->canonicalizePath($this->root . '/' . $relativePath);

            if ($this->isIgnored($fullPath) || !str_starts_with($fullPath, $this->root)) {
                echo "Skipping file: $relativePath\n";
                continue;
            }

            file_put_contents($fullPath, $file['content']);
            echo "Updated file: $relativePath\n";
        }
    }
}
