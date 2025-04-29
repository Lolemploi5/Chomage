function captureAndShare() {
    const infoPanel = document.querySelector('.info-panel');

    if (!infoPanel) {
        alert('Aucune section à capturer. Veuillez sélectionner une région ou un département.');
        return;
    }

    infoPanel.classList.add('capture-style');

    // Masquer les boutons d'action
    const actionButtons = infoPanel.querySelector('.action-buttons');
    if (actionButtons) {
        actionButtons.style.display = 'none';
    }

    const originalHeight = infoPanel.style.height;
    infoPanel.style.height = 'auto';

    html2canvas(infoPanel, {
        backgroundColor: '#ffffff', 
        scale: 2 
    }).then(canvas => {
        infoPanel.classList.remove('capture-style');
        if (actionButtons) {
            actionButtons.style.display = '';
        }
        infoPanel.style.height = originalHeight; 

        canvas.toBlob(blob => {
            if (!blob) {
                alert('Une erreur est survenue lors de la capture de l\'image.');
                return;
            }

            const item = new ClipboardItem({ 'image/png': blob });
            navigator.clipboard.write([item]).then(() => {
                alert('Image copiée dans le presse-papiers !');
            }).catch(err => {
                console.error('Erreur lors de la copie dans le presse-papiers :', err);
                alert('Impossible de copier l\'image dans le presse-papiers.');
            });
        });
    }).catch(err => {
        console.error('Erreur lors de la capture de l\'image :', err);
        alert('Une erreur est survenue lors de la capture de l\'image.');
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const shareButton = document.getElementById('share-data');

    if (shareButton) {
        shareButton.addEventListener('click', captureAndShare);
    }
});