# Customer APIs Documentation

This document contains the details for the requested mobile APIs, including HTTP methods, parameters, request body (if any), headers, and expected responses. 

---

## 1. Categories
**Endpoint:** `/customer/home/categories` *(Note: The prefix `/home` is defined in the routes)*  
**Method:** `GET`  
**Description:** Retrieves all active categories and their sub-categories, along with the branch open/close status.

### Headers
- `Accept`: `application/json`

### Query Parameters
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `app_type`| string | No | `app` | The application type (e.g., `app`, `web`, `all`). |
| `locale` | string | No | App default | Language code for translation (e.g., `en`, `ar`). |
| `branch_id`| integer | No | | ID of the branch (used to check if it's open/closed). |
| `address_id`| integer | No | | ID of the address (used to infer `branch_id` from the zone). |

### Request Body
None.

### Response (200 OK)
```json
{
    "categories": [
        {
            "id": 1,
            "name": "Category Name",
            "image_link": "http://...",
            "banner_link": "http://...",
            "sub_categories": [
                {
                    "id": 2,
                    "name": "Sub Category Name",
                    "image_link": "http://...",
                    "banner_link": "http://..."
                }
            ]
        }
    ],
    "open": true,
    "close_message": ""
}
```
*(If `open` is false, `close_message` will contain the Arabic schedule message).*

---

## 2. Products in Category (Mobile)
**Endpoint:** `/customer/home/products_in_category_mobile/{id}`  
**Method:** `GET`  
**Description:** Retrieves all products belonging to a specific category ID for mobile view.

### Headers
- `Accept`: `application/json`

### Path Parameters
- `{id}` (integer, required): The ID of the category.

### Query Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `locale` | string | No | Language code for translation (e.g., `en`, `ar`). |
| `branch_id`| integer | No | ID of the branch for pricing/tax specific to takeaway. |
| `address_id`| integer | No | ID of the address for pricing/tax specific to delivery. |
| `user_id` | integer | No | ID of the user to check if products are in their favorites. |

### Request Body
None.

### Response (200 OK)
```json
{
    "products": [
        {
            "id": 1,
            "name": "Product Name",
            "price": 100,
            "in_stock": true,
            "favourite": false,
            "favourites": false,
            "count": 5,
            "tax": { ... },
            "variations": [ ... ],
            ...
        }
    ]
}
```

---

## 3. Discount Products (Mobile)
**Endpoint:** `/customer/home/discount_products_mobile`  
**Method:** `GET`  
**Description:** Retrieves a list of all products currently on discount.

### Headers
- `Accept`: `application/json`

### Query Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `locale` | string | No | Language code for translation (e.g., `en`, `ar`). |
| `branch_id`| integer | No | ID of the branch. |
| `address_id`| integer | No | ID of the address. |

### Request Body
None.

### Response (200 OK)
```json
{
    "discounts": [
        {
            "id": 10,
            "name": "Discounted Product Name",
            "price": 50,
            "discount": {
                "id": 1,
                "amount": 10,
                "type": "precentage",
                ...
            },
            ...
        }
    ]
}
```

---

## 4. Restaurant Settings (Mobile)
**Endpoint:** `/customer/home/retuarant_settings_mobile`  
**Method:** `GET`  
**Description:** Retrieves general restaurant timing settings and current tax configurations.

### Headers
- `Accept`: `application/json`

### Query Parameters
None.

### Request Body
None.

### Response (200 OK)
```json
{
    "resturant_time": {
        "saturday": { "from": "09:00", "to": "23:00" },
        ...
    },
    "tax": {
        "status": 1,
        "amount": 15,
        "type": "precentage",
        "setting": "excluded"
    }
}
```

---

## 5. Call Category
**Endpoint:** `/customer/home/call_category/{id}`  
**Method:** `GET`  
**Description:** Records a category click metric for the authenticated user.

### Headers
- `Accept`: `application/json`
- `Authorization`: `Bearer {token}` *(Required, uses `auth:sanctum` middleware)*

### Path Parameters
- `{id}` (integer, required): The ID of the category being clicked/viewed.

### Query Parameters
None.

### Request Body
None.

### Response (200 OK)
```json
{
    "message": "Category click recorded successfully."
}
```
