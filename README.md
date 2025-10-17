# FNBr Webtool 4.0 [Docker Container]
Webtool is an annotation and database management application developed by [FrameNet Brasil Project](http://www.ufjf.br/framenetbr-eng/), which can be accessed using any web browser,
without the need to install any additional software. Webtool handles multilingual framenets and constructicons.

Webtool app is coded using Laravel PHP framework.

### Prerequisities


In order to run this container you'll need docker installed.

* [Windows](https://docs.docker.com/windows/started)
* [OS X](https://docs.docker.com/mac/started/)
* [Linux](https://docs.docker.com/linux/started/)

### Usage

Create a local installation for Webtool:

* Clone this repository at an accesible folder

```sh
$ git clone https://github.com/FrameNetBrasil/webtool38.git
$ cd webtool38
```
* Copy file .env.sample to .env

* Create the database from dump

* Update database conf at .env file

* Build and start the container

```sh
$ docker compose build
$ docker compose up
```

* Access the app at http://localhost:8001 (with user = webtool password = test)

## Built With

* PHP 8.3
* Laravel Framework 10.3

## Tutorials

(work in progress)

## Find Us

* [GitHub](https://github.com/FrameNetBrasil)
* [FrameNet Brasil](http://www.ufjf.br/framenetbr-eng/)

## License

GNU GPLv3 - See the [COPYING](COPYING) file for license rights and limitations.

## How to cite

Torrent, T.T., Matos, E.E.d.S., Costa, A.D.d. et al. A flexible tool for a qualia-enriched FrameNet: the FrameNet Brasil WebTool. Lang Resources & Evaluation (2024). https://doi.org/10.1007/s10579-023-09714-8
