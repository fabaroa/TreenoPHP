export LD_LIBRARY_PATH=$LD_LIBRARY_PATH:/usr/local/Centera_SDK/lib/32/
all: dctput dctget install
#all: dctput dctget dctdel install dctdaemon
#all: dctput dctget retrieve store

dctdaemon: threadedpools.c 
	gcc -pthread threadedpools.c -o dctdaemon

dctput: docutronput.c 
	gcc -DPOSIX -I /usr/local/Centera_SDK/include -L/usr/local/Centera_SDK/lib/32 -lFPLibrary docutronput.c -o dctput

dctget: docutronget.c
	gcc -DPOSIX -I /usr/local/Centera_SDK/include -L/usr/local/Centera_SDK/lib/32 -lFPLibrary docutronget.c -o dctget

dctdel: docutrondel.c
	gcc -DPOSIX -I /usr/local/Centera_SDK/include -L/usr/local/Centera_SDK/lib/32 -lFPLibrary docutrondel.c -o dctdel

#retrieve: retrieve.c
#	gcc -DPOSIX -I /usr/local/Centera_SDK/include -L/usr/local/Centera_SDK/lib/32 -lFPLibrary retrieve.c -o retrieve
#	
#store: store.c
#	gcc -DPOSIX -I /usr/local/Centera_SDK/include -L/usr/local/Centera_SDK/lib/32 -lFPLibrary store.c -o store

clean:
	rm -rf dctput
	rm -rf dctget
	rm -rf dctdel
#	rm -rf retrieve
#	rm -rf store

install:
	mv -f dct* /usr/local/bin
