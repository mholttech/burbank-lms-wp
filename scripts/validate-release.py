#!/usr/bin/env python3
"""Validate Burbank LMS source and distributable WordPress plugin ZIP."""
import argparse
import pathlib
import re
import sys
import zipfile

def extracted_version(content, pattern, name):
    found = re.search(pattern, content, re.MULTILINE)
    if not found:
        raise ValueError(f"Missing {name} version")
    return found.group(1)

def source_version():
    bootstrap = pathlib.Path("burbank-lms.php").read_text()
    cls = pathlib.Path("src/Burbank_LMS.php").read_text()
    a = extracted_version(bootstrap, r"^\s*\* Version:\s*(\d+\.\d+\.\d+)", "plugin header")
    b = extracted_version(cls, r"PLUGIN_VERSION\s*=\s*'(\d+\.\d+\.\d+)'", "plugin class")
    if a != b:
        raise ValueError(f"Version mismatch: header {a}, class {b}")
    return a

def check_zip(filename, expected):
    with zipfile.ZipFile(filename) as z:
        bad = z.testzip()
        if bad:
            raise ValueError(f"Corrupted archive member {bad}")
        names = set(z.namelist())
        required = {
            "burbank-lms/burbank-lms.php",
            "burbank-lms/src/Burbank_LMS.php",
            "burbank-lms/vendor/autoload.php",
            "burbank-lms/vendor-prefixed/autoload.php",
            "burbank-lms/README.txt",
        }
        missing = required - names
        if missing:
            raise ValueError(f"Missing distribution files: {sorted(missing)}")
        forbidden = ("burbank-lms/tests/", "burbank-lms/node_modules/", "burbank-lms/.github/", "burbank-lms/resources/")
        for name in names:
            if name.startswith(forbidden):
                raise ValueError(f"Development file packaged: {name}")
        for name in ("burbank-lms/composer.lock", "burbank-lms/package-lock.json"):
            if name in names:
                raise ValueError(f"Development lockfile packaged: {name}")
        header = z.read("burbank-lms/burbank-lms.php").decode()
        cls = z.read("burbank-lms/src/Burbank_LMS.php").decode()
        a = extracted_version(header, r"^\s*\* Version:\s*(\d+\.\d+\.\d+)", "archive header")
        b = extracted_version(cls, r"PLUGIN_VERSION\s*=\s*'(\d+\.\d+\.\d+)'", "archive class")
        if a != b or (expected and a != expected):
            raise ValueError(f"Archive versions mismatch: {a}, {b}, expected {expected}")
        readme = z.read("burbank-lms/README.txt").decode()
        stable = extracted_version(readme, r"^Stable tag:\s*(\S+)", "WordPress stable tag")
        if stable not in ("trunk", a):
            raise ValueError(f"Invalid stable tag {stable}")
        if not any(n.startswith("burbank-lms/build/") and n.endswith(".js") for n in names):
            raise ValueError("Compiled JavaScript missing")
    print(f"Verified {filename} (plugin version {a})")

def main():
    p = argparse.ArgumentParser()
    p.add_argument("--source")
    p.add_argument("--zip")
    p.add_argument("--version")
    args = p.parse_args()
    if args.source:
        found = source_version()
        if found != args.source:
            raise ValueError(f"Tagged source version {found} != {args.source}")
        print(f"Verified source version {found}")
    if args.zip:
        check_zip(args.zip, args.version)
    if not args.source and not args.zip:
        p.error("specify --source or --zip")

if __name__ == "__main__":
    try:
        main()
    except (OSError, ValueError, zipfile.BadZipFile) as e:
        sys.exit(str(e))
