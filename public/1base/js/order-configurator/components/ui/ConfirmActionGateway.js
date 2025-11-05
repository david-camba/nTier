import { h, initComponent } from '../../lifetree.js';

function ConfirmActionGateway(props) {
    const innerPropsKeys = [
        'promiseResolve', // Función a ejecutar al confirmar
        'domReference', // Pasar objeto estilo: objeto = {current: null}, luego desde fuera, una vez creado el nodo, injectar el objeto con objeto.current = createDomNode(...)
        'message', 'acceptText', 'cancelText' //para formatear el texto mostrado        
    ];

    props.class = 'modal-overlay'; //fijamos la clase del componente para que haga overlay correctamente sobre su padre

    function userResponse(confirmAction){
        
        props.promiseResolve(confirmAction); //resolvemos la promesa con el resolve que nos inyectaron
        removeWithFade(props.domReference.current); //apuntamos al "current", ya cambiado desde fuera, y nos eliminamos
    }

    function removeWithFade(selfDomNode, duration = 250) {
        //Si el nodo no existe o ya no está en el DOM, no hay nada más que hacer
        if (!selfDomNode || !selfDomNode.parentNode) return;

        selfDomNode.style.transition = `opacity ${duration}ms ease`;
        selfDomNode.style.opacity = '0';

        // Espera la duración de la animación antes de quitarlo
        setTimeout(() => {
            selfDomNode.remove();
        }, duration);
    }

    // initComponent es parte de la convención de tu framework, lo mantenemos.
    // Este componente no usará setProp, pero es bueno ser consistente.
    initComponent(props, innerPropsKeys, "ConfirmActionGateway");

    return h('div', { /*class: 'modal-overlay'*/ },
        h('div', { class: 'modal-content' },
            // 1. El mensaje del modal
            h('p', { innerText: props.message }),

            // 2. El contenedor para los botones
            h('div', { class: 'modal-actions' },
                // 3. Botón de Cancelar
                h('button', { 
                    class: 'cancel-btn', 
                    onclick: () => userResponse(false), //resolvemos con false si se cancela la accion
                    innerText: props.cancelText,
                }),
                
                // 4. Botón de Confirmar
                h('button', { 
                    class: 'confirm-btn', 
                    onclick: () => userResponse(true), //resolvemos con true si se acepta
                    innerText: props.acceptText, 
                })
            )
        )
    );
}

export default ConfirmActionGateway;