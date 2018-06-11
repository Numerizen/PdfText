PdfText (module for Omeka S)
=============================

Summary
-----------

Omeka's module that add text contents metadata to an item from a pdf media.

Installation
------------
- This plugin needs pdftotext command-line tool on your server

```
    sudo apt-get install poppler-utils
```

- you can install the module via github

```
    cd omeka-s/modules  
    git clone git@github.com:bubdxm/Omeka-S-module-PdfText.git "PdfText"
```

- Install it from the admin → Modules → PdfText -> install

Using the PdfText module
---------------------------

- Create an item
- Add PDF file(s) to this item
- Save Item
- If you go to the item's view you should see bibo:content filled. 

Troubleshooting
---------------

See online [PdfText issues](https://github.com/bubdxm/Omeka-S-module-PdfText/issues).


License
-------

This module is published under [GNU/GPL].

This program is free software; you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation; either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT
ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
details.

You should have received a copy of the GNU General Public License along with
this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.


Contact
-------

* Syvain Machefert, Université Bordeaux 3 (see [symac](https://github.com/symac))




