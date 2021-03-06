# Key structure

`minimalism-service-cacher` uses `Redis` to store the cache, and it leverages its key/value approac to save the information.

Every key follows a specific pattern, which is always the same:

```
m:T:L:c[:X]
```

The **m:T:L:C[:X]** patterns defines the information stored in the key.

## m

The `m` parameter is a simple text, which is always the same: `minimalism`. It server the purpose of identifying which caches are dynamically generated by minimalism.

### Examples

```
minimalism:
```

## T

The `T` parameter is the identifier of the `TYPE` of cache. In minimalism there can be two types of cache: `DATA` and `JSON`. These represent array data or json resources.

The paremeter is all capital letter and can only contain `DATA` or `JSON`.

### Examples

To identify a data structure, with an array

```
minimalism:DATA
```

To identify an object structure, with a {json:api} resource object

```
minimalism:JSON
```

## L

The `L` parameter identifies a `LIST`. Lists are used to quickly link two caches: the parent and the children. The key does not contain any relevant data, but it is used as an index to identify which caches are part of a list.

The list identifies the `parent` element of a relationship, and every child in the relationship will have a key containing both the parent (in the Element Structure format) and the Content (always in the Element structure format).

If the cache is not a list, the value of the parameter is the text `null` in lowercase.


### Examples

To identify a list of child objects. These bears no content and they are used as indexes in Redis

```
minimalism:DATA:parentName(parentIdentifier):childName(childIdentifier1)
minimalism:DATA:parentName(parentIdentifier):childName(childIdentifier2)
...
minimalism:DATA:parentName(parentIdentifier):childName(childIdentifierN)
```

## C

The `C` parameter identifies the `CONTENT`. This has two ways to be used: inside a list or as a standalone element. A `Content` inside a list is only used as an index key, and does not contain any relevant data. With the `List` set to `null` the key contains the information (`DATA` or `JSON`) of the content.

The parameter is defined as an Element structure.

### Examples

To identify a content

```
minimalism:DATA:null:name(identifier)
```

In case of a list, each list element will have at least two contents:

```
minimalism:DATA:null:parentName(parentIdentifier)
minimalism:DATA:null:childName(childIdentifier1)
minimalism:DATA:null:childName(childIdentifier2)
...
minimalism:DATA:null:childName(childIdentifierN)
```

## X

The `X` parameter identifies zero or more `CONTEXTS`. A `Context` specialises the type of information stored in cache. This can be a specific type of data (*for example you want to specify a public and a private set of data, you can do it with a context*).

The `Context` uses the same Key Element Structure of the rest; however, it has two diffferences: it can contain no `identifier` (`0` will be used as identifier), or it can contain multiple `Contexts`, in which case the contexts will be separated with a `-`.

### Examples

In case of a contextualised cache, the context is added to the end of the key

```
minimalism:DATA:null:name(identifier):context1(contextIdentifier1)-context2(contextIdentifier2)
```

There are various use cases in terms of contexts

```
no context
minimalism:DATA:null:name(identifier)

textual context (without identifier)
minimalism:DATA:null:name(identifier):contextName(0)

full context identifier
minimalism:DATA:null:name(identifier):contextName(contextIdentifier)

multiple contexts
minimalism:DATA:null:name(identifier):contextName(0)-contextName(contextIdentifier)
```

# Key Elements Structure

Each element (`List`, `Content`, `Context`) is identified with two parameters: `name` and `identifier`. The name is normally the name of the database field used to query the data, and the iedntifier is the value of the field. The cacher stores these information in the format `name(identifier)`.

## Contexts Key Elements Structure

While a `List` and a `Content` are uniquely defined by one name/identifier, a cache can belong zero or more contexts. For contexts, the element structure changes to `name1(identifier1)-name2(identifier2)-...-nameN(identifierN)`. If no `Context` is passed, the key won't store anything, however, a `Context` can be a simple `name` without and `identifier`. In this case, the key uses `0` as identifier. 