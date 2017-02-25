# DocCounter
A collection of simple tools for analysing .PDF, .DOCX, .DOC and .TXT files.

I was struggling to find a comprehensive set of tools to do simple word, line and page counts for a variety of commonly used document formats. A lot of the conversion scripts and tools were unsuitable, somehow lacking, or poorly documented. So after a bit of head-scratching here is a simple working set of tools assembled into a workmanlike class.

I hope the tool is useful to people.

## Usage
```php
include "class.doccounter.php";

$doc = new DocCounter();
$doc->setFile("file.ext");

print_r($doc->getInfo());
echo ($doc->getInfo()->wordCount);
... do your thing
```
## Methods
No constructor variables are required to initiate the class.

### setFile( [filepath] )
Does what it says on the tin. The class uses the current working directory so will require folder references depending on your installation.

### getInfo()
Returns an object with the following properties:

```
->format = doc, docx - whatever the format of the given file
->wordCount = int/"unsupported file format"
->lineCount = int/"unsupported..."
->pageCount = int/"unsupported..."
```
And that's it for now.

## Caveat
Expect bugs and compatibility issues. Please submit bug reports if you find anything wonky. Header/Footer text is included in the total wordCount so be careful to not confuse this with the output in Word etc.

## Wish List
This project is by no means complete. More formats and wider compatibility testing would be useful.

The class can be easily modified to extract the plaintext and act as an all-in-one conversion library but this was overkill for this release.

Quick list of missing formats:

* RTF (without shell access, all solutions found so far leave metadata/header info in text output).
* ODT.
* The millions of others I've forgotten.

## Version History

* 1.0.2 - Features - all filepaths now relative to cwd, new word count method.
* 1.0.1 - Bugfix - Removed unnecessary zip->close() to remove warning.
* 1.0.0 - Initial release.
