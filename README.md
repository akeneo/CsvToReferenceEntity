# CsvToRefenceEntity
> Migrate data from [CustomEntityBundle](https://github.com/akeneo-labs/CustomEntityBundle), or initialize data from an ERP/MDM, to Akeneo Reference Entities records (_available since 3.0_), using CSV file import.

# Installation

```bash
git clone git@github.com:akeneo/CsvToRefenceEntity.git csv_to_reference_entity
cd csv_to_reference_entity
composer install
```

# Setup
Note: to setup this tool, you'll need a valid **API Client ID** and its **API Client secret** from your Akeneo PIM instance. Read the dedicated documentation to proceed: https://api.akeneo.com/getting-started-admin.html

Back in tool, you need to copy the [.env](https://symfony.com/doc/current/components/dotenv.html) file:
```bash
cp .env .env.local
```

Then open `.env.local` to define the needed configuration vars:
```
AKENEO_API_BASE_URI=http://your-akeneo-pim-instance.com
AKENEO_API_CLIENT_ID=123456789abcdefghijklmnopqrstuvwxyz
AKENEO_API_CLIENT_SECRET=123456789abcdefghijklmnopqrstuvwxyz
AKENEO_API_USERNAME=admin
AKENEO_API_PASSWORD=admin
```

# How to Use

## 1) Create Reference Entities in your PIM instance
In your PIM instance, you will need to create your structure for your records. In short, you'll need to [create your reference entity](https://help.akeneo.com/pim/v3/articles/manage-reference-entities.html#create-a-reference-entity) first, to define their attributes, if they have a value per channel/locale, etc.

## 2) Generate your .csv file
The only **required field** is the `code`. Regarding attributes, it depends on whether they have a value per channel/locale (_we use the same structure as for products_):

- For attribute without value per channel/locale:
    - `<attribute_code>`, eg. `description`
- For attribute with value per channel:
    - `<attribute_code>-<channel_code>`, eg. `description-ecommerce`
- For attribute with value per locale
    - `<attribute_code>-<locale_code>`, eg. `description-en_US`
- For attribute with value per channel and per locale:
    - `<attribute_code>-<locale_code>-<channel_code>`, eg. `description-en_US-mobile` (_locale first_)

So, let's imagine this structure for the `brand` reference entity:
- A code
- A description with one value per locale
- Some tags (an attribute with multiple options)

This would be a valid file:
```csv
code;description-en_US;description-fr_FR;tags;main_image
ikea;A famous scandinavian brand;Une célèbre marque scandinave;family,nordic;relative/path/to/image1.jpg
made.com;A famous english brand;Une célèbre marque anglaise;design,online;relative/path/to/image2.jpg
```

There should also be two images living next to this file in the directory `relative/path/to`.

## 3) Import your file

Once you have your .csv file, you can import it with this syntax:
```bash
php bin/console app:import <csv_file_path> <reference_entity_code>
``` 

So if you want to import your records in the .csv file located in `/tmp/file.csv` for your `brand` reference entity:
```bash
php bin/console app:import /tmp/file.csv brand
```
