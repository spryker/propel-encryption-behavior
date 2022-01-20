# Spryker - PropelEncryptionBehavior

[![Build Status](https://github.com/spryker/propel-encryption-behavior/workflows/CI/badge.svg?branch=master)](https://github.com/spryker/propel-encryption-behavior/actions?query=workflow%3ACI+branch%3Amaster)
[![codecov](https://codecov.io/gh/spryker/propel-encryption-behavior/branch/master/graph/badge.svg?token=L1thFB9nOG)](https://codecov.io/gh/spryker/propel-encryption-behavior)
[![Latest Stable Version](https://poser.pugx.org/spryker/propel-encryption-behavior/v/stable.svg)](https://packagist.org/packages/spryker/propel-encryption-behavior)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%207.3-8892BF.svg)](https://php.net/)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%208-brightgreen.svg?style=flat)](https://phpstan.org/)
[![License](https://poser.pugx.org/spryker/propel-encryption-behavior/license)](https://packagist.org/packages/spryker/propel-encryption-behavior)

Seamlessly encrypt/decrypt Propel2 data fields. This library is a *plugin* for the [Propel2 ORM framework](http://propelorm.org/).
The library is a fork of [Athens\Encryption](https://github.com/AthensFramework/Encryption). 

For example:

```
// schema.xml

    <table name="my_class">
        <column name="id" type="integer" required="true" primaryKey="true" autoIncrement="true"/>

        <column name="my_data" type="varchar" size="255" />
        <column name="my_secret_data" type="BLOB" />
        <column name="my_searchable_data" type="varbinary" size="255" />

        <behavior name="encryption">
            <parameter name="column_name_1" value="my_secret_data" />
            <parameter name="searchable_column_name_1" value="my_searchable_data" />
            <parameter name="searchable" value="false" />
        </behavior>
    </table>


// Before any database queries:

    use Spryker\PropelEncryptionBehavior\Cipher;
    Cipher::createInstance("mysecretpassphrase");


// In your program:

    $o = new MyClass();

    $o->setMyData("Some data that will remain as plain text.");
    $o->setMySecretData("Some data that will be encrypted.");

    $o->save();

// Later:

    $o = MyClassQuery::create()->findOneByMyData("Some data that will remain as plain text.");

    echo $o->getMySecretData();
    // "Some data that will be encrypted."

// If you need to use different passphrase in different queries:

    use Spryker\PropelEncryptionBehavior\Cipher;
    Cipher::resetInstance();
    Cipher::createInstance("mysecretpassphrase_2");
    
    ...(read or write data with another passphrase)

```

Given the table definition above, the string `"Some data that will be encrypted."` is encrypted in memory before being sent to the database. When we retrieve `MySecretData` later, the ciphertext is decrypted before being returned.

Note/Tradeoff
=============

**spryker/propel-encryption-behavior** *breaks Propel's native search/find/sort* methods on the encrypted field(s). Because the plain-texts of encrypted fields are not available to the database, no database method of search or sort can operate on these fields. A search or sort can only be accomplished by *retrieving all rows*, decrypting all values, and performing a search/sort on those. If you have many rows and you need to search/sort on encrypted fields, this process may be impractically slow.

Installation
===============
```BASH
composer require spryker/propel-encryption-behavior
```
Use
===

This client library provides a `Cipher` class and one Propel2 Behavior class.

To designate a field as encrypted in your Propel schema, set its type as `VARBINARY`, `LONGVARBINARY` or `BLOB` and include the `encryption` behavior.
Parameters that define encrypted columns should contain `column_name_*` prefix in the name attribute.
You may include multiple columns in the `encryption` behavior:

```
    <table name="my_class">
        <column name="id" type="integer" required="true" primaryKey="true" autoIncrement="true"/>

        <column name="my_data" type="varchar" size="255" />

        <column name="my_secret_data" type="varbinary" size="255" />
        <column name="my_secret_data2" type="varbinary" size="255" />

        <behavior name="encryption">
            <parameter name="column_name_1" value="my_secret_data" />
            <parameter name="column_name_2" value="my_secret_data2" />
        </behavior>
    </table>
```

Then build your models and database as usual.

Before querying the database, you must initialize the Cipher class with your passphrase:

```
    // Intialize the cipher
    Cipher::createInstance($my_passphrase);
```

The argument `$my_passphrase` should be a string of random characters. A length of 32-64 characters is appropriate for your passphrase. Because the cipher is initialized with every page load, the passphrase must be stored on your server in a location accessible to PHP. However, the passphrase should *not* be in a file which is viewable to web-visitors, and it almost certainly should not be included in your source/version control (git, scm, etc.).

That's it! The class setters for `MySecretData` and `MySecretData2` now seamlessly encrypt their data before it is sent to the database. The class getters for `MySecretData` and `MySecretData2` seamlessly decrypt data after retrieving it from the database.

Remember that search/find and sort are now *broken* for `MySecretData` and `MySecretData2`, for reasons discussed above.

## Filtering
By default all encrypted columns are not searchable. It's possible to make all encrypted columns of a table searchable by setting a parameter `searchable` to `true`
```
    <table name="my_class">
        <column name="id" type="integer" required="true" primaryKey="true" autoIncrement="true"/>
        <column name="my_data" type="varchar" size="255" />
        <column name="my_secret_data" type="varbinary" size="255" />

        <behavior name="encryption">
            <parameter name="column_name_1" value="my_secret_data" />
            <parameter name="searchable" value="true" />
        </behavior>
    </table>
```
It's also possible to make a particular column as searchable using `searchable_column_name_*` prefix
```
    <table name="my_class">
        <column name="id" type="INTEGER" required="true" primaryKey="true" autoIncrement="true"/>
        <column name="my_data" type="VARCHAR" size="255" />
        <column name="my_secret_data" type="BLOB" />
        <column name="my_secret_searchable_data" type="VARBINARY" size="255" />

        <behavior name="encryption">
            <parameter name="column_name_1" value="my_secret_data" />
            <parameter name="searchable_column_name_1" value="my_secret_searchable_data" />
        </behavior>
    </table>
```
> **Be aware:** For the searchable columns will be used a fixed IV. It looses data security.
