<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\User;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CartController extends AbstractController
{
    #[Route('/cart/add/{id}', name: 'app_cart_add')]
    public function add(int $id, ProductRepository $productRepo, EntityManagerInterface $entityManager): Response
    {
        // 1. REAL LOGIN: Get the currently logged-in user
        $user = $this->getUser();

        // 2. Security Check: If they aren't logged in, kick them to the login screen!
        if (!$user) {
            $this->addFlash('error', 'You must be logged in to add items to your cart.');
            return $this->redirectToRoute('app_login');
        }

        // 3. Fetch the Cart. If the user doesn't have one, build it.
        $cart = $user->getCart();
        if (!$cart) {
            $cart = new Cart();
            $cart->setUser($user);
            $entityManager->persist($cart);
        }

        // 4. Find the Product they clicked on
        $product = $productRepo->find($id);
        if (!$product) {
            throw $this->createNotFoundException('Product not found.');
        }

        // 5. ENFORCE THE LIMIT: Calculate total items currently in the cart
        $currentTotalItems = 0;
        foreach ($cart->getCartItems() as $item) {
            $currentTotalItems += $item->getQuantity();
        }

        if ($currentTotalItems >= 300) {
            $this->addFlash('error', 'Cart Limit Reached: You cannot add more than 300 items.');
            return $this->redirectToRoute('app_product_catalog');
        }

        // 6. Check if the product is already in the cart
        $existingCartItem = null;
        foreach ($cart->getCartItems() as $item) {
            if ($item->getProduct() === $product) {
                $existingCartItem = $item;
                break;
            }
        }

        if ($existingCartItem) {
            // Increase quantity if it's already there
            $existingCartItem->setQuantity($existingCartItem->getQuantity() + 1);
        } else {
            // Create a brand new line item
            $cartItem = new CartItem();
            $cartItem->setProduct($product);
            $cartItem->setQuantity(1);
            $cartItem->setCart($cart);
            $entityManager->persist($cartItem);
        }

        // 7. Save everything to SQLite and show a success message
        $entityManager->flush();
        $this->addFlash('success', $product->getName() . ' was added to your cart!');

        return $this->redirectToRoute('app_product_catalog');
    }

    #[Route('/cart', name: 'app_cart_index')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        // 1. REAL LOGIN: Get the currently logged-in user
        $user = $this->getUser();
        
        // 2. If no one is logged in, or they have no cart, show it as empty
        if (!$user || !method_exists($user, 'getCart') || !$user->getCart()) {
            return $this->render('cart/index.html.twig', [
                'cartItems' => [],
                'total' => 0
            ]);
        }

        // 3. Get the items and calculate the Grand Total
        $cartItems = $user->getCart()->getCartItems();
        $total = 0;
        
        foreach ($cartItems as $item) {
            $total += $item->getProduct()->getPrice() * $item->getQuantity();
        }

        // 4. Send the data to the visual template
        return $this->render('cart/index.html.twig', [
            'cartItems' => $cartItems,
            'total' => $total
        ]);
    }
    
    #[Route('/cart/remove/{id}', name: 'app_cart_remove')]
    public function remove(int $id, EntityManagerInterface $entityManager): Response
    {
        // 1. Get the currently logged-in user
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // 2. Find the exact cart item in the database
        $cartItem = $entityManager->getRepository(CartItem::class)->find($id);

        // 3. SECURITY: Make sure the item exists AND belongs to the person trying to delete it!
        if ($cartItem && $cartItem->getCart()->getUser() === $user) {
            $entityManager->remove($cartItem);
            $entityManager->flush();
            
            $this->addFlash('success', 'Item removed from your cart.');
        } else {
            $this->addFlash('error', 'Could not remove that item.');
        }

        // 4. Send them right back to the cart page to see the updated total
        return $this->redirectToRoute('app_cart_index');
    }
}