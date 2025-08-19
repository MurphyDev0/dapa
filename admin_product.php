            <!-- Új termék létrehozása -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4 text-primary-darkest">Új termék létrehozása</h2>
                <form method="post" action="admin_product.php" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-primary-darkest mb-1">Termék neve</label>
                            <input type="text" id="name" name="name" required class="w-full px-4 py-2 border border-primary-light rounded-md focus:outline-none focus:ring-2 focus:ring-primary-light">
                        </div>
                        <div>
                            <label for="price" class="block text-sm font-medium text-primary-darkest mb-1">Ár (Ft)</label>
                            <input type="number" id="price" name="price" required class="w-full px-4 py-2 border border-primary-light rounded-md focus:outline-none focus:ring-2 focus:ring-primary-light">
                        </div>
                        <div>
                            <label for="stock" class="block text-sm font-medium text-primary-darkest mb-1">Készlet</label>
                            <input type="number" id="stock" name="stock" required class="w-full px-4 py-2 border border-primary-light rounded-md focus:outline-none focus:ring-2 focus:ring-primary-light">
                        </div>
                        <div>
                            <label for="category_id" class="block text-sm font-medium text-primary-darkest mb-1">Kategória</label>
                            <select id="category_id" name="category_id" required class="w-full px-4 py-2 border border-primary-light rounded-md focus:outline-none focus:ring-2 focus:ring-primary-light">
                                <option value="">Válassz kategóriát</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label for="description" class="block text-sm font-medium text-primary-darkest mb-1">Leírás</label>
                            <textarea id="description" name="description" rows="3" class="w-full px-4 py-2 border border-primary-light rounded-md focus:outline-none focus:ring-2 focus:ring-primary-light"></textarea>
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" name="add_product" class="btn btn-primary">Termék hozzáadása</button>
                    </div>
                </form>
            </div>

            <!-- Új kategória létrehozása -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4 text-primary-darkest">Új kategória létrehozása</h2>
                <form method="post" action="admin_product.php" class="space-y-4">
                    <div>
                        <label for="category_name" class="block text-sm font-medium text-primary-darkest mb-1">Kategória neve</label>
                        <input type="text" id="category_name" name="category_name" required class="w-full px-4 py-2 border border-primary-light rounded-md focus:outline-none focus:ring-2 focus:ring-primary-light">
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" name="add_category" class="btn btn-primary">Kategória hozzáadása</button>
                    </div>
                </form>
            </div> 