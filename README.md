# PHP On Cloud Blog

This is a blog about PHP.

The blog itself is a project of ultra light CMS that doesn't use a database and is based on MarkDown files.

So far this is the very initial phase.

## Objective
The idea is not only to create a blog, but to create a CMS framework that be ligthing fast, highly efficient and scalable.

This project aims to prove that not every problem needs to be solved in the same way, and that there are many other
data structure alternatives, including the already efficient OS's file system.

Also, file formats like Yaml with FrontMatter allow you to store additional meta information.

At some point this project will branch into 2 projects:

- A installable framework to be used by any site that requires a CMS.
- An administration application that allows to manage the content and configuration of multiple instances of the CMS.

## Current features
- Serve pages by parsing the content written in MarkDown with Front Matter

## Basic TODO list
This is what I need for the blog to be usable:

- Template system
- Compiling front end assets and include them in the templates
- Allowing sub blocks of information
- Handle assets in a CDN

## Advanced TODO list
What I want to achieve for the project to be more mature and be able to be used in more serious situations.

- (Site) Basic block system for additional information that can be included in different pages
- (Admin) Register CMS sites
- (Admin) Navigate and view content markdown files and their information
- (Admin) Basic editing of the content
- (Admin) Image editing for the content
- (Admin) Trigger assets compilation in the site
- (Admin) Deploy sites/site changes
- (Search) Create a Full text search engine that can be efficient without a database and can index incrementally
- (Site) Use the search engine
- (Site) Handle internationalization
- (Admin) Handle translations of content
- (Admin) Manage blocks
- (Site) More advance block system