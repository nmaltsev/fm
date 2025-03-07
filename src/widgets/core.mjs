// <fm-component source="" arg1="abc" />
const DEFAULT_ATTRS = ['source'];
customElements.define('fm-component', class extends HTMLElement {
    #source = null;
    
    constructor() {
        super();
        this.attachShadow({mode: 'open'});
    }

	/**
		@returns {string[]} list of attributes
	*/
    static get observedAttributes() {
        return ['source']; 
    }

    attributeChangedCallback(name, oldValue, newValue) {
        // console.log('Attr changed %s %s %s', name, oldValue, newValue)
        if (name === 'source') {
            this.#source = newValue;
        }
    }

    connectedCallback() {
        this.initialize();
    }

    initialize() {
        let last;
        while (last=this.shadowRoot.lastElementChild) last.remove();

        let i = this.attributes.length;
        const futureParameters = {};
        while(i-->0) {
            if (!DEFAULT_ATTRS.includes(this.attributes[i].name)) {
                futureParameters[this.attributes[i].name] = this.attributes[i].value;
            }
        }

        import(this.#source).then((module) => {
            if (!module.default) return;
            
            const res = module.default(futureParameters);
            if (res instanceof HTMLElement) {
                this.shadowRoot.append(res);
            }
        });
    }

    disconnectedCallback()  {}
});
