-- Sample data for Fresh Harvest website
USE fresh_harvest;

-- Add admin user if not exists already (password: admin123)
INSERT IGNORE INTO users (username, email, password, full_name, role) VALUES
('admin', 'admin@freshharvest.com', '$2y$10$YWRtaW4xMjM=', 'Admin User', 'admin');

-- Add sample sellers
INSERT IGNORE INTO users (username, email, password, full_name, phone, address, role) VALUES
('sunvalleyfarm', 'sunvalley@example.com', '$2y$10$c2VsbGVyMTIz', 'Sun Valley Farm', '+880 1712345678', 'Rajshahi Region, Bangladesh', 'seller'),
('greenfieldorchards', 'greenfield@example.com', '$2y$10$c2VsbGVyMTIz', 'Greenfield Orchards', '+880 1812345678', 'Khulna Division, Bangladesh', 'seller'),
('tropicalfruits', 'tropical@example.com', '$2y$10$c2VsbGVyMTIz', 'Tropical Fruits Co.', '+880 1912345678', 'Chittagong Hill Tracts, Bangladesh', 'seller'),
('organicgardens', 'organic@example.com', '$2y$10$c2VsbGVyMTIz', 'Organic Gardens', '+880 1612345678', 'Sylhet Division, Bangladesh', 'seller');

-- Add seller profiles
INSERT IGNORE INTO seller_profiles (seller_id, user_id, farm_name, description, location, is_verified) VALUES
(1, (SELECT user_id FROM users WHERE username = 'sunvalleyfarm'), 'Sun Valley Farm', 'Family-owned farm specializing in high-quality seasonal fruits. Our fruits are grown with care and harvested at peak ripeness.', 'Rajshahi Region, Bangladesh', 1),
(2, (SELECT user_id FROM users WHERE username = 'greenfieldorchards'), 'Greenfield Orchards', 'Established in 1990, we grow a variety of stone fruits and citrus. Known for our sweet mangoes and juicy litchis.', 'Khulna Division, Bangladesh', 1),
(3, (SELECT user_id FROM users WHERE username = 'tropicalfruits'), 'Tropical Fruits Co.', 'Specializing in tropical fruits grown in the lush hill tracts. Our fruits are exotic and full of flavor.', 'Chittagong Hill Tracts, Bangladesh', 1),
(4, (SELECT user_id FROM users WHERE username = 'organicgardens'), 'Organic Gardens', 'Certified organic farm with sustainable farming practices. We grow fruits without pesticides or chemical fertilizers.', 'Sylhet Division, Bangladesh', 1);

-- Insert categories if not exists
INSERT IGNORE INTO categories (name, description) VALUES
('Citrus', 'Citrus fruits like oranges, lemons, and limes'),
('Tropical', 'Tropical fruits such as mangoes, pineapples, and bananas'),
('Berries', 'Various berries including strawberries, blueberries, and raspberries'),
('Stone Fruits', 'Fruits with pits, like peaches, plums, and cherries'),
('Melons', 'Watermelons, cantaloupes, and honeydews');

-- Insert sample fruits (20+)
INSERT INTO fruits (seller_id, category_id, name, description, price_per_kg, stock_quantity, is_organic, is_available, image) VALUES
-- Tropical Fruits - Seller 1
(1, 2, 'Alphonso Mango', 'Premium Alphonso mangoes known for their sweet taste and rich flavor. These golden-yellow fruits are perfect for eating fresh or making desserts.', 250.00, 100, 0, 1, 'alphonso_mango.jpg'),
(1, 2, 'Green Mango', 'Unripe green mangoes perfect for making pickles, chutneys, and savory dishes. Has a sour, tangy taste that adds flavor to many recipes.', 120.00, 150, 0, 1, 'green_mango.jpg'),
(1, 2, 'Jackfruit', 'Sweet and aromatic jackfruit with yellow flesh. The national fruit of Bangladesh, known for its unique flavor and texture.', 180.00, 80, 0, 1, 'jackfruit.jpg'),
(1, 2, 'Papaya', 'Sweet and juicy papayas with bright orange flesh. Rich in vitamins and enzymes, great for digestion and skin health.', 90.00, 120, 0, 1, 'papaya.jpg'),
(1, 2, 'Banana', 'Fresh locally grown bananas, sweet and nutritious. Perfect for quick snacks or adding to desserts.', 60.00, 200, 0, 1, 'banana.jpg'),

