# SMIP2PIAF

This project includes a collection of browser scripts that allow the import of Asset Framework (AF) Element Types from AVEVA's PI System Explorer (PI AF) into the SMIP. Reverse functionality, i.e. exporting SMIP types so they can be imported into PI AF is not yet supported, but planned. Below is a dual screenshot of how Element Types can be imported into the SMIP. Notice, how the lower type is based on the other:

![Screenshot](./images/ImportTypesScreenshot%20w%20BaseType.png)
<p align = "center"><b>Fig.1 - Screenshot of UI to import Element Types /w Inheritance</b></p>

To load this set of tools into a SMIP, simply import the library saved in this repo's release section.

![Screenshot](./images/ImportLibrary.png)
<p align = "center"><b>Fig.2 - Import Library to Load Smart App into SMIP</b></p>

## Modeling Techniques Supported in PI AF 

In PI AF we have only 2 types of objects that models are based upon: elements and attributes, both of which can be nested. Elements can be based on types, Element Templates, which are stored in a model library’s “Element Templates” section. An Element Template can be based of a single other Element Template. This allows inheritance and chaining of dependencies.

Even though element instances can be nested, element templates can not be nested, i.e. it is not possible to create composite types. Within an element template, however, attribute templates can be nested. For the purpose of importing AF element templates into the SMIP, we can either disregard nested attributes, or flatten them. Flattening nested attributes would preserve them for usage in the SMIP, but ultimately change the structure of the type, and possibly create issues with unique naming of sibling attributes.

## Element Template Meta-Data

- Name - is captured.
- Description - is captured.
- Base Template - is captured (the base template needs to be imported first and be present in the SMIP).
- Type - only Elements are considered.
- Categories - omitted. This does not exist in the SMIP.
- Default Attribute - omitted. This does not exist in the SMIP.
- Naming Pattern - omitted. This does not exist in the SMIP.

## Attribute Template Meta-Data

- Name - is captured.
- Description - is captured.
- Properties - omitted. The are AF specific attributes.
- Categories - omitted. This does not exist in the SMIP.
- Default UoM - is captured by name. The is no mapping of UoM taxonomies.
- Default Value - is captured.
- Display Digits - omitted. This does not exist in the SMIP.
- Data Reference - only <none> (for static attributes) and PI Point (for tags) are captured.

### Value Type Mapping

Only basic types are captured. We omit array types, enums, and AF object references. 

AF types are mapped to SMIP types as follows:

- Boolean - bool
- Byte, Int16, Int32, Int64 - integer
- Single, Double - float
- String - string
- GUID - string
- DateTime - DateTime

## Approach
  
We decided to use browser scripts for this work for the following reasons:
  
### In and Out of PI AF using XML
  
PI AF has had robust import and export methods utilizing XML files for decades. One can simply righ-click an Element Template and export to file. Or export all Element Templates. Or export a whole model, or portions of a model, including the referenced types. There are many ways to export from PI AF and the user should be aware of what data will be contained in an XML file. This repository includes a number of different sample XML files to illustrate this.
  
We chose to parse XML using javascript in the browser over PHP, Python, or .NET Core in a server setting simply because HTML is XML and javascript does a superb job handling XML. We are not concerened about loading too large a model into the browser: modern browsers can handle large files, and simple XML exports of Element Template libraries from PI AF should rarely exceed 10MB.
  
### Interoperability with SMIP using the GraphQL API
  
The SMIP's script engine allows the creation of browser scripts uing modern web development techniques. We use a standard set of patterns that allow us to access the SMIP model using the GraphQL API and bind data to the UI using Vue.js. 
It is important to note that browser scripts act "on behalf of the user", that means that a SMIP user has to bring sufficient GraphQL priviledges to modify the model, i.e. create equipment types.
