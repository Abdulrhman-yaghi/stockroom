# Reproducing the demo film

## Requirements

Run the local dashboard and report queue worker first. Install Chromium with Playwright. Install FFmpeg and make its executable available on PATH.

## Record

```sh
node scripts/record-demo.mjs
```

This signs into the local demo, records seven real application scenes and writes the footage and timing manifest to the ignored .recordings directory. It adjusts fictional inventory, creates and cancels a demo order, and generates a report.

## Edit

```sh
node scripts/edit-demo.mjs
```

The editor creates title cards, trims loading time, adds chapter captions, applies short transitions and exports a 1920 by 1080 H.264 video with an English caption track. The film is silent so it remains usable without audio. The generated poster, video and captions are written to docs/assets.

Set the FFMPEG_BIN environment variable if FFmpeg is not on PATH. Set DEMO_FONT to a local TrueType font if the default system font path does not exist. Set DEMO_URL to record an alternate local application URL.

The raw recordings and authentication state are ignored by Git. Do not record a production database or use a real account when reproducing this film.
