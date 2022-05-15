FROM squidfunk/mkdocs-material

# Install custom plugins
RUN apk add --no-cache git
RUN git config --global --add safe.directory /docs
RUN pip install \
        Pygments \
        mkdocs-git-revision-date-plugin \
        mkdocs-localsearch \
        mkdocs-exclude \
        mkdocs-simple-hooks \
        mkdocs-macros-plugin
