<?php
define('BLOG_PRO', true);
require_once '../config.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Test vytvoření příspěvku</h2>";

// Test 1: Připojení k databázi
echo "<h3>1. Test databáze:</h3>";
try {
    $db = new Database();
    echo "✅ Databáze připojena<br>";
} catch (Exception $e) {
    echo "❌ Chyba: " . $e->getMessage() . "<br>";
    die();
}

// Test 2: Vytvoření Post objektu
echo "<h3>2. Test Post třídy:</h3>";
try {
    $post = new Post();
    echo "✅ Post třída funguje<br>";
} catch (Exception $e) {
    echo "❌ Chyba: " . $e->getMessage() . "<br>";
    die();
}

// Test 3: Testovací data
echo "<h3>3. Testovací data:</h3>";
$testData = [
    'title' => 'Testovací příspěvek',
    'content' => '<p>Toto je testovací obsah příspěvku.</p>',
    'category_id' => null,
    'author_id' => 1,
    'status' => 'draft',
    'meta_title' => 'Testovací příspěvek',
    'meta_description' => 'Popis testovacího příspěvku',
    'meta_keywords' => 'test, příspěvek'
];

echo "<pre>";
print_r($testData);
echo "</pre>";

// Test 4: Vytvoření příspěvku
echo "<h3>4. Pokus o vytvoření:</h3>";
try {
    $result = $post->create($testData);
    
    echo "<pre>";
    print_r($result);
    echo "</pre>";
    
    if ($result['success']) {
        echo "✅ <strong>ÚSPĚCH! Příspěvek byl vytvořen!</strong><br>";
        echo "ID: " . $result['id'] . "<br>";
        echo "Slug: " . $result['slug'] . "<br>";
    } else {
        echo "❌ <strong>CHYBA:</strong> " . ($result['message'] ?? 'Neznámá chyba') . "<br>";
    }
    
} catch (Exception $e) {
    echo "❌ <strong>VÝJIMKA:</strong> " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
