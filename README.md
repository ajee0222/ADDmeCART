# ADDmeCART 🛒

A lightweight e-commerce catalog and inventory management system built with Symfony 8, Twig, and SQLite.

## 🛠️ Prerequisites
Before you begin, ensure you have the following installed on your machine:
* **PHP** (v8.2 or higher)
* **Composer** (PHP dependency manager)
* **Git**

---

## 🚀 First-Time Setup (Onboarding)
If this is your first time working on the project, follow these steps to rebuild the environment on your local machine.

**1. Clone the repository**
```bash
git clone [https://github.com/ajee0222/ADDmeCART.git](https://github.com/ajee0222/ADDmeCART.git)
cd ADDmeCART

composer install

php bin/console doctrine:database:create
php bin/console doctrine:schema:create

php -S 0.0.0.0:8000 -t public
