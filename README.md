# International Barcode of Life project (iBOL) to Darwin Core

## Fetch data


### Gotchas

#### File encoding
The file iBOL_phase_0.50_COI.tsv is not UTF-8 encoded, so we need to convert it. For example:

```
iconv -f iso-8859-1 -t utf-8 iBOL_phase_0.50_COI.tsv > iBOL_phase_0.50_COI.tsv.new
rm iBOL_phase_0.50_COI.tsv
mv iBOL_phase_0.50_COI.tsv.new iBOL_phase_0.50_COI.tsv
```

#### Image URLs have awkward characters
BOLD web site has URLs for images that contain ‘#’ and ‘+’ symbols. These need to be URL encoded.

## Load into MySQL

Not strictly necessary, but helps when investigating the data and generating data for extensions.

LOAD DATA LOCAL INFILE ‘/Users/rpage/iBOL_phase_0.50_COI.tsv’ REPLACE INTO TABLE barcodes IGNORE 1 LINES;

### Generate image data

```
SELECT barcodes.processid, barcodes_api.processid, `barcodes_api`.image_urls, `barcodes_api`.copyright_licenses 
FROM barcodes INNER JOIN barcodes_api ON barcodes.processid = CONCAT(barcodes_api.processid, “.COI-5P”)
WHERE `barcodes_api`.image_urls <> “”;
```

### Generate identifications




## Publishing

## Step 1 Create dataset on GBIF

Create a dataset on GBIF using registry API. The **publishingOrganizationKey** is the publisher UUID that you see in the link to the publisher page: http://www.gbif.org/publisher/92f51af1-e917-49bc-a8ed-014ed3a77bec. You also need a **installationKey** provided by GBIF, and you also need to authenticate the call using your GBIF portal username and password.

http://api.gbif.org/v1/dataset

POST

```javascript
{
	“publishingOrganizationKey”:”92f51af1-e917-49bc-a8ed-014ed3a77bec”,
	“installationKey”:”645445d5-177a-475d-b2fe-69d3f6c89498”,
	“title”:”International Barcode of Life project (iBOL)”,
	“type”:”OCCURRENCE” 
}
```
RESPONSE

```javascript
“040c5662-da76-4782-a48e-cdea1892d14c”
```

We now have a UUID (040c5662-da76-4782-a48e-cdea1892d14c) for the dataset, which lives here: http://www.gbif.org/dataset/040c5662-da76-4782-a48e-cdea1892d14c

## Step 2 Create and validate Darwin Core archive

Now we need to create the Darwin Core archive. 
I then generated a meta.xml file, and finally the Darwin Core Archive (DwC-A) (which is simply a zip file):

```
zip ibol-dwca.zip eml.xml meta.xml occurrences.tsv media.txt
```

Next we need to check that the DwC-A file is valid using the [Darwin Core Archive Validator](http://tools.gbif.org/dwca-validator/).

## Step 3 Create endpoint

Now we need to tell GBIF where to get the data. In this example, the Darwin Core Archive file is hosted by Github (make sure you link to the raw file).

http://api.gbif.org/v1/dataset/040c5662-da76-4782-a48e-cdea1892d14c/endpoint

POST
```javascript
{
  “type”:”DWC_ARCHIVE”,
  “url”:”https://dl.dropboxusercontent.com/u/639486/ibol-dwca.zip”
}
```

RESPONSE 

HTTP 201 Created

```javascript
131032
```

## Step 4 Wait

Wait for GBIF to index the data… this happens in near real time.

## Step 5 Edit and update

If the data needs to be tweaked, edit the data, put the new archive where it can be harvested (i.e., the endpoint) and ask GBIF to crawl it again.

```
http://api.gbif.org/v1/dataset/040c5662-da76-4782-a48e-cdea1892d14c/crawl

POST

Response

HTTP/1.1 201 Created





