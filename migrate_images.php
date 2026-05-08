<?php
require 'includes/db.php';

$html = file_get_contents('models.html');

$doc = new DOMDocument();
@$doc->loadHTML($html);

$xpath = new DOMXPath($doc);

$categories = ['women', 'men', 'kids'];

foreach ($categories as $cat) {
    $cards = $xpath->query("//div[@id='$cat']//div[contains(@class, 'model-card')]");
    
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
        } else {
            // Find the img tag inside
            $img = $xpath->query(".//img", $card)->item(0);
            if ($img) {
                $src = $img->getAttribute('src');
                if ($src) {
                    $images = [$src];
                }
            }
        }
        
        // viewSizing('Name','Cat',{...},this)
        if (preg_match('/viewSizing\s*\(\s*([\'"])(.*?)\1\s*,/', $onclickAttr, $matches)) {
            $name = $matches[2];
            $imagesJson = json_encode($images);
            
            // update existing model in db
            $stmt = $pdo->prepare("UPDATE models SET images = ? WHERE name = ?");
            $stmt->execute([$imagesJson, $name]);
            echo "Updated images for $name: $imagesJson\n";
        }
    }
}
