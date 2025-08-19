<?php
/**
 * Kategóriák és alkategóriák kezelő függvények
 */

require_once 'config.php';

class CategoryManager {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Összes kategória lekérése hierarchikus struktúrában
     */
    public function getAllCategoriesHierarchical() {
        try {
            $stmt = $this->db->prepare("
                SELECT c.*, parent.name as parent_name,
                       COUNT(p.id) as product_count
                FROM categories c
                LEFT JOIN categories parent ON c.parent_id = parent.id
                LEFT JOIN products p ON c.id = p.category_id AND p.is_active = 1
                WHERE c.is_active = 1
                GROUP BY c.id
                ORDER BY 
                    CASE WHEN c.parent_id IS NULL THEN c.id ELSE c.parent_id END,
                    c.parent_id IS NULL DESC,
                    c.name
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Hiba a kategóriák lekérése során: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Főkategóriák lekérése
     */
    public function getMainCategories() {
        try {
            $stmt = $this->db->prepare("
                SELECT c.*, COUNT(p.id) as product_count,
                       COUNT(sub.id) as subcategory_count
                FROM categories c
                LEFT JOIN products p ON c.id = p.category_id AND p.is_active = 1
                LEFT JOIN categories sub ON c.id = sub.parent_id AND sub.is_active = 1
                WHERE c.parent_id IS NULL AND c.is_active = 1
                GROUP BY c.id
                ORDER BY c.name
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Hiba a főkategóriák lekérése során: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Alkategóriák lekérése egy főkategóriához
     */
    public function getSubcategories($parentId) {
        try {
            $stmt = $this->db->prepare("
                SELECT c.*, COUNT(p.id) as product_count
                FROM categories c
                LEFT JOIN products p ON c.id = p.category_id AND p.is_active = 1
                WHERE c.parent_id = ? AND c.is_active = 1
                GROUP BY c.id
                ORDER BY c.name
            ");
            $stmt->execute([$parentId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Hiba az alkategóriák lekérése során: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Kategória részletek lekérése
     */
    public function getCategoryDetails($categoryId) {
        try {
            $stmt = $this->db->prepare("
                SELECT c.*, parent.name as parent_name,
                       COUNT(p.id) as product_count
                FROM categories c
                LEFT JOIN categories parent ON c.parent_id = parent.id
                LEFT JOIN products p ON c.id = p.category_id AND p.is_active = 1
                WHERE c.id = ? AND c.is_active = 1
                GROUP BY c.id
            ");
            $stmt->execute([$categoryId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Hiba a kategória részletek lekérése során: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Új kategória létrehozása
     */
    public function createCategory($name, $description, $parentId = null, $imageUrl = null, $slug = null) {
        try {
            if (!$slug) {
                $slug = $this->generateSlug($name);
            }
            
            $stmt = $this->db->prepare("
                INSERT INTO categories (name, description, parent_id, image_url, slug, is_active)
                VALUES (?, ?, ?, ?, ?, 1)
            ");
            $stmt->execute([$name, $description, $parentId, $imageUrl, $slug]);
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Hiba a kategória létrehozása során: " . $e->getMessage());
            throw new Exception("Nem sikerült létrehozni a kategóriát: " . $e->getMessage());
        }
    }
    
    /**
     * Kategória frissítése
     */
    public function updateCategory($id, $name, $description, $parentId = null, $imageUrl = null, $slug = null) {
        try {
            // Ellenőrizzük, hogy a szülő kategória nem önmaga
            if ($parentId == $id) {
                throw new Exception("A kategória nem lehet saját maga szülője!");
            }
            
            // Ellenőrizzük, hogy nincs körkörös hivatkozás
            if ($parentId && $this->hasCircularReference($id, $parentId)) {
                throw new Exception("Körkörös hivatkozás nem engedélyezett!");
            }
            
            if (!$slug) {
                $slug = $this->generateSlug($name);
            }
            
            $stmt = $this->db->prepare("
                UPDATE categories 
                SET name = ?, description = ?, parent_id = ?, image_url = ?, slug = ?
                WHERE id = ?
            ");
            $stmt->execute([$name, $description, $parentId, $imageUrl, $slug, $id]);
            return true;
        } catch (PDOException $e) {
            error_log("Hiba a kategória frissítése során: " . $e->getMessage());
            throw new Exception("Nem sikerült frissíteni a kategóriát: " . $e->getMessage());
        }
    }
    
    /**
     * Kategória törlése
     */
    public function deleteCategory($id) {
        try {
            $this->db->beginTransaction();
            
            // Ellenőrizzük, hogy vannak-e alkategóriák
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM categories WHERE parent_id = ?");
            $stmt->execute([$id]);
            $subcategoryCount = $stmt->fetchColumn();
            
            if ($subcategoryCount > 0) {
                throw new Exception("Nem törölhető a kategória, mert vannak alkategóriái!");
            }
            
            // Ellenőrizzük, hogy vannak-e termékek
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
            $stmt->execute([$id]);
            $productCount = $stmt->fetchColumn();
            
            if ($productCount > 0) {
                throw new Exception("Nem törölhető a kategória, mert vannak hozzá tartozó termékek!");
            }
            
            // Kategória törlése
            $stmt = $this->db->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Hiba a kategória törlése során: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Körkörös hivatkozás ellenőrzése
     */
    private function hasCircularReference($categoryId, $newParentId) {
        $currentParent = $newParentId;
        $visited = [];
        
        while ($currentParent) {
            if ($currentParent == $categoryId || in_array($currentParent, $visited)) {
                return true;
            }
            
            $visited[] = $currentParent;
            
            $stmt = $this->db->prepare("SELECT parent_id FROM categories WHERE id = ?");
            $stmt->execute([$currentParent]);
            $currentParent = $stmt->fetchColumn();
        }
        
        return false;
    }
    
    /**
     * URL-barát slug generálása
     */
    private function generateSlug($name) {
        $slug = strtolower($name);
        $slug = preg_replace('/[áàâä]/u', 'a', $slug);
        $slug = preg_replace('/[éèêë]/u', 'e', $slug);
        $slug = preg_replace('/[íìîï]/u', 'i', $slug);
        $slug = preg_replace('/[óòôö]/u', 'o', $slug);
        $slug = preg_replace('/[úùûü]/u', 'u', $slug);
        $slug = preg_replace('/[ő]/u', 'o', $slug);
        $slug = preg_replace('/[ű]/u', 'u', $slug);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        
        // Ellenőrizzük az egyediséget
        $counter = 0;
        $originalSlug = $slug;
        
        do {
            if ($counter > 0) {
                $slug = $originalSlug . '-' . $counter;
            }
            
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM categories WHERE slug = ?");
            $stmt->execute([$slug]);
            $exists = $stmt->fetchColumn() > 0;
            
            $counter++;
        } while ($exists && $counter < 100);
        
        return $slug;
    }
    
    /**
     * Kategória breadcrumb útvonal generálása
     */
    public function getBreadcrumb($categoryId) {
        $breadcrumb = [];
        $currentId = $categoryId;
        
        while ($currentId) {
            $stmt = $this->db->prepare("SELECT id, name, parent_id, slug FROM categories WHERE id = ?");
            $stmt->execute([$currentId]);
            $category = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$category) break;
            
            array_unshift($breadcrumb, $category);
            $currentId = $category['parent_id'];
        }
        
        return $breadcrumb;
    }
}

?> 