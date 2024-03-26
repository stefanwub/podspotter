import sys
from pytube import YouTube

def download(youtube_url, output_path, filename):
    yt = YouTube(youtube_url)
    video = yt.streams.get_highest_resolution()
    out_file = video.download(output_path=output_path, filename=filename)
    return out_file

if __name__ == "__main__":
    download(sys.argv[1], sys.argv[2], sys.argv[3])

