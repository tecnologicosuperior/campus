const media = document.querySelector('#media')

const urlParams = new URLSearchParams(window.location.search)

const mediaId = urlParams.get('mediaId')

document.addEventListener('DOMContentLoaded', () => {

    getMediaFile(mediaId)
})

const getMediaFile = (mediaId) => {

    fetch(`${API_URL}/media/scripts/blob.txt`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'Access-Control-Allow-Origin': '*'
        }
    })
    .then(res => res.text())
    .then(blob => {

        

        const blobUrl = URL.createObjectURL(blob);

        media.src = blobUrl
    })
}