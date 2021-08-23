# bunnystream-api-php

### Usage

Import the library and use the constructor:

    $bstream = new BunnyCDNStream("YOUR_VIDEO_LIBRARY_ID", "YOUR_STREAM_ZONE_API_KEY");

---

### Methods

#### `(json) getVideo($videoId)`

Provided that the correct Video ID and Stream API key were used, this will return a JSON object containing the video object's metadata.

Exceptions are divided into HTTP status codes (and this applies to all methods):

- 401
  - Your API key is invalid
- 404
  - Either your library or video could not be found
  
It is safe to assume that if no exception is thrown, the video's information will be in the response JSON object.

#### `(json) listVideos($page = 1, $perPage = 10, $sortBy = "date", $search = null, $collection = null)`

While you can override the default values, this, by default, will provide the first 10 videos in your Stream zone.

You can also provide an optional "search" parameter to find videos, or look for videos in specific collections.

#### `(json) updateVideo($videoId, $title, $collectionId)`

This will update a video's title and collection ID.

#### `(json) deleteVideo($videoId)`

This will **permanantly** delete a video.

#### `(json) createVideo($title, $collectionId = null)`

Creates a video object (this MUST BE DONE before calling `uploadVideoWithId()`).

#### `(json) uploadVideoWithVideoId($videoId, $filePath)`

This will upload a video located at `/path/to/your/video.mp4` to the video object located at `$videoId`.

#### `(json) uploadVideo($title, $filePath, $collectionId = null)`

This will create a video object AND upload a video at `$filePath`. Collection ID is optional.

#### `(json) setVideoThumbnail($videoId, $thumbnailUrl)`

Set the video thumbnail to an image at `$thumbnailUrl`.

#### `(json) fetchVideo($videoId, $source, $headers = null)`

Fetch video from external source `$source` and upload it to `$videoId`. 

You may optionally provide a key value pair to `$headers` if your external source requires authentication.

#### `(json) addVideoCaptions($videoId, $language, $content, $label = null)`

Add a VRT captions file (`$content` = the /path/to/file.vrt) to `$videoId`. 

While `$label` is optional, it is _highly recommended_ so users know what they're selecting.

#### `(json) deleteVideoCaptions($videoId, $language)`

Deletes a caption file from a video object (where video == `$videoId`). 

Make sure to specify the correct 2-letter language code.


