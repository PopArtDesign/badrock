BUILD_DIR:="$(shell pwd)/var/build"

# Format: user@host:/path
PROD:=
PROD_BRANCH:=main

.PHONY: deploy@prod
deploy@prod:
ifndef PROD
	$(error PROD is undefined)
endif
	rm -rf $(BUILD_DIR)/prod
	mkdir -p $(BUILD_DIR)/prod
	WP_ENV=production tools/build.sh $(BUILD_DIR)/prod $(PROD_BRANCH)
	tools/deploy.sh $(BUILD_DIR)/prod $(PROD)
