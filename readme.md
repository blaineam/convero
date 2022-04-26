# Convero
## _A Brute Force Animation Compressor_

Convero is a php script that can run on macos that uses a brute force method 
to compress aninmated stickers to specification sizes for various sticker 
platforms: 

- iMessage (S,M,L Sizes)
- Discord (Emoji, Sticker formats)
- Telegram (Video Sticker Format)
- Giphy/Tenor (Basic Animated Gif Format)

## Features

- wraps ffmpeg, gifsicle, imagemagick, apng, apngasm.
- outputs all possible variations of a compressed animated sticker that
- you can choose which compression level you want to use for your services.
- uses the system temp directory for trials
- can use tinypng.com api for even better compression efficiency.
- is ran from a terminal window/cli.

## How to Run

 1. Clone the repo to a folder on your mac computer (supports Apple Silicon Macs too)
 2. make sure you have brew.sh installed on your mac.
    1. [brew.sh](https://brew.sh/) install instructions
 3. open a terminal window and install dependencies with:
    1. `brew install -y ffmpeg gifsicle php@7.4 imagemagick webp apngasm pngquant curl gnu-tools`
    2. close this terminal window and open a new one.
 4. cd into the folder you cloned the repo.
 5. run the command and replace `<preset>` with a preset from below (arguments in [] are optional, use them without the wrapping []):
    1. `php index.php preset=<preset> [fps=<integer>] [tinypngapikey=<YOUR_API_KEY>] [aggressive]`
    2. Preset options are:
        1. `telegram`
        2. `imessage`
        3. `imessage-medium`
        4. `imessage-small`
        5. `giphy`
        6. `tenor`
        7. `discord`
        8. `discord-emoji`
    3. `[fps=<integer>]` lets you set a minimum frame per second for the compression trials.
    4. `[tinypngapikey=<YOUR_API_KEY>]` lets you use your own tinypng api key to run compression attempts(NOTE: This can be Costly! $$$$$)
    5. `[aggressive]` used even more intensive default options that try to successfully compress images that are impossible without this optional flag.



## Development

Want to contribute? Great! 
Feel free to open a pr with any suggested improvements you think of. 
The more tested your pr is the quicker I will merge it into main.

## Docker

Even with the inherent performance hits using docker for mac, I'd like to make this docker compatible at some point so that it can run on other platforms and work more as a service with a form a queue of files to work through.

## License

MIT
