zip:
	zip -pr supervisorcom.zip . --exclude ".git/*" --exclude "Makefile"

clean:
	git clean -df

# make release version=0.0.0
# TODO: sed might be macos specific
release:
	git push
	cp readme.txt supervisor-com/trunk
	cp supervisor-com.php supervisor-com/trunk
	mkdir -p supervisor-com/trunk/views
	cp views/index.php supervisor-com/trunk/views
	cd supervisor-com/trunk; find . -type f -exec sed -i '' -e 's/__SUPERVISOR_WORDPRESS_VERSION__/$(version)/g' {} \;
	cd supervisor-com/trunk; find . -type f -exec sed -i '' -e 's/https:\/\/my.superbot.club\/new/https:\/\/my.supervisor.com\/new/g' {} \;
	cd supervisor-com; svn ci -m "$(version)" --username supervisorcom
	cd supervisor-com; svn cp trunk "tags/$(version)"
	cd supervisor-com; svn ci -m "tag $(version)" --username supervisorcom
