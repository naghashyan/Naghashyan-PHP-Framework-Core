// BaseModel class: a base class for creating models with type validation and default values.
export class BaseModel {

    data = {};

    /**
     * Constructor for BaseModel.
     * @param {Object} data - The data object used to initialize the model.
     */
    constructor(data = {}) {
        const structure = this.getStructure();
        if (data && Object.keys(data).length > 0) {
            this.data = data;
        }
        this.createAccessors(structure);
        this.validateAndAssign(structure);
    }

    updateData(data) {
        this.data = data;
        this.validateAndAssign(this.getStructure());
        return this;
    }

    getData() {
        return this.data;
    }

    /**
     * Creates getters and setters for each property defined in the model's structure.
     * This enables validation when properties are assigned new values.
     * @param {Object} structure - The structure definition of the model.
     */
    createAccessors(structure) {
        Object.keys(structure).forEach(key => {
            Object.defineProperty(this, key, {
                get: () => {
                    if (typeof this.data[key] !== 'undefined') {
                        return this.data[key];
                    }
                    if (structure[key].defaultValue) {
                        return structure[key].defaultValue;
                    }
                    return null;
                },
                set: (newValue) => {
                    const {type} = structure[key];
                    this.data[key] = this.processValue(newValue, type, key);
                },
                enumerable: true,
                configurable: true
            });
        });
    }

    /**
     * Validates and assigns data to the model based on its structure.
     * @param {Object} structure - The structure definition of the model.
     */
    validateAndAssign(structure) {
        const data = this.getData();
        Object.keys(structure).forEach(key => {
            const {type, defaultValue} = structure[key];
            let value = data.hasOwnProperty(key) ? data[key] : defaultValue;
            value = this.processValue(value, type, key);
            this[key] = value;
        });
    }

    /**
     * Processes the value based on its type.
     * @param {*} value - The value to process.
     * @param {*} type - The expected type of the value.
     * @param {string} key - The property key for the value.
     * @returns {*} - The processed value.
     */
    processValue(value, type, key) {


        if (Array.isArray(type) && type.length > 1) {
            // Check if the value matches any of the specified types
            let validType = false;
            let isTypeArray = false;
            let isTypeBaseModel = false;
            for (let singleType of type) {
                try {
                    validType = this.validateType(value, singleType, key);
                    if (validType && validType.prototype && validType.prototype instanceof BaseModel) {
                        isTypeBaseModel = true;
                        if (Array.isArray(singleType)) {
                            isTypeArray = true;
                        }
                    }
                    break;
                } catch (error) {
                    // Continue checking against other types
                }
            }
            if (validType === false) {
                throw new Error(`${key} is of an incorrect type`);
            }
            if (isTypeBaseModel) {
                if (!isTypeArray) {
                    return this.handleSingleModel(value, validType);
                }
                return this.handleArrayOfModels(value, validType);
            }
            try {
                if (type.prototype instanceof BaseModel) {
                    return this.handleArrayOfModels(value, type);
                } else {
                    return this.handleSimpleArray(value, type, key);
                }
            } catch (e) {
                return value;
            }


        } else if (Array.isArray(type)) {
            // Handle arrays of simple types
            if (type[0].prototype instanceof BaseModel) {

                return this.handleArrayOfModels(value, type[0]);
            } else {
                return this.handleSimpleArray(value, type[0], key);
            }
        } else {
            // Validate and return simple types
            this.validateType(value, type, key);
            return value;
        }
    }

    /**
     * Handles BaseModel instances, ensuring each element is an instance of the specified model.
     * @param {*} value - The array to process.
     * @param {BaseModel} modelType - The type of BaseModel the array elements should be instances of.
     * @returns {*} - The processed single with model instances.
     */
    handleSingleModel(value, modelType) {
        return value instanceof modelType ? value : new modelType(value);
    }

    /**
     * Handles arrays of BaseModel instances, ensuring each element is an instance of the specified model.
     * @param {Array} value - The array to process.
     * @param {BaseModel} modelType - The type of BaseModel the array elements should be instances of.
     * @returns {Array} - The processed array with model instances.
     */
    handleArrayOfModels(value, modelType) {
        if (!Array.isArray(value)) {
            throw new Error(`In ${this.constructor.name} Model Expected an array for ${modelType.name}, but got ${typeof value}`);
        }
        return value.map(item => item instanceof modelType ? item : new modelType(item));
    }

