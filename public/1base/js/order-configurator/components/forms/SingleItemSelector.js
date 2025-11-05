import { h, initComponent } from '../../lifetree.js';

function SingleItemSelector(props){
    const innerPropsKeys = ['$itemList', '$selected', '$saveChanges', 'filterBy', 'title', 'ItemViewComponent', 'childrenProps'];
    //En "ItemViewComponent", podemos pasar el objeto de vista que prefiramos, renderizará nuestra lista

    // Hook - initComponent()
    // Se lanza en el "initComponent", nos permite trabajar con las props antes de fijar el primer arbol virtual
    props.beforeInitComponent = (setProp) => {
        let filteredList = null;
        if(props.filterBy){
            filteredList = props.$itemList.filter(item => {
                // Se filtra la lista recibida
                if (props.filterBy.field && props.filterBy.value) {
                    return item[props.filterBy.field] == props.filterBy.value;
                }
                return true;
            });
        }

        if(filteredList) setProp('$itemList', filteredList); //fijamos la lista filtrada        

        //si hay un articulo seleccionado, lo seleccionamos en la lista para asegurarnos de que se selecciona
        if(props.$selected !== null){
            selectItem(props.$selected, true); 
        }
    }

    const setProp = initComponent(props, innerPropsKeys, "SingleItemSelector");

    //Por qué se lanza reorderAnimation al seleccionar? Tengo que debugear eso, tremendo cagadón

    function selectItem(itemId, forceUpdateList=false){
        const currentSelectionId = (typeof itemId === 'object') ? itemId.idKey : itemId;
        if(!currentSelectionId) return console.warn("itemId no tiene idKey o no esta definido", itemId);
        
        const previousSelectionId = props.$selected?.idKey ?? props.$selected;

        if(currentSelectionId === previousSelectionId && !forceUpdateList) return;

        let hasChanged = true;
        let selectedItem;
        const listWithNewSelection = props.$itemList.map(({ $isSelected, ...rest }) => {
        // si es el que coincide con itemId, añadimos selected: true
        if (rest.idKey === currentSelectionId) {
            selectedItem = rest;
            if($isSelected){
                hasChanged = false; //si el modelo seleccionado es el mismo, el click no debe cambiar nada
                if(!forceUpdateList) return;
            }
            return { ...rest, $isSelected: true };
        }
        // en caso contrario, dejamos todo igual pero sin selected
        return { ...rest };
        });

        //Se actualiza la lista si hay una actualización visual
        if(hasChanged){
            props.setProp('$itemList', listWithNewSelection); //Aquí tenemos que usar props.setProp porque somos llamados en el beforeMount            
        }
        //Se actualiza el itemId y se marca que se ha realizado un cambio si ha habido cambios realmente
        if(currentSelectionId !== previousSelectionId){  
            props.setProp('$selected', selectedItem);          
            props.setProp('$saveChanges', true);
        }
        //DONE: handleSlot debe añadir a sus dependencias las propiedades globales que los nuevos componentes o slots creados necesiten
    }

    return h('div', {class: 'selector-container'}, [ // Contenedor principal
        // 1. El título <h2>
        h('h2', { innerText: props.title }),

        // 2. La rejilla que contendrá las tarjetas de los modelos
        h('div', { class: 'selector-grid' },         
            // 3. El mapeo de los modelos para crear las tarjetas (los hijos de la rejilla)

            // Dejarlo como una función permite al compilador extraer esta dependencia para solicitarla sus padres.
            // IMPORTANTE: la primera "props.$[var]" será la lista dinámica asociada a este lista. En este caso props.$itemList. Aunque dentro se utilicen otras variables dinámicas (con $), este "div" solo se actualizará cuando haya cambios en "props.$itemList"

            // const hasChanged = props.$saveChanges; //Esto dentro haría (incluso como comentario) que la lista dinámica no funcionara como lo esperado. Esta lista se actualizaría con los cambios en "props.$saveChanges", pero no con los cambios que queremos en la lista "$itemList". El compilador JIT recupera el primer $.var que encuentra
            () => {
                return props.$itemList.map(item =>    
                    h(props.ItemViewComponent, { 
                        idKey: item.idKey, 
                        name: item.name, 
                        image: item.image, 
                        price: item.price,
                        additionalProps: props.childrenProps, 
                        $isSelected: item.$isSelected, 
                        'data-item-id': item.idKey, 
                        onclick: (e) => selectItem(item.idKey)
                    })
                );
            }
            //TO-DO: añadir error (o warning) si se detecta que una lista dinámica, ya sea la propia definición de la lista o los vNodes que se crean luego no tienen idKey definido

        )
    ])    
}

export default SingleItemSelector; 