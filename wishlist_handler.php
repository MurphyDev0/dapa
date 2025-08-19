<?php
require_once 'session_config.php';
require_once 'config.php';

// Alapértelmezett válasz
$response = [
    'success' => false,
    'message' => 'Ismeretlen hiba történt.',
    'error' => null
];

// Ellenőrizzük, hogy be van-e jelentkezve a felhasználó
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'A kívánságlista használatához be kell jelentkezni.';
    $response['error'] = 'not_logged_in';
    echo json_encode($response);
    exit();
}

// Felhasználó azonosítója
$userId = $_SESSION['user_id'];

// Ellenőrizzük, hogy megfelelő-e a kérés (POST + megfelelő action)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    // Kívánságlistához adás
    if ($action === 'add_to_wishlist' && isset($_POST['product_id'])) {
        $productId = intval($_POST['product_id']);
        
        // Ellenőrizzük, hogy a termék létezik-e
        $checkProduct = $db->prepare("SELECT id FROM products WHERE id = ?");
        $checkProduct->execute([$productId]);
        
        if ($checkProduct->rowCount() > 0) {
            // Ellenőrizzük, hogy a termék már szerepel-e a kívánságlistában
            $checkWishlist = $db->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
            $checkWishlist->execute([$userId, $productId]);
            
            if ($checkWishlist->rowCount() > 0) {
                // A termék már szerepel a kívánságlistában
                $response['success'] = true;
                $response['message'] = 'A termék már szerepel a kívánságlistában.';
            } else {
                // Hozzáadjuk a terméket a kívánságlistához
                $insertWishlist = $db->prepare("INSERT INTO wishlist (user_id, product_id, created_at) VALUES (?, ?, NOW())");
                
                if ($insertWishlist->execute([$userId, $productId])) {
                    $response['success'] = true;
                    $response['message'] = 'Termék sikeresen hozzáadva a kívánságlistához.';
                } else {
                    $response['message'] = 'Hiba történt a kívánságlistához adás közben.';
                    $response['error'] = 'database_error';
                }
            }
        } else {
            $response['message'] = 'A megadott termék nem létezik.';
            $response['error'] = 'product_not_found';
        }
    } 
    // Kívánságlistából eltávolítás (ezt is implementálhatjuk, ha szükséges)
    else if ($action === 'remove_from_wishlist' && isset($_POST['wishlist_id'])) {
        $wishlistId = intval($_POST['wishlist_id']);
        
        // Ellenőrizzük, hogy a kívánságlista elem a felhasználóé-e
        $checkOwnership = $db->prepare("SELECT id FROM wishlist WHERE id = ? AND user_id = ?");
        $checkOwnership->execute([$wishlistId, $userId]);
        
        if ($checkOwnership->rowCount() > 0) {
            // Töröljük a kívánságlista elemet
            $deleteWishlist = $db->prepare("DELETE FROM wishlist WHERE id = ?");
            
            if ($deleteWishlist->execute([$wishlistId])) {
                $response['success'] = true;
                $response['message'] = 'Termék sikeresen eltávolítva a kívánságlistából.';
            } else {
                $response['message'] = 'Hiba történt a kívánságlistából törlés közben.';
                $response['error'] = 'database_error';
            }
        } else {
            $response['message'] = 'A megadott kívánságlista elem nem létezik vagy nem a felhasználóé.';
            $response['error'] = 'wishlist_not_found';
        }
    } else {
        $response['message'] = 'Érvénytelen művelet.';
        $response['error'] = 'invalid_action';
    }
} else {
    $response['message'] = 'Érvénytelen kérés.';
    $response['error'] = 'invalid_request';
}

// Válasz küldése JSON formátumban
header('Content-Type: application/json');
echo json_encode($response);
?> 