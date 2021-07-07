## List of deliverables vs Schedule

The idea 3 includes better compatibility of the Webtool and more data visualizations features.It is consists of the following:  

### Implementation for importing from XML, CONLLU, JSON (Week 2-3)

Data that the system would be using must be compatible and flexible because of its use in GlobalFrameNet and various other platforms. 
Thus an interface to convert data in order to be imported from varioius types like XML,CONLLU and JSON is aimed to be created.

1.Implementation of feature to import XML files (using the Berkeley format) at menu "Utils"

2. Implementation of feature to import CONLLU files (using similar CoNLL output from OpenSESAME) at menu "Utils"

3. Export of XML files (it is necessary to adapt/correct the current existing code)

4. Export of CONLL files (it is necessary to adapt/correct the current existing code)

#### Exporting to CONLLU format as opposed to the previous CONLL format.

Since CONLL is an old format and has been upgraded to CONLLU format it is necessary to update to the latest version

### Making the Lexical Entry Report more interactive (Week 4-6)

Inorder to supplement the views of the Lexical Entry Report it is necessary to offer different insights into patterns occurrence of 
Frame Elements at different levels of generalization. These would be as interactive as possible, so that the user could select 
many different views by setting a few parameters. These include:

· To toggle between showing only Core FEs or all FEs. 

·A view of the same lemma+POS in different frames, that is, different senses (LUs) for the same word

· A view of different POS of the same lemma. Some of these will be unrelated senses, which will generally be in different frames with 
quite different FEs

· To compare frames linked by the relation Causative_of.The FEs that are the same in both frames would have similar valences, but a 
report that highlighted differences in their valences would make this easy to see

### Adding more parameters for the Grapher API of WebTool (Week 7-8)

As the Grapher API of the WebTool consists of the following parts like Frames by CxN , Frames by Domain and Constructicon. So to get a 
better understanding of the graph an additional field is to be added to the Frames by Domain - Semantic Type

### Creating more graphs for the GlobalFramenet (Week 9)

To bring more number of graphs we can build a graph for various parameters like Sentences per Language and Lexeme per Language


