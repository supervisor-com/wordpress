zip:
	zip -pr supervisorcom.zip . --exclude ".git/*" --exclude "Makefile"

clean:
	git clean -df
