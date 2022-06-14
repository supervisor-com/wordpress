zip:
	zip -pr supervisorcom.zip . --exclude ".git/*" --exclude "Makefile"

clean:
	git clean -df

release:
	cp readme.txt supervisor-com/trunk
	cp supervisor-com.php supervisor-com/trunk
	mkdir -p supervisor-com/trunk/views
	cp views/index.php supervisor-com/trunk/views
	cd supervisor-com; svn ci -m "$(version)" --username supervisorcom
	cd supervisor-com; svn cp trunk "supervisor-com/tags/$(version)"
	cd supervisor-com; svn ci -m "tag $(version)" --username supervisorcom