## List of deliverables vs Schedule

### Part 1: Data Import/Export Pipeline

While all the user have to do is import a video file, what happens under the hood is mildly more complex:

#### The workflow

_*@PRISHIta123 Take some time to expand/make changes to this workflow*_

1. User import a video file (via direct upload or importing from a url);
2. File/URL is validated (checking that the URL points to a video file);
3. Check the database for duplicates (inform user/drop duplicate file);
4. Check video width/height to make sure they meet a minimum constraint;
5. Non-duplicate video is uploaded/scraped and stored;
6. Audio track extracted and converted (FLAC / 44,100 Hz / Mono) and stored;
7. Video converted (MP4/H.264) and stored;
8. Video thumbnails generated and stored;
9. Audio file uploaded to Cloud Storage/Speech API;
10. Transcription returns from Cloud Speech API and stored;
11. Subtitles extracted from video with Python-tesseract and stored;
12. Transcription and subtitles synced/aligned and merged into a single text file;
13. User review the video and sentences (side by side) for validation;
14. Transcriptions and subtitles are organized into sentences and stored according to the Webtool standard;
15. Reviewed file is uploaded to the FrameNet Webtool.

#### Pipeline Architecture



#### Vídeo Import/Convert

#### Importing from URL

After a video URL has been entered by the user, it must be sent to the pipeline which processes it through several components that are executed sequentially.

Each pipeline component is a Python class that implements a simple method. They receive an item and perform an action over it (also deciding if the item should continue through the pipeline or be dropped and no longer processed).

Typical actions include:

* validating data (checking that the URL points to a video file)
* checking for duplicates (and dropping them)
* storing the video in the database

The pipeline needs a few extra functions for processing video files:

* check video width/height to make sure they meet a minimum constraint (720x480/WideSD)
* convert all downloaded videos to a common format (MP4) and codec (H.264)
* thumbnail generation
* keep an internal queue of those media URLs which are currently being scheduled for download, and connect those responses that arrive containing the same media to that queue (this avoids downloading the same media more than once)


#### Filtering out small videos

When using the video pipeline, users might try to upload videos which are too small. The tool should restrict videos which do not have the minimum allowed size in the VIDEO_MIN_HEIGHT and VIDEO_MIN_WIDTH settings.

For example:

    VIDEO_MIN_HEIGHT = 480
    VIDEO_MIN_WIDTH = 720

It should be possible to set just one size constraint or both. When setting both of them, only videos that satisfy both minimum sizes will be saved. For the above example, videos of sizes (640 x 480) or (800 x 460) will all be dropped because at least one dimension is shorter than the constraint.

By default, there are no size constraints, so all videos are processed.


#### File storage system

The video files should be stored using a SHA1 hash of their URLs for the file names.

For example, the following video URL:

    https://youtu.be/j1IfooX0_Hw

Whose SHA1 hash is:

    abcf9fa8e0d025ed1a35e425122a4de86980334b

Will be stored in the following file:

    <VIDEOS_STORE>/full/abcf9fa8e0d025ed1a35e425122a4de86980334b.mp4

Where:

`<VIDEOS_STORE>` is the directory defined in `VIDEO_STORE` setting for the video pipeline.

`/full` is a sub-directory to separate full videos from thumbnails (if used) and video segments.

The existing database for videos will be managed using the MySQL workbench, using the DocumentMM table, that contains the path for the videos, and the SentenceMM table, that holds the sentences and associated timestamps in the video. 


#### Thumbnail generation for videos

The video pipeline should automatically create thumbnails of the downloaded videos.

In order to use this feature, users will set `IMAGES_THUMBS` to a dictionary where the keys are the thumbnail names and the values are their dimensions.

For example:

    IMAGES_THUMBS = {
        'small': (240, 180),
        'big': (320, 240),
    }

When you use this feature, the video pipeline will create thumbnails of the each specified size with this format:

    <IMAGES_STORE>/thumbs/<size_name>/<video_id>.jpg

Where:

`<size_name>` is the one specified in the `IMAGES_THUMBS` dictionary keys (small/big)
`<video_id>` is the SHA1 hash of the image url

Example of image files stored using small and big thumbnail names:

    <IMAGES_STORE>/thumbs/small/abcf9fa8e0d025ed1a35e425122a4de86980334b.jpg
    <IMAGES_STORE>/thumbs/big/abcf9fa8e0d025ed1a35e425122a4de86980334b.jpg
    
