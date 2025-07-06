-- Create product_images table to better manage images for products
CREATE TABLE IF NOT EXISTS `product_images` (
  `image_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `product_type` enum('fruit','agricultural') NOT NULL DEFAULT 'fruit',
  `image_path` varchar(255) NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`image_id`),
  KEY `product_id` (`product_id`,`product_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample data - adjust file paths as needed 
-- These paths should point to images in your img directory
INSERT INTO `product_images` (`product_id`, `product_type`, `image_path`, `is_primary`, `sort_order`) VALUES
(1, 'fruit', 'img/apple1.jpg', 1, 1),
(1, 'fruit', 'img/apple2.jpg', 0, 2),
(2, 'fruit', 'img/orange1.jpg', 1, 1),
(2, 'fruit', 'img/orange2.jpg', 0, 2),
(3, 'fruit', 'img/banana1.jpg', 1, 1),
(3, 'fruit', 'img/banana2.jpg', 0, 2),
(4, 'fruit', 'img/mango1.jpg', 1, 1),
(4, 'fruit', 'img/mango2.jpg', 0, 2),
(5, 'fruit', 'img/strawberry1.jpg', 1, 1),
(5, 'fruit', 'img/strawberry2.jpg', 0, 2),
(6, 'fruit', 'img/grape1.jpg', 1, 1),
(6, 'fruit', 'img/grape2.jpg', 0, 2),
(7, 'fruit', 'img/pineapple1.jpg', 1, 1),
(7, 'fruit', 'img/pineapple2.jpg', 0, 2),
(8, 'fruit', 'img/watermelon1.jpg', 1, 1),
(8, 'fruit', 'img/watermelon2.jpg', 0, 2),
(9, 'fruit', 'img/kiwi1.jpg', 1, 1),
(9, 'fruit', 'img/kiwi2.jpg', 0, 2),
(10, 'fruit', 'img/peach1.jpg', 1, 1),
(10, 'fruit', 'img/peach2.jpg', 0, 2),
(11, 'fruit', 'img/plum1.jpg', 1, 1),
(11, 'fruit', 'img/plum2.jpg', 0, 2),
(12, 'fruit', 'img/cherry1.jpg', 1, 1),
(12, 'fruit', 'img/cherry2.jpg', 0, 2),
(13, 'fruit', 'img/papaya1.jpg', 1, 1),
(13, 'fruit', 'img/papaya2.jpg', 0, 2),
(14, 'fruit', 'img/guava1.jpg', 1, 1),
(14, 'fruit', 'img/guava2.jpg', 0, 2),
(15, 'fruit', 'img/lychee1.jpg', 1, 1),
(15, 'fruit', 'img/lychee2.jpg', 0, 2);

-- Add procedure to update fruit images from the product_images table
DELIMITER //
CREATE PROCEDURE update_fruit_images()
BEGIN
    -- Update primary images for all fruits
    UPDATE fruits f
    JOIN (
        SELECT product_id, image_path 
        FROM product_images 
        WHERE product_type = 'fruit' AND is_primary = 1
    ) pi ON f.fruit_id = pi.product_id
    SET f.image = pi.image_path;
    
    SELECT CONCAT('Updated images for ', ROW_COUNT(), ' fruits') AS result;
END //
DELIMITER ;

-- Add procedure to update agricultural product images if that table exists
DELIMITER //
CREATE PROCEDURE update_agricultural_images()
BEGIN
    -- Check if agricultural_products table exists
    IF EXISTS (SELECT * FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'agricultural_products') THEN
        -- Update primary images for all agricultural products
        UPDATE agricultural_products ap
        JOIN (
            SELECT product_id, image_path 
            FROM product_images 
            WHERE product_type = 'agricultural' AND is_primary = 1
        ) pi ON ap.product_id = pi.product_id
        SET ap.image = pi.image_path;
        
        SELECT CONCAT('Updated images for ', ROW_COUNT(), ' agricultural products') AS result;
    ELSE
        SELECT 'Table agricultural_products does not exist' AS result;
    END IF;
END //
DELIMITER ;

-- Create a function to get all images for a product
DELIMITER //
CREATE FUNCTION get_product_images(p_product_id INT, p_product_type VARCHAR(20)) 
RETURNS TEXT
DETERMINISTIC
BEGIN
    DECLARE result TEXT DEFAULT '';
    
    SELECT GROUP_CONCAT(image_path SEPARATOR '|')
    INTO result
    FROM product_images
    WHERE product_id = p_product_id AND product_type = p_product_type
    ORDER BY is_primary DESC, sort_order ASC;
    
    RETURN result;
END //
DELIMITER ; 