    /**
     * Handles arrays of simple types, validating each element in the array.
     * @param {Array} value - The array to process.
     * @param {*} elementType - The expected type of the array elements.
     * @param {string} key - The property key for the array.
     * @returns {Array} - The validated array.
     */
    handleSimpleArray(value, elementType, key) {
        if (!Array.isArray(value)) {
            throw new Error(`In ${this.constructor.name} Model Expected an array for ${key}, but got ${typeof value}`);
        }
        return value.map(item => {
            this.validateType(item, elementType, key);
            return item;
        });
    }

    /**
     * Validates a value against the expected type(s).
     * @param {*} value - The value to be validated.
     * @param {*} expectedType - The expected type(s) of the value. Can be a single type or an array of types.
     * @param {string} key - The property key associated with the value.
     */
    validateType(value, expectedType, key) {
        // Directly handling null and undefined values
        if (value === null || value === undefined) {
            if (value === expectedType) {
                return value;
            }
            throw new Error(`In ${this.constructor.name} Model ${key} cannot be null or undefined unless explicitly allowed`);
        }

        if (Array.isArray(expectedType)) {
            for (let type of expectedType) {
                try {
                    this.checkType(value, type);
                    return type; // Return the type that matches the value
                } catch (error) {
                    // Continue checking against other types
                }
            }
            throw new Error(`Expected ${key} to be one of the types: ${expectedType.join(', ')}, but got ${typeof value}`);
        } else {
            this.checkType(value, expectedType);
            return expectedType; // Return the single expected type
        }
    }

    /**
     * checkType: Helper method for validateType. Checks if the value matches a single type or an array of that type.
     * @param {*} value - The value to be checked.
     * @param {*} type - The type to check against. Can be a single type or an array of a single type.
     * @returns {boolean} - True if the value matches the type, false otherwise.
     */
    checkType(value, type) {
        if (type === null) {
            return value === null;
        }
        if (type === undefined) {
            return value === undefined;
        }
        if (Array.isArray(type)) {
            // Checks if value is an array and all its elements match the specified type.
            return Array.isArray(value) && value.every(item => this.checkType(item, type[0]));
        } else if (type === Number) {
            return typeof value === 'number';
        } else if (type === String) {
            return typeof value === 'string';
        } else if (type === Boolean) {
            return typeof value === 'boolean';
        } else if (type.prototype instanceof BaseModel) {
            // Checks if value is an instance of the specified BaseModel subclass.
            return value instanceof type;
        }
        return false;
    }

    // getStructure: An abstract method that should be implemented in derived classes to define the model's structure.
    getStructure() {
        return null;
    }
}

// ObjectModel function: Creates a dynamic model based on a given structure and optional data.
export function ObjectModel(structure, data = {}, extendFrom = null) {
    // DynamicModel class: extends BaseModel and implements getStructure to return the given structure.
    if (data === null) {
        data = {};
    }
    if (extendFrom) {
        class NGSDynamicModel extends extendFrom {
            getStructure() {
                const parentStructure = super.getStructure();
                if (parentStructure) {
                    return Object.assign(parentStructure, structure);
                }
                return structure;
            }

            getData() {
                const parentData = super.getData();
                if (parentData) {
                    return Object.assign(parentData, data);
                }
                return data;
            }
        }

        // Instantiates and returns a new DynamicModel with the provided data.
        return new NGSDynamicModel;
    }

    class NGSDynamicModel extends BaseModel {
        getStructure() {
            const parentStructure = super.getStructure();
            if (parentStructure) {
                return Object.assign(parentStructure, structure);
            }
            return structure;
        }

        getData() {
            const parentData = super.getData();
            if (parentData) {
                return Object.assign(parentData, data);
            }
            return data;
        }
    }

    // Instantiates and returns a new DynamicModel with the provided data.
    return new NGSDynamicModel;
}
