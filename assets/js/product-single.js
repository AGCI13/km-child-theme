document.addEventListener('DOMContentLoaded', () => {
    /**
     * Add minus and plus buttons to number inputs
     */
    const numberInputPlusMinus = () => {
        const numberInputs = document.querySelectorAll('.quantity input[type="number"]');

        numberInputs.forEach(input => {
            // Création du bouton "-"
            const minusButton = document.createElement('span');
            minusButton.classList.add('qty-modifier', 'minus');
            minusButton.innerHTML = `-`;
            minusButton.onclick = () => {
                input.value = Math.max(1, input.value - 1); // Assure que la valeur minimale est 1
                triggerChangeEvent(input);
            };

            // Création du bouton "+"
            const plusButton = document.createElement('span');
            plusButton.classList.add('qty-modifier', 'plus');
            plusButton.innerHTML = `+`;
            plusButton.onclick = () => {
                input.value = parseInt(input.value) + 1;
                triggerChangeEvent(input);
            };

            // Insertion des boutons
            input.parentNode.insertBefore(minusButton, input);
            input.parentNode.appendChild(plusButton);

            // Mise à jour du panier
            const handleMouseOut = () => {
                setTimeout(() => {
                    jQuery("[name='update_cart']").trigger("click");
                }, 200);
            };
            minusButton.addEventListener('mouseout', handleMouseOut);
            plusButton.addEventListener('mouseout', handleMouseOut);
        });
    }

    const triggerChangeEvent = (element) => {
        const event = new Event('change', {
            bubbles: true
        });
        element.dispatchEvent(event);
    }

    numberInputPlusMinus();
});
