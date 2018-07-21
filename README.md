# PdfText

## Requirements

* Omeka-S 1.1.1
* pdftotext, utility program part of poppler-utils.

## Goal

PdfText helps indexing PDF files for Omeka search. The text the PDF files you upload to your site will be made available for Omeka search, whatever your search engine is.

It does so by extracting text parts from the uploaded files, thanks to pdftotext.

The extracted text is then added to the bibo:content field.

## Usage

Install as usual. If the module doesn't find pdftotext by itself, click the *Configure* button on the modules admin page.

You can then type the path to pdftotext in the form /example/path/bin/ 

