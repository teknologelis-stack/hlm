<?php
if (class_exists('ZipArchive')) {
    echo "✅ ZipArchive AVAILABLE";
} else {
    echo "❌ ZipArchive NOT AVAILABLE - php.ini'de extension=zip açılmalı";
}