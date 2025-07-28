# Bitmap Billboard Canvas

Welcome to the Bitmap Billboard/Gallery, a Web2.5 application that brings Bitcoin Bitmaps to life. Our project is an interactive digital canvas of one million plots, where each plot corresponds to a unique Bitmap. Owners can customize their space, creating a vibrant, community-driven digital world tied directly to on-chain asset ownership.

## Project Goal

Our core objective is to build a vast, interactive digital map where Bitmap owners can express themselves. Through a user-friendly interface, owners can set a custom avatar (image or GIF) for their plot and configure a pop-up IFrame to display rich content like personal projects, social links, or galleries. I aim to merge the permanence of the blockchain with the dynamic, high-performance interactivity of the modern web.

## Features

The project is in a stable, feature-complete state. Here's what you can do:

### 🗺️ Main Gallery (`[bitmap_gallery]`)

  - **High-Performance Rendering**: Smoothly renders a massive map of one million individual plots using Three.js.
  - **Lazy Loading**: A milestone feature that dramatically improves performance. The gallery only fetches and renders avatar data for the currently visible area of the map, ensuring fast initial loads and efficient memory usage.
  - **Dynamic Avatars**: Displays custom static images and GIFs set by owners.
  - **Interactive Pop-ups**: Clicking an avatar opens a modal IFrame window with the owner's configured content.
  - **Seamless Navigation**:
      - **Search**: Instantly find and focus on any plot by its Bitmap ID.
      - **Deep Linking**: Share a direct link to any plot using the `?location=ID.bitmap` URL parameter.
      - **Intuitive Controls**: Pan and zoom across the map with fluid mouse controls.
  - **Hover Information**: See a plot's Bitmap ID by hovering over it.
  - **Cross-Browser Compatibility**: Works consistently across modern browsers like Chrome and Safari.
  - **Fullscreen Mode**: Immerse yourself in the gallery with a fully-featured fullscreen view.

### ✍️ User Configuration Form (`[bitmap_update_form]`)

  - **Three-Step Verification**: A secure and streamlined process for owners to update their plot.
  - **On-Chain Ownership Verification**:
      - The system verifies the user's ownership of a Bitmap by querying the Hiro API via a Cloudflare Worker.
      - Wallet control is confirmed by having the user place a unique code in their Magic Eden bio, which is then verified via the ME API.
  - **Enhanced Security**: The wallet address is locked after verification, and the entire flow is secured with server-side sessions and nonces to prevent tampering.
  - **User Experience Focused**:
      - Clear, internationalized (English) instructions guide the user through the process.
      - Detailed help text for finding Bitmap numbers, setting the ME bio, and understanding URL requirements.
      - **Live Image Preview**: A client-side CORS check attempts to preview the user's chosen image URL, guiding them to use a publicly accessible host if necessary.
      - Upon successful submission, the user receives a direct link to view their newly updated plot on the map.
  - **Data Persistence**: Successfully stores the user's wallet address as the `operator` in the database, linking the configuration to the verified owner.

## Installation

### Step 1: Install the Main Plugin

1.  Find the `bitmap-billboard.zip` file in the main directory of this project.
2.  Log in to your WordPress admin dashboard.
3.  Navigate to **Plugins → Add New**.
4.  Click the **Upload Plugin** button at the top of the page.
5.  Select the `bitmap-billboard.zip` file from your computer and click **Install Now**.
6.  Once the installation is complete, click **Activate Plugin**.

Upon activation, the plugin will automatically create the required `wp_bitmap_configs` database table.

### Step 2: Create and Populate the Inscriptions Table

For the ownership verification to work, the system needs a way to map a Bitmap number to its unique on-chain inscription ID. This requires a separate database table.

**1. Create the Table:**
Use a database management tool like Adminer or phpMyAdmin to run the following SQL command. This will create the empty `wp_bitmap_inscriptions` table.

```sql
CREATE TABLE `wp_bitmap_inscriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `bitmap_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `inscription_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sat_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**2. Populate the Table:**
The SQL files needed to populate this table are located in the `wp_bitmap_inscriptions sql files/` directory. You must import each of these `.sql` files into your database **in sequential order** (starting with `0-99999.sql`, then `100000-199999.sql`, and so on) until all files have been imported.
Data Source: https://github.com/ordengine/BitmapOCI
## Usage

### Displaying the Gallery (`[bitmap_gallery]`)

  - To show the main Bitmap Gallery on your site, create a new Page or Post.
  - In the WordPress editor, add a "Shortcode" block and enter:
    ```
    [bitmap_gallery]
    ```
  - Publish the page. Visitors can now view and interact with the gallery.

### User Update Form (`[bitmap_update_form]`)

This form allows Bitmap owners to verify their ownership and update their plot's avatar and content.

**1. Add the Code Snippet:**
I recommend using a plugin like [WP Code](https://wordpress.org/plugins/insert-headers-and-footers/) to manage this functionality.

  - The complete PHP code for the form is located in the file: `code snippet/bitmap update form.php`.
  - Copy the entire content of this file and paste it into a new snippet in your WordPress admin. Save and activate it.

**2. Display the Form:**
Once the snippet is active, you can display the form on any page by using the shortcode:

```
[bitmap_update_form]
```

### Required APIs and Recommended Cloudflare Workers

The verification process relies on two public APIs. To ensure reliability and avoid CORS issues, I **strongly recommend** using [Cloudflare Workers](https://developers.cloudflare.com/workers/) as a proxy. The code for these workers is provided in the `cloudflare worker/` directory.

1.  **Hiro API Proxy**: Fetches inscription ownership data.

      - The required worker code is in `cloudflare worker/Hiro api worker.js`.

2.  **Magic Eden API Proxy**: Fetches a user's profile bio to verify wallet ownership.

      - The required worker code is in `cloudflare worker/ME api worker.js`.

**IMPORTANT:** After deploying these workers to your own Cloudflare account, you must **update the endpoint URLs** inside the `code snippet/bitmap update form.php` file to point to your new worker URLs.

## Architecture

Our hybrid architecture is designed for scalability, performance, and ease of use.

  - **Frontend (Rendering Layer)**

      - **Technology**: Three.js
      - **Implementation**: The core `wp-bitmap-gallery.php` WordPress plugin injects the Three.js canvas and handles all frontend logic for the interactive map.

  - **Backend (Data & Logic Layer)**

      - **Technology**: WordPress
      - **Role**: Serves as the central hub for data caching, administrative UIs, and the primary API for the gallery.
      - **Database Tables**:
          - `wp_bitmap_configs`: Stores the final user-submitted configurations (image URL, IFrame URL, operator wallet address, etc.).
          - `wp_bitmap_inscriptions`: A lookup table to map Bitmap numbers to their on-chain inscription IDs.

  - **API Proxy Layer**

      - **Technology**: Cloudflare Workers
      - **Role**: High-performance, scalable proxies that handle requests to third-party services. This reduces the load on the main server and improves response times.
      - **Workers**:
        1.  **Hiro API Worker**: Fetches on-chain Bitmap ownership data.
        2.  **Magic Eden API Worker**: Verifies wallet ownership by checking the user's bio.
