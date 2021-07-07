# FNBr Webtool [Docker Container]
Webtool is an annotation and database management application developed by [FrameNet Brasil Project](http://www.ufjf.br/framenetbr-eng/), which can be accessed using any web browser,
without the need to install any additional software. Webtool handles multilingual framenets and constructicons.

Webtool app is implemented using [Framework Maestro](https://github.com/frameworkmaestro/maestro3/), a PHP7 framework developed at Federal University of Juiz de Fora (Brazil).

This repository contains a customized Maestro copy. Webtool app is localized at folder apps/webtool.

## GSoC 2020 Project Idea 2: New Frame-Based Image and Video Annotation Pipeline for the FNBr Webtool
This branch contains the code for this project selected under Google Summer of Code 2020.

Contributor- Prishita Ray  
Mentors- Tiago Timponi Torrent, Ely Matos, Marcelo Viridiano, Fred Belcavello

### Summary of Project
The main motivation for this project is to create a more automated and simplified video annotation pipeline using both image and textual data for the FrameNet webtool. The existing version relies on manual annotation which is a highly tedious task. In order to annotate multimodal corpora, individual frames or ideas depicted within the video need to be extracted using corresponding audio transcriptions and identified objects. This is important to obtain fine grained semantic representations of events and entities. Moreover, the video may be presented in multiple languages that need to be detected and translated to Portuguese. Also individual objects within a video need to be tracked and identified to generate corresponding textual frame elements that can speed up the annotation process.

The project will be implemented in the following three stages:

1. Pre-Processing Pipeline- This will segment an uploaded video based on timestamps of the spoken sentences, as well as generate and add subtitles that are translated into Portuguese to the video. The outcome of this stage will be the audio transcriptions obtained from the above process, that forms the textual data required for automatic semantic annotation, and the set of segmented video clips.

2. Semi-automatization of the annotation process- Individual objects present in a video segment will be identified and tracked across time, using feature tracking algorithms, to form the image data for automatic semantic annotation. An option for manual object selection will also be provided for better accuracy.

3. Data Compilation and Reporting Module- The textual data (audio transcriptions) and image data (tracked objects) will be mapped with the frame elements they invoke for the automatic semantic annotation. Captions will be generated for the representative images of tracked objects using a pretrained ML model. Using these captions and the audio transcriptions in a video segme, a model based on NLP and ML will be designed to train on a gold standard corpus, that will predict the frame elements for semantic annotation. This will also correlate the captions and words or phrases within the transcriptions that invoke the same frame elements for cross-annotation.

### Prerequisities


In order to run this container you'll need docker installed.

* [Windows](https://docs.docker.com/windows/started)
* [OS X](https://docs.docker.com/mac/started/)
* [Linux](https://docs.docker.com/linux/started/)

### Usage

Create a local installation for Webtool:

* Clone this repository at an accesible folder

```sh
$ git clone https://github.com/FrameNetBrasil/webtool.git
$ cd webtool
```
* If necessary, modify the docker-compose configuration file (.env)

* Start the container

```sh
$ docker-compose up
```

* Application configuration files will be created from dist

  * webtool/core/conf/conf.php
  * webtool/apps/webtool/conf/conf.php
  
* Access the app at http://localhost:8001 (with user = webtool password = test)

## Built With

* PHP 7.4
* MariaDb 10.4
* PhpMyAdmin 5.0.1
* Framework Maestro 3.0
* Vue 2.7

## Tutorials

See [this YouTube channel](https://www.youtube.com/playlist?list=PLbRWTx8_CBTniSlJdlhBqJNe7A-AjKizD) for tutorials on the main functions of the WebTool.

## Find Us

* [GitHub](https://github.com/FrameNetBrasil)
* [FrameNet Brasil](http://www.ufjf.br/framenetbr-eng/)

## Contributing
* Create a new branch with a meaningful name `git checkout -b branch_name`.<br />
* Develop your feature on Xcode IDE  and run it .<br />
* Add the files you changed `git add file_name`.<br />
* Commit your changes `git commit -m "Message briefly explaining the feature"`.<br />
* Keep one commit per feature. If you forgot to add changes, you can edit the previous commit `git commit --amend`.<br />
* Push to your repo `git push --set-upstream origin branch-name`.<br />
* Go into [the Github repo](https://github.com/FrameNetBrasil/webtool.git) and create a pull request explaining your changes.<br />

## License

GNU GPLv3 - See the [COPYING](COPYING) file for license rights and limitations.
