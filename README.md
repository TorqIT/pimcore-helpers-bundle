# Pimcore Helpers Bundle
A collection of several simple but commonly used, mockable service wrappers

- `AssetRepository`: Service wrapper for fetching, saving and deleting assets
  - `createAsset(UploadedFile $file, ElementInterface $parent)`: A helper which builds an asset from an UploadedFile
- `FolderRepository`: Service wrappers for either getting or getting/creating the three major folder types in pimcore
- `DataObjectRepository`: Abstract service wrapper for saving/deleting data objects
- `ArrayUtils`: A collection of helper functions for deep parsing arrays as well as finding items within an array
  - `get(string|string[] $key, array $array)`: Navigates either one or a series of nested keys within an array and returns either the target value or null if _any_ part of the chain does not exist. Several casting variations also exist which return specific types or null:
    - `getDate()`
    - `getInt()`
    - `getFloat()`
  - `findIndex(callable $callable, array $array)` and `findInArray(callable $callable, array $array)` functions, which find an item in an array based on a given function
