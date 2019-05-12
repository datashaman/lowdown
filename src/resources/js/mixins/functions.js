export const functionsMixin = {
    methods: {
        generateSignature(f, link) {
            if (link === undefined) {
                link = false
            }

            let parameters = f.parameters
                .map(function (param) {
                    let result = ''

                    if (param.type) {
                        result += param.type + ' '
                    }

                    result += '$' + param.name

                    if (param.defaultValue !== undefined) {
                        result += ' = ' + JSON.stringify(param.defaultValue)
                    } else if (param.defaultValueConstantName !== undefined) {
                        result += ' = ' + param.defaultValueConstantName
                    }

                    return result
                })
                .join(', ')

            if (link) {
                return '<a href="#' + (f.shortName || f.name) + '">' + (f.shortName || f.name) + '</a>(' + parameters + ')'
            } else {
                return (f.shortName || f.name) + '(' + parameters + ')'
            }
        },
        abstract(f, type) {
            if (f.modifiers.abstract && type !== 'interface') {
                return 'abstract';
            }
            if (f.modifiers.final) {
                return 'final';
            }
        },
        scope(f) {
            if (f.modifiers.static) {
                return 'static';
            }
        },
        visibility(f) {
            if (f.modifiers.protected) {
                return 'protected';
            }
            if (f.modifiers.private) {
                return 'private';
            }
        }
    }
}
