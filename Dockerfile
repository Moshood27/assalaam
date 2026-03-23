FROM ubuntu:latest
LABEL authors="mosho"

ENTRYPOINT ["top", "-b"]