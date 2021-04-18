FROM squidfunk/mkdocs-material

# Install custom plugins
RUN apk add --no-cache git
RUN pip install \
        Pygments \
        mkdocs-git-revision-date-plugin \
        mkdocs-localsearch \
        mkdocs-exclude \
        mkdocs-simple-hooks \
        mkdocs-macros-plugin
