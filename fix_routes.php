<?php
$dir = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('c:\xampp\htdocs\sistema-assistencia\app'));
$count = 0;
foreach($dir as $file) {
    if($file->isFile() && $file->getExtension() === 'php') {
        $path = $file->getRealPath();
        $content = file_get_contents($path);
        // Look for '/sistema-assistencia/public/' and replace it with '/'
        $newContent = str_replace("'/sistema-assistencia/public/", "'/", $content);
        
        // Also look for double quotes
        $newContent = str_replace('"/sistema-assistencia/public/', '"/', $newContent);

        if($content !== $newContent) {
            file_put_contents($path, $newContent);
            echo "Updated {$path}\n";
            $count++;
        }
    }
}
echo "Total updated: $count files.\n";
