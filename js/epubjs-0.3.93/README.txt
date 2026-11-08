epub.js 0.3.93 (BSD-2-Clause, https://github.com/futurepress/epub.js) and JSZip 3.10.1 (MIT, https://stuk.github.io/jszip/), each one static file, fetched once from
cdn.jsdelivr.net/npm/epubjs@0.3.93/dist/epub.min.js and cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js (no npm in the stack, nothing is built here).
reader.html and reader.js are the site's own page around them: an in-page epub reader that is opened in an iframe, the same way pdf.js's viewer.html is
(fisheyemedia/view_extra_file.php): reader.html?file=<url of the epub>&search=<term> opens the book, finds the term in the whole book, lists the hits and
highlights the first. sha256: epub.min.js 06eae15745107b4aa508c95538275251f69bfb9f1175621fc458d9f42ed082d4, jszip.min.js acc7e41455a80765b5fd9c7ee1b8078a6d160bbbca455aeae854de65c947d59e.
