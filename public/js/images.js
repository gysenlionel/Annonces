window.onload = () => {
    let links = document.querySelectorAll("[data-delete]")

    for (let link of links) {
        link.addEventListener('click', function (e) {
            // désactiver le lien
            e.preventDefault()

            if (confirm("Voulez-vous supprimer cette image?")) {
                // On envoie une req Ajax vers le href du lien avec la méthode Delete
                fetch(this.getAttribute("href"), {
                    method: "DELETE",
                    headers: {
                        "X-Requested-With": "XMLHttpRequest",
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({ "_token": this.dataset.token })
                }).then(
                    // On récupère la réponse en JSON
                    response => response.json()
                ).then(data => {
                    if (data.success) {
                        this.parentElement.remove()
                    } else {
                        alert(data.error)
                    }
                }).catch(e => alert(e))
            }
        })
    }
}