The thumbnail images will be stored in the JPEG format.

    
#### Audio Transcription

Transcriptions should have timestamps that identify the exact point in an audio/video where the given text was spoken.

    For example: 00:00:08,204 –> 00:00:10,143 - Good morning.

Timestamps will use the format `[HH:MM:SSS]` where `HH`, `MM`, `SSS` are hours, minutes and milliseconds from the beginning and ending of each sentence in the audio track.

Once finished processing, the Speech-to-Text API will return the transcription to be stored in the following file:

    <TEXT_STORE>/transcripts/<video_id>.txt

###### /* [Via IBM Watson Speech-to-text?](https://www.ibm.com/in-en/cloud/watson-speech-to-text)

The advantages of using the Google Cloud Speech-to-text API is its affordability and its ability to detect multiple languages present in the video. 

![question](https://img.shields.io/static/v1?label=&message=question&color=green) @PRISHIta123 I suggested using IBM Watson speech-to-text because of the 500MB/month we get with the [Lite (free) account](https://cloud.ibm.com/catalog/services/speech-to-text). Are you considering the fact that Google only offers 60MB/month? 

#### Subtitle Extraction

*Python-tesseract is an optical character recognition (OCR) tool for python. That is, it will recognize and “read” the text embedded in images.*

*Python-tesseract is a wrapper for Google’s Tesseract-OCR Engine. It is also useful as a stand-alone invocation script to tesseract, as it can read all image types supported by the Pillow and Leptonica imaging libraries, including jpeg, png, gif, bmp, tiff, and others.*

[https://pypi.org/project/pytesseract/](https://pypi.org/project/pytesseract/)

Once finished processing, the tool will return the subtitles to be stored in the following file:

    <TEXT_STORE>/subtitles/<video_id>.srt
    
In case visual subtitles are not present in a video, only the Speech-to-Text API will be used to generate the audio transcriptions, in Portuguese.


#### Transcription-Subtitle Alignment

The tool should have an interface to compare video and text files (audio transcripts and extracted subtitles) side by side, allowing users to review, search and make the necessary edits and corrections of any errors.

This "validation interface" should have a simple UI, consisting mainly of a video viewer with playback controls (such as "play" and "stop") where users will be able to verify the temporally aligning of video and transcription (similar to YouTube's auto-generated subtitles interface).

After reviewing, the tool should merge both audio transcripts and extracted subtitles (aligned and synced according to their timestamps) into one single file, then output to a combined folder.

    <TEXT_STORE>/combined/<video_id>.json
    
The video will not be segmented, however a provision must be made to locate the start and end timestamps of a sentence that is to be annotated. 


#### Item Exporter

Once we have all of the above, we want to export those items to the webtool. For this purpose, the pipeline should provide different output formats, such as XML, CSV or JSON. The JSON format will be used for the second part of the project, i.e. object extraction.


#### Timeline

The tasks specified in the workflow will be completed as follows:  
*June 1st-June 7th*: Tasks 1 to 5  
*June 8th-June 15th*: Tasks 6, 9 and 10 to generate the audio transcriptions  
*June 15th-June 22nd*: Tasks 7, 8 and 11 to generate video subtitles  
*June 22nd-July 1st*: Tasks 12 to 15, for validation, and integration into the webtool   
*July 2nd-July 3rd*:Phase 1 evaluation



### Part 2: Semi-Automatization of the Annotation Process

The objects within a video need to be detected and tracked over time to form the image data that will be used for automated annotation/


#### The workflow

1. The preprocessed video from the previous pipeline is imported into the webtool from the server.
2. To annotate a sentence, the start and end timestamps of that sentence are chosen.
3. Run the video with each frame having a time gap of 1 second, using VATIC.js.
4. Objects in a frame will be detected automatically using YOLO (You Look Only Once), which will also create bounding boxes around them. In case COCO does not perform well, a new model will be trained using the DarkNet dataset, and in addition, changes to the code for obtaining the actual pixel coordinates will also be made.

![question](https://img.shields.io/static/v1?label=&message=question&color=green) @PRISHIta123 you said on Slack that you would be training a new model if COCO does not perform well. Is this training included in step 4 of the workflow?

5. The coordinates of the pixels that serve as corners to a detected object's bounding box will be saved in a list.
6. For the following 5 frames, the KLT (Kanade-Lucas-Tomasi) feature tracking algorithm will track these objects by interpolating the current coordinates of the detected objects.
7. The 5 frame constraint is kept for each detected object that is to be tracked, to ensure that it is present in the video for at least five seconds, otherwise tracking it is not useful and won't help in annotation. 
8. If the image for an object is generated after the previous step is performed, a minimum size constraint and image quality resolution will have to be met to save the image.
9. The generated images will be stored in the OBJECTS_STORE folder.
10. Using a windowing technique, the same object detection and tracking process from steps 4 to 9 will be followed for the duration of the video. Every new object that is tracked successfully will be added to the list storing the coordinates.
11. The generated images will be shown to the user for validation. An option for manual creation of bounding boxes will be provided if the user is not satisfied. 
12. Identified objects in the video will be stored in the ObjectMM table of the webtool database. 


#### Running videos with VATIC.js

The preprocessed video imported into the webtool will be run in frames of fixed duration (i.e. 1 second) by adjusting the speed multiplier option in VATIC. This step is important to effectively detect objects in the given frame, with as much accuracy as possible, and minimizing noise due to movements.  


#### Object Detection

In each frame, the YOLO (You Look Only Once) model using neural networks which is trained on an image database such as DarkNet, will help in identifying objects of interest. The DarkNet database can be installed by the instructions mentioned here- https://medium.com/analytics-vidhya/installing-darknet-on-windows-462d84840e5a. Since VATIC is a javascript video annotation tool, to perform these image processing tasks, the OpenCV javascript module will be used. However, a drawback of OpenCV is that it directly gives the centre coordinates x and y probabilities, width(w) and height(h) from the actual pixel coordinates, without returning the original coordinates i.e. x_start, x_end, y_start, y_end of the detected objects. 

![alt text](https://github.com/FrameNetBrasil/webtool/blob/gsoc2020_2/YOLO.JPG) 

    x = (x_mean- x_start)/x_start
    y = (y_mean- y_start)/y_start
    w = actual_width/frame_width
    h = actual_height/frame_height 
    
In order to get these original pixel coordinates, a modification to the file that generates these values can be made as follows:

    x_start = round(x- w/2)
    y_start = round(y- h/2)
    x_end = x_start + w
    y_end = y_start + h
    
This gives back the original pixel coordinate values for the object.


#### Object Tracking

After the objects have been detected in the first frame, they will be tracked for the next five frames to ensure that they are present in the video, and are significant to the annotation process. For this, the Kanade Lucas Tomasi (KLT) feature tracking algorithm will be used: https://www.learnopencv.com/object-tracking-using-opencv-cpp-python/ 

 Based on the bounding boxes of the objects, the algorithm will use them as interest points for local optimization. KLT uses a squared distance  criterion to check for transformation parameters such as displacement in x and y  from the original positions. Thus, the bounded boxes of the identified objects are  expanded using interpolation while they are being tracked with the help of these  transformation parameters as features. KLT is chosen as it is able to handle occlusions very well, and can track major transformations over shorter periods of  time efficiently too.

Two cases that may arise are as follows:

* An object detected in the first frame does not persist in the video for the next 5 frames. In that case, the coordinates of the object will be discarded and it will not be considered for annotation.
* The object is present in the video for the next 5 frames. Its original coordinates are then retained and an image is generated from the bounding boxes.

In the second case, an image will be generated for the object based on its bounding box coordinates.

This same procedure is followed using a windowing technique for every set of five frames for the duration of the video, to identify all possible objects. 


#### User Validation

Based on the objects identified by the model, the user has to make a decision to accept or reject them. Therefore, an option is provided to the user for manual bounding box creation in case he is not satisfied. 


#### Saving Identified Objects in File Storage System

Before the images of the tracked objects can be stored in the file system, they need to meet certain size and quality requirements, to ensure better accuracy for annotation. Moreover, duplicate images of the same object need to be discarded.

For example:

    OBJECT_MIN_HEIGHT =  800 pixels
    OBJECT_MIN_WIDTH = 800 pixels
    OBJECT_QUALITY = 200 DPI (Dots per inch)

A folder to store the images of these objects will be created as follows:

    <OBJECTS_STORE>/<video_id>/image_name.JPG
    
The image name will be updated sequentially for every new object being stored.

    
#### Timeline

The tasks specified in the workflow will be completed as follows:  
*July 3rd- July 10th*: Tasks 1 to 5 for object detection using YOLO   
*July 11th- July 20th*: Tasks 6,7 and 10 for object tracking  
*July 21st- July 25th*: Tasks 9,11 and 12 for validation and storage in file system  
*July 26th- July 27th*: Phase 2 evaluation   



### Part 3: Data Compilation and Reporting Module

With the textual and image data obtained from these two pipelines, and automated annotation procedure for the video will be performed as follows:

1. Download the Flickr30k dataset, that contains images with their corresponding captions
2. Get the images from the object store folder
3. Train a CNN+LSTM model on the Flickr30k dataset for image caption generation
4. Generate captions for the object images using the above trained model
5. Convert the captions to Portuguese using the Google Translate API
6. Add POS tags for the textual data (words and phrases) from the SentenceMM table, and the image data (captions) from step 4.
7. Match the POS-tagged words, phrases and captions with lexical units from the Frame table in the database, either directly or using synonym checks possible with Altervista, or the Big Huge Thesaurus APIs.
8. List the frames that are invoked by the lexical units.
9. Train an ML model for semantic annotation of lexical units with their corresponding frame elements. This can be done using the already annotated Wikipedia corpus and others.
10. Using the trained model, generate annotations for the captions, words and phrases
11. Captions with same frame elements as certain words and phrases, will be cross-annotated.


#### Generating Image Captions

The images of the objects identified in part 2 will need to be captioned for creating their corresponding lexical units. Therefore, a caption-generating model, involving CNN and LSTM neural networks will be trained on the Flickr 30k image dataset. The architecture of the training model will be as described in the following link-
https://data-flair.training/blogs/python-based-project-image-caption-generator-cnn/

Flickr30k Dataset for image captioning:  http://shannon.cs.illinois.edu/DenotationGraph/data/index.html


#### Preprocessing

The captions generated in the previous step are in English. Therefore, to convert them to Portuguese, the Google Translate API will be called. To help in the identification of their related lexical units and frames they invoke, the image captions, as well as words and phrases in the JSON file obtained as textual data from part 1 will be POS-tagged. 


#### Identifying Lexical Units and Frames

All of the POS-tagged text from the preprocessing step will be treated as lexical units, and mapped with existing lexical units in the FrameNet database. If they have the same words and POS tags, a matching lexical unit is found. If not, a synonym of the word that is a lexical unit in the database is considered. The synonyms of a word are obtained with the help of the AlterVista or BigHugeLabs API. http://thesaurus.altervista.org/service
http://words.bighugelabs.com/api.php

Finally the frame to which the lexical unit belongs is found from the database. This will be used for generating the frame elements of the lexical unit for semantic annotation in the next step.

#### Automated Annotation 

An ML model will be trained on an already annotated FrameNet text (eg: the Wikipedia corpus), so that it can generate frame elements for unseen data, that is the text and image data in our project, obtained from parts 1 and 2. The model should be able to accurately identify the frame elements that each lexical unit invokes. Moreover, if an image caption and a subtitles text or phrase invoke the same frame element, they should be cross-annotated. This will then be used to generate the Gold Standard Corpus.

Following are some papers that I referred to decide on an ML model that would be most suitable:
http://www.cs.cmu.edu/~ark/SEMAFOR/  
https://github.com/microth/mateplus  
https://www.mitpressjournals.org/doi/pdf/10.1162/COLI_a_00163  
https://arxiv.org/pdf/1706.09528v1.pdf  
https://www.aclweb.org/anthology/P15-2036.pdf  
http://www.coli.uni-saarland.de/projects/salsa/shal/  
http://nlp.cs.lth.se/software/semantic-parsing-framenet-frames/   


#### Timeline

The tasks specified in the workflow will be completed as follows:  
*July 28th- Aug 3rd*: Tasks 1 to 4 for generating image captions using trained model  
*Aug 4th- Aug 6th*: Tasks 5 to 6 for POS tagging textual and image data  
*Aug 7th- Aug 15th*: Tasks 7 to 8 for identifying frames  
*Aug 16th- Aug 27th*: Tasks 9 to 11 for automatically annotating the corpus  
*Aug 28th- Aug 30th*: Final Evaluation  

---

Some links for reference:

[http://www.redhenlab.org/home/tutorials-and-educational-resources/-red-hen-rapid-annotator](http://www.redhenlab.org/home/tutorials-and-educational-resources/-red-hen-rapid-annotator)

[https://sites.google.com/case.edu/techne-public-site/red-hen-rapid-annotator](https://sites.google.com/case.edu/techne-public-site/red-hen-rapid-annotator)
