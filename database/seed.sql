INSERT INTO categories (name) VALUES ('Signature bouquets'),('Seasonal stems'),('Flower boxes');
INSERT INTO products (category_id,name,description,image_path,price,stock_quantity) VALUES
((SELECT id FROM categories WHERE name='Signature bouquets'),'The Blush Edit','Soft pink roses, thoughtfully gathered for the little things worth celebrating.','assets/images/rose-bouquet.jpg',1250.00,16),
((SELECT id FROM categories WHERE name='Signature bouquets'),'Rose & Reverie','A generous gathering of roses wrapped with care. A quietly beautiful way to say it all.','assets/images/rose-bouquet.jpg',1850.00,12),
((SELECT id FROM categories WHERE name='Signature bouquets'),'Sunday Morning','A relaxed mixed bouquet with gentle color and an easy, garden-picked feel.','assets/images/mixed-bouquet.jpg',1450.00,15),
((SELECT id FROM categories WHERE name='Signature bouquets'),'A Little Romance','A petite rose bouquet for love notes, thank-yous, and just-because days.','assets/images/rose-bouquet.jpg',850.00,20),
((SELECT id FROM categories WHERE name='Seasonal stems'),'Tulip Season','Fresh tulips with graceful stems and joyful color. A little spring, any day.','assets/images/tulip-bouquet.jpg',1150.00,18),
((SELECT id FROM categories WHERE name='Seasonal stems'),'Garden Party','A playful mix of seasonal blooms in a full, loose arrangement.','assets/images/mixed-bouquet.jpg',1650.00,10),
((SELECT id FROM categories WHERE name='Seasonal stems'),'Softly, Always','A delicate selection of flowers with a gentle blush palette.','assets/images/rose-bouquet.jpg',1350.00,14),
((SELECT id FROM categories WHERE name='Seasonal stems'),'Golden Hour','Warm seasonal flowers for bright rooms and brighter days.','assets/images/mixed-bouquet.jpg',1250.00,14),
((SELECT id FROM categories WHERE name='Flower boxes'),'The Keepsake Box','A carefully arranged flower box, ready to give to someone you love.','assets/images/rose-bouquet.jpg',2250.00,8),
((SELECT id FROM categories WHERE name='Flower boxes'),'Love, In Bloom','A generous arrangement of roses with a gift-ready finish.','assets/images/rose-bouquet.jpg',2850.00,6),
((SELECT id FROM categories WHERE name='Flower boxes'),'Little Luxuries','A small but thoughtful arrangement of seasonal flowers.','assets/images/mixed-bouquet.jpg',1750.00,9),
((SELECT id FROM categories WHERE name='Flower boxes'),'For You, Always','A full arrangement of soft blooms for a memorable gesture.','assets/images/tulip-bouquet.jpg',2450.00,7);