-- Citrus Fruits - Seller 2
(2, 1, 'Sweet Orange', 'Juicy sweet oranges full of vitamin C. Perfect for fresh juice or eating as a healthy snack.', 150.00, 100, 0, 1, 'sweet_orange.jpg'),
(2, 1, 'Lemon', 'Fresh tangy lemons, essential for cooking, beverages, and cleaning. Adds zest to any dish or drink.', 100.00, 120, 0, 1, 'lemon.jpg'),
(2, 1, 'Key Lime', 'Small, aromatic limes with a distinctive flavor. Perfect for making key lime pie or adding to drinks.', 120.00, 100, 0, 1, 'key_lime.jpg'),
(2, 1, 'Pomelo', 'Large citrus fruit with refreshing sweet-tart flavor. Has thick peel and juicy segments.', 200.00, 70, 0, 1, 'pomelo.jpg'),
(2, 1, 'Grapefruit', 'Ruby red grapefruit with sweet-tart flavor. Excellent for breakfast or fresh juicing.', 180.00, 80, 0, 1, 'grapefruit.jpg'),

-- Stone Fruits - Seller 3
(3, 4, 'Litchi', 'Sweet, juicy litchis with fragrant, translucent white flesh. One of Bangladesh\'s favorite summer fruits.', 300.00, 120, 0, 1, 'litchi.jpg'),
(3, 4, 'Plum', 'Juicy dark purple plums with sweet flesh. Great for eating fresh or using in desserts.', 220.00, 90, 0, 1, 'plum.jpg'),
(3, 4, 'Peach', 'Sweet, aromatic peaches with fuzzy skin and juicy flesh. Perfect for fresh eating or baking.', 250.00, 85, 0, 1, 'peach.jpg'),
(3, 4, 'Cherry', 'Sweet dark red cherries, handpicked at perfect ripeness. A seasonal delicacy.', 400.00, 60, 0, 1, 'cherry.jpg'),
(3, 4, 'Apricot', 'Golden apricots with sweet-tart flavor. Rich in vitamins and perfect for jams.', 280.00, 75, 0, 1, 'apricot.jpg'),

-- Organic Fruits - Seller 4
(4, 2, 'Organic Pineapple', 'Sweet and tangy organic pineapples grown without pesticides. Rich in vitamins and bromelain enzyme.', 180.00, 100, 1, 1, 'organic_pineapple.jpg'),
(4, 3, 'Organic Strawberry', 'Juicy red organic strawberries grown using sustainable farming methods. Sweet and aromatic.', 450.00, 60, 1, 1, 'organic_strawberry.jpg'),
(4, 5, 'Organic Watermelon', 'Sweet, juicy organic watermelon perfect for hot summer days. Grown without chemical fertilizers.', 120.00, 70, 1, 1, 'organic_watermelon.jpg'),
(4, 1, 'Organic Mandarin', 'Sweet, easy-to-peel organic mandarins. Excellent source of vitamin C and perfect lunchbox addition.', 220.00, 90, 1, 1, 'organic_mandarin.jpg'),
(4, 2, 'Organic Dragon Fruit', 'Exotic dragon fruit with vibrant pink skin and speckled flesh. Mildly sweet with crunchy seeds.', 350.00, 50, 1, 1, 'organic_dragonfruit.jpg'),

-- Melons - Mix of Sellers
(1, 5, 'Watermelon', 'Large, juicy watermelons with sweet red flesh and black seeds. Perfect refreshment for hot days.', 80.00, 150, 0, 1, 'watermelon.jpg'),
(2, 5, 'Honeydew Melon', 'Sweet, juicy honeydew melons with pale green flesh. Refreshing and hydrating.', 150.00, 90, 0, 1, 'honeydew.jpg'),
(3, 5, 'Cantaloupe', 'Aromatic cantaloupes with orange flesh. Sweet, juicy, and nutritious.', 170.00, 85, 0, 1, 'cantaloupe.jpg'),

-- Additional Berries - Seller 4
(4, 3, 'Organic Blueberry', 'Plump, sweet organic blueberries packed with antioxidants. Excellent for desserts or eating fresh.', 600.00, 40, 1, 1, 'organic_blueberry.jpg'),
(4, 3, 'Organic Raspberry', 'Delicate, sweet-tart organic raspberries. Perfect for fresh eating or making preserves.', 550.00, 45, 1, 1, 'organic_raspberry.jpg');

