[![Build Status](https://travis-ci.org/UWEnrollmentManagement/Encryption.svg)](https://travis-ci.org/UWEnrollmentManagement/Encryption)

UWDOEM/Encryption
=============

Seamlessly encrypt/decrypt Propel2 data fields. This library is a *plugin* for the [Propel2 ORM framework](http://propelorm.org/).

For example:

```
// schema.xml

    <table name="my_class">
        <column name="id" type="integer" required="true" primaryKey="true" autoIncrement="true"/>

        <column name="my_data" type="varchar" size="255" />
        <column name="my_secret_data" type="varbinary" size="255" />

        <behavior name="encryption" id="encrypt_my_secret_data">
            <parameter name="column_name" value="my_secret_data" />
        </behavior>
    </table>


// Before any database queries:

    use /DOEM/Encryption/Cipher;
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

```

Given the table definition above, the string `"Some data that will be encrypted."` is encrypted in memory before being sent to the database. When we retrieve `MySecretData` later, the ciphertext is decrypted before being returned.

Note/Tradeoff
=============

UWDOEM/Encryption *breaks Propel's native search/find/sort* methods on the encrypted field(s). Because the plain-texts of encrypted fields are not available to the database, no database method of search or sort can operate on these fields. A search or sort can only be accomplished by *retrieving all rows*, decrypting all values, and performing a search/sort on those. If you have many rows and you need to search/sort on encrypted fields, this process may be impractically slow.

Installation
===============

This library is published on packagist. To install using Composer, add the `"uwdoem/encryption": "0.1.*"` line to your "require" dependencies:

```
{
    "require": {
        "uwdoem/encryption": ">=0.1"
    }
}
```

Of course, if you're not using Composer then you can download the repository using the *Download ZIP* button at right.

Use
===

This client library provides a `Cipher` class and one Propel2 Behavior class.

To designate a field as encrypted in your Propel schema, set its type as `varbinary` and include the `encryption` behavior. You may include multiple `encryption` behaviors for multiple encrypted fields:

```
    <table name="my_class">
        <column name="id" type="integer" required="true" primaryKey="true" autoIncrement="true"/>

        <column name="my_data" type="varchar" size="255" />

        <column name="my_secret_data" type="varbinary" size="255" />
        <column name="my_secret_data2" type="varbinary" size="255" />

        <behavior name="encryption" id="encrypt_my_secret_data">
            <parameter name="column_name" value="my_secret_data" />
        </behavior>
        <behavior name="encryption" id="encrypt_my_secret_data2">
                    <parameter name="column_name" value="my_secret_data2" />
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


Compatibility
=============

* PHP5
* Propel2

Todo
====

See GitHub [issue tracker](https://github.com/UWEnrollmentManagement/Encryption/issues/).

License
====

Employees of the University of Washington may use this code in any capacity, without reservation. I MAY release this under a less restrictive license in the future.

Getting Involved
================

Feel free to open pull requests or issues. [GitHub](https://github.com/UWEnrollmentManagement/Encryption) is the canonical location of this project.
