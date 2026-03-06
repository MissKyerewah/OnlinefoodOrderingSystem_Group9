<?php
function getMenuItems() {
    return [
        ['id'=>1,'name'=>'Margherita Pizza','category'=>'Pizza🍕','price'=>14.99,'description'=>'Classic tomato sauce, fresh mozzarella, basil leaves on hand-tossed dough.','emoji'=>'P','badge'=>'Bestseller','time'=>'20-25 min','rating'=>4.8],
        ['id'=>2,'name'=>'BBQ Chicken Burger','category'=>'Burgers🍔','price'=>12.49,'description'=>'Grilled chicken breast, smoky BBQ sauce, cheddar, crispy onion rings.','emoji'=>'B','badge'=>'Spicy','time'=>'15-20 min','rating'=>4.6],
        ['id'=>3,'name'=>'Sushi Platter (12 pcs)','category'=>'Sushi🍣','price'=>22.99,'description'=>"Chef's selection of nigiri and maki rolls with wasabi and pickled ginger.",'emoji'=>'S','badge'=>"Chef's Pick",'time'=>'25-30 min','rating'=>4.9],
        ['id'=>4,'name'=>'Pasta Carbonara','category'=>'Pasta🍝','price'=>13.99,'description'=>'Spaghetti with creamy egg sauce, pancetta, parmesan, and black pepper.','emoji'=>'P','badge'=>'New','time'=>'20-25 min','rating'=>4.7],
        ['id'=>5,'name'=>'Grilled Salmon Bowl','category'=>'Healthy','price'=>17.99,'description'=>'Atlantic salmon over jasmine rice with avocado, cucumber and sesame dressing.','emoji'=>'F','badge'=>'Healthy','time'=>'20-25 min','rating'=>4.7],
        ['id'=>6,'name'=>'Chicken Tacos (3 pcs)','category'=>'Mexican','price'=>11.49,'description'=>'Soft corn tortillas, seasoned chicken, pico de gallo, sour cream and lime.','emoji'=>'T','badge'=>'Popular','time'=>'15-20 min','rating'=>4.5],
        ['id'=>7,'name'=>'Veggie Buddha Bowl','category'=>'Healthy','price'=>10.99,'description'=>'Quinoa, roasted veggies, chickpeas, tahini dressing and fresh herbs.','emoji'=>'V','badge'=>'Vegan','time'=>'15-20 min','rating'=>4.4],
        ['id'=>8,'name'=>'Pepperoni Pizza','category'=>'Pizza','price'=>15.99,'description'=>'Loaded with premium pepperoni, mozzarella and our signature tomato sauce.','emoji'=>'P','badge'=>'Bestseller','time'=>'20-25 min','rating'=>4.8],
        ['id'=>9,'name'=>'Chocolate Lava Cake','category'=>'Desserts🍨','price'=>6.99,'description'=>'Warm dark chocolate cake with a molten center, served with vanilla ice cream.','emoji'=>'D','badge'=>'Sweet','time'=>'10-15 min','rating'=>4.9],
        ['id'=>10,'name'=>'Mango Lassi','category'=>'Drinks🥂','price'=>4.49,'description'=>'Chilled blend of fresh mango, yogurt, a touch of cardamom and rose water.','emoji'=>'L','badge'=>'Fresh','time'=>'5 min','rating'=>4.6],
        ['id'=>11,'name'=>'Double Smash Burger','category'=>'Burgers','price'=>14.49,'description'=>'Two smashed patties, American cheese, secret sauce, pickles, shredded lettuce.','emoji'=>'B','badge'=>'Hot','time'=>'15-20 min','rating'=>4.8],
        ['id'=>12,'name'=>'Chicken Tikka Masala','category'=>'Indian','price'=>15.49,'description'=>'Tender chicken in a rich, spiced tomato-cream curry, served with basmati rice.','emoji'=>'C','badge'=>'Spicy','time'=>'25-30 min','rating'=>4.7],
    ];
}
function getCategories() {
    return ['All','Pizza','Burgers','Sushi','Pasta','Healthy','Mexican','Indian','Desserts','Drinks'];
}
?>
