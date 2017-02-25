<?php

/* 
 * A collection of simple tools for analysing 
 * .PDF, .DOCX, .DOC and .TXT docs. 
 * 
 *  Copyright (C) 2016
 *    Joseph Blurton (http://github.com/foo/bar)
 *    And other contributors (see attrib below)
 *  
 *  Version 1.0.1
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * ATTRIBUTIONS
 *
 * PageCount_PDF and 
 * PageCount_DOCX by Whiteflash
 * http://stackoverflow.com/questions/5540886/extract-text-from-doc-and-docx/
 *
 * Paragraph tweak by JoshB
 * http://stackoverflow.com/questions/5607594/find-linebreaks-in-a-docx-file-using-php
 * 
 * read_word_doc by
 * Davinder Singh
 * http://stackoverflow.com/questions/7358637/reading-doc-file-in-php
 * 
 * Line Count method by K2xL
 * http://stackoverflow.com/questions/7955402/count-lines-in-a-posted-string
 *
 * RTFTOOLS by
 * Christian Vigh
 * https://github.com/christian-vigh-phpclasses/RtfTools
 *
 * PDF Parser by
 * Smalot GPL 3
 * https://github.com/smalot/pdfparser
 */

class DocCounter {
    
    // Class Variables   
    private $file;
    private $filetype;
    
    // Set file
    public function setFile($filename)
    {
        $this->file = $filename;
        $this->filetype = pathinfo($this->file, PATHINFO_EXTENSION);
    }
    
    // Get file
    public function getFile()
    {
        return $this->file;
    }
    
    // Get file information object
    public function getInfo()
    {
        // Function variables
        $ft = $this->filetype;
        
        // Let's construct our info response object
        $obj = new stdClass();
        $obj->format = $ft;
        $obj->wordCount = null;
        $obj->lineCount = null;
        $obj->pageCount = null;
        
        // Let's set our function calls based on filetype
        switch($ft)
        {
            case "doc":
                $doc = $this->read_doc_file();
                $obj->wordCount = str_word_count($doc);
                $obj->lineCount = $this->lineCount($doc);
                $obj->pageCount = $this->pageCount($doc);
                break;
            case "docx":
                $obj->wordCount = str_word_count($this->docx2text());
                $obj->lineCount = $this->lineCount($this->docx2text());
                $obj->pageCount = $this->PageCount_DOCX();
                break;
            case "pdf":
                $obj->wordCount = str_word_count($this->pdf2text());
                $obj->lineCount = $this->lineCount($this->pdf2text());
                $obj->pageCount = $this->PageCount_PDF();
                break;
            case "txt":
                $textContents = file_get_contents($this->file);
                $obj->wordCount = str_word_count($textContents);
                $obj->lineCount = $this->lineCount($textContents);
                $obj->pageCount = $this->pageCount($textContents);
                break;
            default:
                $obj->wordCount = "unsupported file format";
                $obj->lineCount = "unsupported file format";
                $obj->pageCount = "unsupported file format";
        }
        
        return $obj;
    }
    
    // Convert: Word.doc to Text String
    function read_doc_file() {
        $f = $this->file;
         if(file_exists($f))
        {
            if(($fh = fopen($f, 'r')) !== false ) 
            {
               $headers = fread($fh, 0xA00);

               // 1 = (ord(n)*1) ; Document has from 0 to 255 characters
               $n1 = ( ord($headers[0x21C]) - 1 );

               // 1 = ((ord(n)-8)*256) ; Document has from 256 to 63743 characters
               $n2 = ( ( ord($headers[0x21D]) - 8 ) * 256 );

               // 1 = ((ord(n)*256)*256) ; Document has from 63744 to 16775423 characters
               $n3 = ( ( ord($headers[0x21E]) * 256 ) * 256 );

               // 1 = (((ord(n)*256)*256)*256) ; Document has from 16775424 to 4294965504 characters
               $n4 = ( ( ( ord($headers[0x21F]) * 256 ) * 256 ) * 256 );

               // Total length of text in the document
               $textLength = ($n1 + $n2 + $n3 + $n4);

               $extracted_plaintext = fread($fh, $textLength);
                $extracted_plaintext = mb_convert_encoding($extracted_plaintext,'UTF-8');
               // simple print character stream without new lines
               //echo $extracted_plaintext;

               // if you want to see your paragraphs in a new line, do this
               return nl2br($extracted_plaintext);
               // need more spacing after each paragraph use another nl2br
            }
        }
    }
    // Convert: Word.docx to Text String
    function docx2text()
    {
        return $this->readZippedXML($this->file, "word/document.xml");
    }

