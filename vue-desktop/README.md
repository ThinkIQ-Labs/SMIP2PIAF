# Running the Aveva Import / Export Tool from the Desktop

This is a Vue+Electron App based on the process described here: [Building an app with Electron and Vue](https://blog.logrocket.com/building-app-electron-vue/).

We can use almost all of the code we've written for the SM App. Only thing to add is code to pull an access token to a SMIP instance using an authenticator.

The below steps will be needed to compile this project locally on your prefered platform.

## Project setup

To install 3rd party libraries:
```
npm install
```

### Compiles and hot-reloads for development

To run the project in a browser or via electron:
```
npm run serve
npm run electron:serve
```

### Compiles and minifies for production

To build the project for a web server or to generate electron executable and installer.
```
npm run build
npm run electron:build
```


