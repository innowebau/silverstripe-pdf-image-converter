# Silverstripe PDF to image converter

[![Version](http://img.shields.io/packagist/v/innoweb/silverstripe-fail2ban.svg?style=flat-square)](https://packagist.org/packages/innoweb/silverstripe-fail2ban)
[![License](http://img.shields.io/packagist/l/innoweb/silverstripe-fail2ban.svg?style=flat-square)](license.md)

## Overview

This module converts the first page of a PDF file to an image. 

It uses the high level conversion API introduced in Silverstripe 5.3, see the [image documentation](https://docs.silverstripe.org/en/5/developer_guides/files/file_manipulation/#converting-between-other-formats).

## Requirements

* Silverstripe Assets 2.3 (Silverstripe 5.3)

## Installation

Install the module using composer:

```bash
composer require innoweb/silverstripe-pdf-image-converter
```

Then run dev/build.

### Usage

To display a thumbnail of a PDF file in a template you can convertthe PDF to an image in the template:

```
<img src="$PDFDocument.Convert('jpg').Pad(100,150).URL" height="150" width="100" alt="">
```

You can use the `Quality` method to set a specific image quality for different formats:

```
	<picture>
		<source type="image/webp" srcset="$PDFDocument.Convert('webp').Quality(80).Pad(100,150).URL">
		<img src="$PDFDocument.Convert('jpg').Pad(100,150).URL" height="150" width="100" alt="">
	</picture>
```

## License

BSD 3-Clause License, see [License](license.md)
