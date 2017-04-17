How to modify the fre OCR engine:

--install fre-6.0-98
--link or move necessary files as directed in ReadMe.txt
--go into the samples/PlainText path
--in the PlainText.cpp file:
	change char* pwszImagePath = argv[1]
	change char* pwszTextPath = argv[2]

	this will change from loading default files into loading the first argument and writing text to the second argument as filenames

	change the second argument in the SaveToTextFile method to false.  This will eliminated the unwanted unicode characters in translation.

--make the PlainText again
--run the program as follows:
	./PlainText image.tiff text.txt

to do an OCR translation