    function readZippedXML($archiveFile, $dataFile)
    {
        // Create new ZIP archive
        $zip = new ZipArchive;

        // Open received archive file
        if (true === $zip->open($archiveFile)) {
            // If done, search for the data file in the archive
            if (($index = $zip->locateName($dataFile)) !== false) {
                // If found, read it to the string
                $data = $zip->getFromIndex($index);
                // Close archive file
                $zip->close();
                // Load XML from a string
                // Skip errors and warnings
                $xml = new DOMDocument();
                $xml->loadXML($data, LIBXML_NOENT | LIBXML_XINCLUDE | LIBXML_NOERROR | LIBXML_NOWARNING);
                
                $xmldata = $xml->saveXML();
                // Newline Replacement
                $xmldata = str_replace("</w:p>", "\r\n", $xmldata);
                // Return data without XML formatting tags
                return strip_tags($xmldata);
            }
            $zip->close();
        }

        // In case of failure return empty string
        return "";
    }
    
    // Convert: Word.doc to Text String
    function read_doc()
    {
        $fileHandle = fopen($this->file, "r");
        $line = @fread($fileHandle, filesize($this->file));   
        $lines = explode(chr(0x0D),$line);
        $outtext = "";
        foreach($lines as $thisline)
          {
            $pos = strpos($thisline, chr(0x00));
            if (($pos !== FALSE)||(strlen($thisline)==0))
              {
              } else {
                $outtext .= $thisline." ";
              }
          }
        $outtext = preg_replace("/[^a-zA-Z0-9\s\,\.\-\n\r\t@\/\_\(\)]/","",$outtext);
        return $outtext;
    }
    
    // Convert: Adobe.pdf to Text String
    function pdf2text()
    {
        //absolute path for file
        $path = getcwd();
        $f = $path."/".$this->file;
        if (file_exists($f)) {
            include('vendor/autoload.php');
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($f);
            $text = $pdf->getText();
            return $text;
        }
        
        return null;
    }
    
    // Page Count: DOCX using XML Metadata
    function PageCount_DOCX()
    {
        $pageCount = 0;

        $zip = new ZipArchive();

        if($zip->open($this->file) === true) {
            if(($index = $zip->locateName('docProps/app.xml')) !== false)  {
                $data = $zip->getFromIndex($index);
                $zip->close();
                $xml = new SimpleXMLElement($data);
                $pageCount = $xml->Pages;
            }
        }

        return intval($pageCount);
    }

    // Page Count: PDF using FPDF and FPDI 
    function PageCount_PDF()
    {
        //absolute path for file
        $path = getcwd();
        $f = $path."/".$this->file;
        $pageCount = 0;
        if (file_exists($f)) {
            require_once('lib/fpdf/fpdf.php');
            require_once('lib/fpdi/fpdi.php');
            $pdf = new FPDI();
            $pageCount = $pdf->setSourceFile($f);        // returns page count
        }
        return $pageCount;
    }
    
    // Page Count: General
    function pageCount($text)
    {
        require_once('lib/fpdf/fpdf.php');

        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Times','',12);
        $pdf->MultiCell(0,5,$text);
        //$pdf->Output();
        $filename="tmp.pdf";
        $pdf->Output($filename,'F');
        
        require_once('lib/fpdi/fpdi.php');
        $pdf = new FPDI();
        $pageCount = $pdf->setSourceFile($filename);
        
        unlink($filename);
        return $pageCount;
    }
    
    // Line Count: General
    function lineCount($text)
    {
        $lines_arr = preg_split('/\n|\r/',$text);
        $num_newlines = count($lines_arr); 
        return $num_newlines;
    }
}


?>