function setTitle(title: string) {
    if (document) {
        document.getElementsByTagName('title')[0].innerText = title
    }
}

export { setTitle }
