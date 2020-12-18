---
title: Add a product to a list
weight: 1
---

## Preconditions

Having a customer account NOT logged in\
No custom wishlist created (only the default one)\
Having 
- a product with stock
- a product without stock (stock = 0)
- a product with a minimum quantity
## Steps
| Step Description | Expected result |
| ----- | ----- |
| Browse through the catalog and open a product page | The heart next to the picture is empty |
| Click on the heart | A modal appears, telling you to log in before you can add products to your wishlists<br>There is a "Sign In" button |
| Click on the "Sign In" button<br>Log in as a customer | You are logged in |
| Go to the customer account<br>Click on the "Wishlist" block | Default wishlist is displayed<br>There is a (0) next to the title |
| Click on the default wishlist | The wishlist opens<br>It's empty |
| Browse through the catalog and open a product page<br>The product must be a single product with a lot of stock | The heart next to the picture is empty |
| Change the quantity to 5<br>Click on the heart | A modal appears, asking you to select a wishlist to add the product to |
| Select the default wishlist | The modal closes and the heart is now filled (even after a refresh of the page) |
| Go to the Wishlist in your customer account section<br>Open the default wishlist | The item is saved, with its quantity as 5 |
| Browse through the catalog and open a product page<br>The product must be a single product with ZERO stock<br>Click on the heart | A modal appears, asking you to select a wishlist to add the product to |
| Select the CUSTOM wishlist | The modal closes and the heart is now filled (even after a refresh of the page) |
| Go to the Wishlist in your customer account section<br>Open the custom wishlist | The item is saved, with its quantity as 0 |
| Browse through the catalog and open a product page<br>The product must be a single product with a minimal quantity<br>Change the quantity to a number below the minimal quantity<br>Click on the heart | A modal appears, asking you to select a wishlist to add the product to |
| Select the CUSTOM wishlist | The modal closes and the heart is now filled (even after a refresh of the page) |
| Go to the Wishlist in your customer account section<br>Open the custom wishlist | The item is saved, with its quantity as the minimal quantity set in the product setting |
| Browse through the catalog and open a product page<br>The product must be a product with combinations<br>Select a specific combination (NOT the default one)<br>Click on the heart | A modal appears, asking you to select a wishlist to add the product to |
| Select the custom wishlist | The modal closes and the heart is now filled |
| Change the combination | The heart empties |
| Get back to the saved combination | The heart refills automatically |
| Go to the Wishlist in your customer account section<br>Open the custom wishlist | The item is saved, for the specific combination |