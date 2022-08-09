# PayWithNEAR Docs Builder

Twig static builder.

## Requirements

* Docker

## Usage

```bash
$ docker run -v $(pwd)/dist/:/app/dist/ \
  -v $(pwd)/templates/:/app/templates \
  -v $(pwd)/assets/:/app/assets \
  --user 1000:000 \
  --env BUILD_DIR=dist \
  ghcr.io/pay-with-near/docs-builder:latest
```

As a result of the command execution, the assembled html files will appear in the `dist` folder.

Now you can start the web server in the `dist` folder to get a working website with documentation.

## Example

See [PayWithNEAR Docs repo](https://github.com/Pay-With-NEAR/docs)