-- Insert harvest seasons for fruits
INSERT INTO harvest_seasons (fruit_id, start_date, end_date, region, notes) VALUES
-- Mangoes
(1, '2024-05-01', '2024-07-31', 'Rajshahi, Bangladesh', 'Peak season for Alphonso mangoes'),
(2, '2024-04-01', '2024-08-31', 'Rajshahi, Bangladesh', 'Available for longer period but best in early summer'),

-- Tropical fruits
(3, '2024-04-01', '2024-09-30', 'Khulna Division, Bangladesh', 'Main jackfruit season'),
(4, '2024-01-01', '2024-12-31', 'Chittagong, Bangladesh', 'Available year-round but best in summer'),
(5, '2024-01-01', '2024-12-31', 'Dhaka Division, Bangladesh', 'Available year-round'),

-- Citrus
(6, '2024-11-01', '2024-02-28', 'Sylhet, Bangladesh', 'Winter is peak season for oranges'),
(7, '2024-01-01', '2024-12-31', 'Khulna Division, Bangladesh', 'Available year-round with peak in winter'),
(8, '2024-05-01', '2024-09-30', 'Sylhet, Bangladesh', 'Peak season in summer'),
(9, '2024-09-01', '2024-12-31', 'Khulna Division, Bangladesh', 'Best harvested in late fall'),
(10, '2024-11-01', '2024-02-28', 'Chittagong, Bangladesh', 'Winter harvest has best flavor'),

-- Stone fruits
(11, '2024-05-01', '2024-06-30', 'Rajshahi, Bangladesh', 'Short but abundant season'),
(12, '2024-06-01', '2024-08-31', 'Dhaka Division, Bangladesh', 'Summer is peak season'),
(13, '2024-05-15', '2024-07-31', 'Rajshahi, Bangladesh', 'Late spring to early summer season'),
(14, '2024-05-01', '2024-06-30', 'Mymensingh, Bangladesh', 'Limited harvest season'),
(15, '2024-05-15', '2024-07-15', 'Rajshahi, Bangladesh', 'Short season, harvest when golden'),

-- Organic fruits
(16, '2024-03-01', '2024-06-30', 'Sylhet, Bangladesh', 'Spring-summer harvest yields sweetest fruit'),
(17, '2024-01-01', '2024-03-31', 'Sylhet, Bangladesh', 'Winter harvest with peak flavor'),
(18, '2024-04-01', '2024-07-31', 'Sylhet, Bangladesh', 'Summer is the perfect melon season'),
(19, '2024-11-01', '2024-02-28', 'Sylhet, Bangladesh', 'Winter crop has best flavor'),
(20, '2024-06-01', '2024-09-30', 'Sylhet, Bangladesh', 'Summer and early fall season'),

-- Melons
(21, '2024-04-01', '2024-08-31', 'Rajshahi, Bangladesh', 'Summer is peak watermelon season'),
(22, '2024-05-01', '2024-08-31', 'Khulna Division, Bangladesh', 'Harvested in hot summer months'),
(23, '2024-04-15', '2024-08-15', 'Chittagong, Bangladesh', 'Best flavor in middle of summer'),

-- Berries
(24, '2024-01-01', '2024-02-28', 'Sylhet, Bangladesh', 'Limited winter harvest'),
(25, '2024-01-15', '2024-03-15', 'Sylhet, Bangladesh', 'Early spring harvest');

-- Insert some sample reviews for fruits
INSERT INTO reviews (fruit_id, user_id, rating, comment, created_at) VALUES
(1, 1, 5, 'The Alphonso mangoes were incredibly sweet and fragrant. Best I\'ve ever tasted!', '2024-06-15 14:30:00'),
(1, 4, 4, 'Very good quality mangoes, arrived perfectly ripe.', '2024-06-17 09:45:00'),
(6, 1, 5, 'These oranges were so juicy and sweet. Perfect for fresh juice!', '2024-01-10 11:20:00'),
(11, 1, 5, 'The litchis were amazingly fresh and sweet. Will definitely buy again!', '2024-05-25 16:10:00'),
(16, 1, 4, 'This organic pineapple was very sweet and had a wonderful aroma.', '2024-04-12 13:15:00'),
(21, 4, 5, 'The watermelon was perfectly ripe and extremely sweet. Great for hot days!', '2024-07-05 17:30:00'); 