<?php
require 'includes/db.php';

$html = file_get_contents('models-new.html');

$doc = new DOMDocument();
@$doc->loadHTML($html);

$xpath = new DOMXPath($doc);

$categories = ['women', 'men', 'kids'];

foreach ($categories as $cat) {
    $cards = $xpath->query("//div[@id='$cat']//div[contains(@class, 'model-card')]");
    echo "Found " . $cards->length . " models in category: " . $cat . "\n";
    
    foreach ($cards as $card) {
        $imagesAttr = $card->getAttribute('data-images');
        $onclickAttr = $card->getAttribute('onclick');
        
        $images = [];
        if ($imagesAttr) {
            $imagesAttr = html_entity_decode($imagesAttr, ENT_QUOTES);
            $decoded = json_decode($imagesAttr, true);
            if (is_array($decoded)) {
                $images = $decoded;
            }
        }
        
        // viewSizing('Name','Cat',{...},this)
        if (preg_match('/viewSizing\s*\(\s*([\'"])(.*?)\1\s*,\s*([\'"])(.*?)\3\s*,\s*(\{.*?\})\s*,/s', $onclickAttr, $matches)) {
            $name = $matches[2];
            $category = $matches[4]; // might differ from $cat
            $measStr = $matches[5];
            
            // replace &quot; with "
            $measStr = html_entity_decode($measStr, ENT_QUOTES);
            
            // fix single quotes used as JSON keys/values (JSON requires double quotes)
            // It's a JS object, not valid JSON. e.g. {Height:'5`4',Chest:'32"'}
            // regex to convert JS object to valid JSON
            $measStr = preg_replace('/\'/', '"', $measStr); // replace ' with "
            // but what if there's an actual quote inside? It's tricky.
            // Let's just try basic string replacement for common keys
            // The format is like {'Shoe Size':'6',...} or {Height:'5`4',...}
            // we will replace ' with "
            // to handle valid json decode
            $measJson = preg_replace('/([a-zA-Z0-9\s_]+)\s*:/', '"$1":', $measStr);
            $measJson = str_replace('`', "'", $measJson); // if they used ` for feet
            
            // Just evaluate it in a quick context or manual parse?
            // Actually, we can just insert as is if we fix the quotes.
            $measArray = [];
            if (preg_match_all('/([\'"]?)([a-zA-Z0-9\s_]+)\1\s*:\s*([\'"])(.*?)\3/', $measStr, $m)) {
                for ($i=0; $i<count($m[0]); $i++) {
                    $k = trim($m[2][$i]);
                    $v = trim($m[4][$i]);
                    $measArray[$k] = $v;
                }
            }
            
            $imagesJson = json_encode($images);
            $measurementsJson = json_encode($measArray);
            
            // insert
            $stmt = $pdo->prepare("INSERT INTO models (name, category, measurements, images) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $category, $measurementsJson, $imagesJson]);
            echo "Inserted $name\n";
        } else {
            echo "Failed to parse: $onclickAttr\n";
        }
    }
}
