# Android Staff App

This folder contains a simple Android WebView wrapper scaffold for the internal staff section.

## Target URL

- `https://YOUR-INTERNAL-HOST/joms-ci4/public/staff/login`

## Purpose

- sideload APK to staff Android devices
- open directly to staff login
- use internal network only

## Suggested next Android steps

1. Create an Android Studio project in this folder
2. Set the start URL to the internal `staff/login` page
3. Allow your internal host in network security config if needed
4. Build a debug/release APK for sideloading

## Web routes prepared in this repo

- `staff/login`
- `staff/`
- `staff/touch-booking`
- `staff/touch-booking/create`
- `staff/stock-lookup`