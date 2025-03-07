customElements.define('fm-tabs', class extends HTMLElement {
    #$root = null;
    
    constructor() {
        super();
    }

	/**
		@returns {string[]} list of attributes
	*/
    static get observedAttributes() {
        return []; 
    }

    attributeChangedCallback(name, oldValue, newValue) {
        console.log('Attr changed %s %s %s', name, oldValue, newValue)
    }

    connectedCallback() {
        const $root = this.attachShadow({mode: 'open'});
		$root.innerHTML = this.constructor.template;
        this.#$root = $root;
    }

    disconnectedCallback()  {
        // TODO
    }

    static template = `
<style>
:host {
    color: red;
}
</style>
<div>Hello world!</div>
`
});

export default function test(arg) {
    console.log('Arg:')
    console.dir(arg)
    return document.createElement('fm-tabs')
    // return 123;
}