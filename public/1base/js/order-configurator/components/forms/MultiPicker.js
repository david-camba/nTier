import { h, initComponent } from '../../lifetree.js';


function MultiPicker(props){
    const innerPropsKeys = ['$itemList', '$pickedList', '$saveChanges', 'filterBy', 'title'];

    // Hook - beforeInitComponent()
    // Se lanza en el "initComponent", nos permite trabajar con las props antes de fijar el primer arbol virtual
    // Pasa el "setProp" y lo guarda en "props.setProp" por si queremos desplazar la lógica a otras funciones para ser llamadas desde el beforeInitComponent sin necesidad de pasarles el "setProp" como argumento
    props.beforeInitComponent = (setProp) => {     

        //Marcamos todos los items seleccionados en la lista
        if(props.$pickedList?.length > 0){
            const pickedItemIds = props.$pickedList.map(item => item.idKey);

            const updatedList = props.$itemList.map(item => ({
                ...item,
                isPicked: pickedItemIds.includes(item.idKey)
            }));
            setProp('$itemList', updatedList);            
        }
    }

    const setProp = initComponent(props, innerPropsKeys, "MultiPicker");

    function AddOrRemoveItem(item){
        const itemId = item.idKey;
        const addItem = (!item.isPicked) ? true : false;

        const updatedList = props.$itemList.map(currentItem => 
            currentItem.idKey === itemId
                ? { ...currentItem, isPicked: addItem } 
                : currentItem
        );
        
        setProp('$itemList', updatedList); //Seteo la lista actualizada

        if(addItem) {
            setProp('$pickedList', [...props.$pickedList, item] ); //si el item no estaba seleccionado, lo añado
        }else{
            setProp('$pickedList', props.$pickedList.filter(oldItem => oldItem.idKey !== itemId)); //si el item estaba seleccionado, lo elimino
        }

        setProp('$saveChanges', true);

        return true;
    }

    return h('div', {class: 'multipicker-container'}, 
    [ // Contenedor principal
        h('h2', { innerText: props.title }),
        h('div', { class: 'multipicker-list' },
            () => {
                return props.$itemList.filter(item => {
                    // Se comprueba que el filtro exista y tenga un 'field' para evitar errores
                    if (props.filterBy && props.filterBy.field) {
                        return item[props.filterBy.field] == props.filterBy.value;
                    }
                    // Si no hay filtro, se devuelve true para no descartar ningún elemento
                    return true;
                }).map(item =>    
                    h('div',{ class: 'extra-item', idKey: item.idKey, onclick: () => AddOrRemoveItem(item) }, 
                    [
                        h('input', {
                                type: 'checkbox',
                                id: `extra-${item.idKey}`,
                                name: 'extras',
                                value: item.idKey,
                                checked: item.isPicked,
                        }),
                        h('label',{ for: `extra-${item.idKey}`}, 
                        [
                            h('strong', { innerText:  `${item.name} (+ ${item.price.toFixed(2)} €)`}),
                            h('small', { innerText: item.description || '' })
                        ])                            
                    ])                    
                );
            }
        )
    ]);    
}

export default MultiPicker; 